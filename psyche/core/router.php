<?php
namespace Psyche\Core;
use Psyche\Core\Response,
	Psyche\Core\Uri,
	Psyche\Core\Event;

/**
 * Router
 * 
 * A fully featured and flexible router that maps url parameters to
 * controllers, methods and function arguments. It's supposed to do
 * anything automatically, but for flexibility, it has a few methods
 * to override the default behaviour, such as: rerouting, before and
 * after methods, route method that catches all routes for that
 * controller, etc.
 *
 * @package Psyche\Core\Router
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Router
{

	/**
	 * @var array URL pieces
	 */
	protected static $pieces = array();

	/**
	 * @var string Base path to controllers
	 */
	protected static $path;

	/**
	 * @var string Appended to the base path if the controller is in a sub-folder
	 */
	protected static $path_extra;

	/**
	 * @var array The rules on the (re)routes config file
	 */
	protected static $routes;

	protected static $errors = array();

	/**
	 * Starts the router functionality.
	 * 
	 * @return void
	 */
	public static function start ()
	{
		$url = Uri::parse_url();

		if ($url)
		{
			static::$pieces = $url;
		}

		static::run();
	}

	/**
	 * Silently redirects responsability to another controller/method.
	 * 
	 * @param string $route The route to be redirected
	 * @return void
	 */
	public static function reroute ($route)
	{
		if (empty($route)) return false;
		
		$pieces = explode('/', $route);
		static::$pieces = $pieces;
		static::$path_extra = '';
		
		// The router is run again with the defaults reset. It has a bit
		// overhead, but there's no way around because the router is started
		// before almost anything else.
		static::run();
	}
	
	/**
	 * Most of the router logic.
	 * 
	 * @return void
	 */
	protected static function run ()
	{
		static::$path = config('controllers path');

		// If a reroute exists, modifies the url pieces so they
		// point to another controller/method.
		if (static::check_reroutes())
		{
			$reroute = static::make_reroute();
			if ($reroute)
			{
				static::$pieces = $reroute;
			}
		}
		
		$pieces = static::find_directories();

		// Params will end up as the URL pieces that don't belog to a
		// controller and/or method.
		$params = $pieces;
		$show_error = true;
		$blocked_methods = array('route', 'before', 'after');

		$controller_name = config('default controller');

		// If piece number 1 (controller piece) is set and it's file exists, it's a controller call.
		// Otherwise, it may be a method of the index controller or an argument of index::action_index().
		if (isset($pieces[0]) and file_exists(static::$path.static::$path_extra.$pieces[0].'.php'))
		{
			$controller_name = $pieces[0];

			// Controller is removed from the params.
			unset($params[0]);
		}

		$controller_path = static::$path.static::$path_extra.$controller_name.'.php';

		if (file_exists($controller_path))
		{
			require_once $controller_path;

			$controller = '\\Psyche\\Controllers\\'.$controller_name;

			// It knows the file exists, but the controller class be be undefined.
			// The "false" as second argument prevents class_exists() from calling
			// the autoloader.
			if (class_exists($controller, false))
			{
				$controller = new $controller;
				$r_class = new \ReflectionClass($controller);

				$method = 'action_'.config('default method');

				// The route() method will handle any possible call to that controller.
				if ($r_class->hasMethod('route'))
				{
					$method = 'route';
				}
				// If a method is set, it should exist in the controller too.
				elseif (isset($pieces[1]) and $r_class->hasMethod('action_'.$pieces[1]))
				{
					$method = 'action_'.$pieces[1];

					// Method is removed from the params.
					unset($params[1]);
				}
				// Lastly, check if the first piece is a method for index controllers.
				elseif (isset($pieces[0]) and $r_class->hasMethod('action_'.$pieces[0]))
				{
					$method = 'action_'.$pieces[0];

					unset($params[0]);
				}

				// We know that the class exists and it may have a method. What's of
				// interest now are the method arguments, as those will be compared
				// with the URL parameters.
				$r_method = new \ReflectionMethod($controller, $method);
				$method_parameters = $r_method->getParameters();
				$parameters = null;

				// A private or protected method shouldn't be called from the URL.
				if ($r_method->isPublic())
				{
					$params = array_values($params);

					// Ideally, method arguments should match URL parameters (without the
					// controller and/or method). However, arguments are treated as ungreedy.
					// There may be as many arguments as needed and they aren't obligatory
					// to be reflected in the URL parameters. On the contrary, URL parameters
					// shouldn't be more than the number of arguments.
					if (count($method_parameters) >= count($params) or $method == 'route')
					{
						$show_error = false;

						if (count($method_parameters))
						{
							// Builds an array with the right number of parameters, based
							// on the number of arguments.
							$i = 0;
							foreach ($method_parameters as $p)
							{
								if (isset($params[$i]))
								{
									$param_value = $params[$i];
								}
								else
								{
									$param_value = null;
								}

								$parameters[] = $param_value;
							    $i++;
							}
						}

						// The before() method is always called before the actual
						// method.
						if (method_exists($controller, 'before'))
						{
							Response::write($controller, 'before');
						}

						Response::write($controller, $method, $parameters);

						// The after() method is always called after the actual
						// method.
						if (method_exists($controller, 'after'))
						{
							Response::write($controller, 'after');
						}

						// As Gizmo Toolbar is executed almost last, listen for it's event
						// trigger to pass the active controller and method.
						Event::on('psyche gizmo', function() use ($controller_name, $method)
						{
							return array($controller_name, $method);
						});
					}		
				}
			}
		}

		// If the conditions above aren't all satisfied, a 404 error will be triggered.
		if ($show_error)
		{
			Response::error(404);
		}
	}

	/**
	 * Builds correct directories for controllers that reside in sub-folders.
	 * 
	 * @return void
	 */
	protected static function find_directories ()
	{
		$pieces = static::$pieces;
		$real_pieces = array();
		
		foreach ($pieces as $piece)
		{
			if (is_dir(static::$path.static::$path_extra.$piece))
			{
				static::$path_extra .= $piece.'/';
			}
			else
			{
				$real_pieces[] = $piece;
			}
		}

		return $real_pieces;
	}

	/**
	 * Manual route for a GET http request.
	 * 
	 * @param string $route Route to be checked
	 * @param closure $callback Closure to be executed
	 * @return void
	 */
	public static function get ($route, $callback)
	{
		static::manual_route($route, $callback, array('GET'));
	}

	/**
	 * Manual route for a POST http request.
	 * 
	 * @param string $route Route to be checked
	 * @param closure $callback Closure to be executed
	 * @return void
	 */
	public static function post ($route, $callback)
	{
		static::manual_route($route, $callback, array('POST'));
	}

	/**
	 * Manual route for a PUT http request.
	 * 
	 * @param string $route Route to be checked
	 * @param closure $callback Closure to be executed
	 * @return void
	 */
	public static function put ($route, $callback)
	{
		static::manual_route($route, $callback, array('PUT'));
	}

	/**
	 * Manual route for any type of http request.
	 * 
	 * @param string $route Route to be checked
	 * @param closure $callback Closure to be executed
	 * @return void
	 */
	public static function any ($route, $callback)
	{
		static::manual_route($route, $callback);
	}

	/**
	 * Checks manual routes with the current URL and executes a closure
	 * if it's successful.
	 * 
	 * @param string $route Route to be checked
	 * @param closure $callback Closure to be executed
	 * @param array $request_type The http request type/types
	 * @return void
	 */
	protected static function manual_route ($route, $callback, $request_type = array('GET', 'POST', 'PUT'))
	{
		// An empty route triggers an error
		if (empty($route))
		{
			static::$errors[] = 1;
			return false;
		}

		// An invalid http request triggers an error
		if (!in_array($_SERVER['REQUEST_METHOD'], $request_type))
		{
			static::$errors[] = 1;
			return false;
		}
		
		// Explode route into pieces
		$routes = explode('/', trim($route, ' /'));
		$routes = static::analyze_manual_route($routes);
		
		$pieces = static::$pieces;
		$make_reroute = false;
		$params = array();
		$i = 0;

		// Number of routes should be the same as the current url pieces.
		if (count($routes) == count($pieces))
		{
			foreach ($routes as $route)
			{
				// For the manual route to be successful, every piece should
				// be the same as the url piece in the same position, or have
				// a wildcard whose value corresponds with the data type (-any,
				// -num, -int). If a single manual piece doesn't fulfill the
				// requirements, the route is discarded.
				if ($route == $pieces[$i])
				{
					$make_reroute = true;
				}
				elseif (static::parse_params($route, $pieces[$i]) and isset($pieces[$i]))
				{
					// $param holds any url parameter that corresponds to a wildcard.
					$params[] = $pieces[$i];
					$make_reroute = true;
				}
				else
				{
					$make_reroute = false;
					break;
				}

				$i++;
			}
		}

		if ($make_reroute)
		{
			static::$errors[] = 0;
			call_user_func_array($callback, $params);
		}
		else
		{
			static::$errors[] = 1;
		}
	}

	/**
	 * Triggers a 404 error if all the manual routes in a controller are
	 * treated as invalid. Should be called after all manual routes are
	 * created.
	 * 
	 * @return void
	 */
	public static function errors ()
	{
		if (!count(static::$errors) or !in_array('0', static::$errors))
		{
			Response::error(404);
		}
	}

	/**
	 * Checks the manual route for a -self or -this wildcard and
	 * replaces them with the current controller. It works even for
	 * controllers nested in sub-folders, as it checks each piece
	 * if it's a directory.
	 * 
	 * @param array $routes The manual route pieces
	 * @return array
	 */
	protected static function analyze_manual_route ($routes)
	{
		if ($routes[0] == '-self' or $routes[0] == '-this')
		{
			$pieces = static::$pieces;
			$to_add = array();
			$real_pieces = array();

			// Each found directory is added in a separate array.
			foreach ($pieces as $piece)
			{
				if (is_dir(static::$path . $piece))
				{
					$to_add[] = $piece;
				}
				else
				{
					$real_pieces[] = $piece;
				}
			}

			// The controller is the first piece of the url pieces
			// with the directories removed. Finally it's merged with
			// the directories.
			$routes[0] = $real_pieces[0];
			$routes = array_merge($to_add, $routes);
		}

		return $routes;
	}

	/**
	 * Parses wildcards and checks if the value is of the correct data type.
	 * 
	 * @param string $param The route piece
	 * @param string $value The url value corresponding to the route piece
	 * @return bool
	 */
	protected static function parse_params ($param, $value)
	{
		if ($param == '-any')
		{
			return true;
		}
		elseif ($param == '-num')
		{
			if (is_numeric($value)) 
			{
				return true;
			}
		}
		elseif ($param == '-int')
		{
			if (filter_var($value, FILTER_VALIDATE_INT))
			{
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Checks the config file for any reroutes.
	 * 
	 * @return bool
	 */
	protected static function check_reroutes ()
	{
		$routes = config('routes:');

		if (!count($routes))
		{
			return false;
		}
		
		if (!count(self::$pieces))
		{
			return false;
		}
		
		static::$routes = $routes;
		
		return true;
	}
	
	/**
	 * Checks any reroute from the config if it matches the current url.
	 * The idea is pretty much similiar to the manual routes, but with a
	 * few changes.
	 * 
	 * @return bool|array
	 */
	protected static function make_reroute ()
	{
		$routes = static::$routes;
		$real_route = null;

		// Each reroute is checked individually. $key is the route
		// to be checked and $val is the reroute.
		foreach ($routes as $key=>$val)
		{
			$reroute = $val;
			$route = explode('/', trim($key, ' /'));
			$pieces = static::$pieces;
			
			$i = 0;
			$make_reroute = false;

			if (count($pieces) == count($route))
			{
				foreach ($route as $val)
				{
					// Pieces should be equal to url parameters or have the appropriate
					// value for wildcards. If a single route doesn't fulfill the conditions,
					// the reroute is discarded completely.
					if (isset($pieces[$i]) and ($val == $pieces[$i] or (static::parse_params($val, $pieces[$i]) and $pieces[$i] != null)))
					{
						$make_reroute = true;
						$real_route = $reroute;
					}
					else
					{
						$make_reroute = false;
						$real_route = null;
						break;
					}
					
					$i++;
				}
			}

			// For a successfull reroute, the route pieces are returned
			if ($make_reroute)
			{
				return explode('/', $real_route);
			}
		}
		
		return false;
	}
	
}