<?php
namespace FW\Core;
use FW\Core\Response;

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
 * @package FW\Core\Router
 * @author Fadion Dashi
 * @version 1.1
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

	/**
	 * Starts the router functionality.
	 * 
	 * @return void
	 */
	public static function start ()
	{
		// URLs use a single GET parameter. It is retreived and turned
		// into an array.
		if (isset($_GET['s']))
		{
			$url = rtrim($_GET['s'], ' /');

			if ($url != '')
			{
				static::$pieces = explode('/', $url);
			}
		}

		static::run();
	}

	/**
	 * Silently redirects responsability to another controller/method.
	 * 
	 * @param string $route The route to be redirected
	 * 
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

		$controller = config('default controller');

		// If piece number 1 (controller piece) is set and it's file exists, it's a controller call.
		// Otherwise, it may be a method of the index controller or an argument of index::action_index().
		if (isset($pieces[0]) and file_exists(static::$path.static::$path_extra.$pieces[0].'.php'))
		{
			$controller = $pieces[0];

			// Controller is removed from the params.
			unset($params[0]);
		}

		$controller_path = static::$path.static::$path_extra.$controller.'.php';

		if (file_exists($controller_path))
		{
			require_once $controller_path;

			$controller = '\\FW\\Controllers\\'.$controller;

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
					if (count($method_parameters) >= count($params))
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
	 * @param closure $function Closure to be executed
	 * 
	 * @return void
	 */
	public static function get ($route, $function)
	{
		static::manual_route($route, $function, array('GET'));
	}

	/**
	 * Manual route for a POST http request.
	 * 
	 * @param string $route Route to be checked
	 * @param closure $function Closure to be executed
	 * 
	 * @return void
	 */
	public static function post ($route, $function)
	{
		static::manual_route($route, $function, array('POST'));
	}

	/**
	 * Manual route for a PUT http request.
	 * 
	 * @param string $route Route to be checked
	 * @param closure $function Closure to be executed
	 * 
	 * @return void
	 */
	public static function put ($route, $function)
	{
		static::manual_route($route, $function, array('PUT'));
	}

	/**
	 * Manual route for any type of http request.
	 * 
	 * @param string $route Route to be checked
	 * @param closure $function Closure to be executed
	 * 
	 * @return void
	 */
	public static function any ($route, $function)
	{
		static::manual_route($route, $function);
	}

	/**
	 * @TODO: Test! It most probably needs some refactoring
	 */
	protected static function manual_route ($route, $function, $request_type = array('GET', 'POST', 'PUT'))
	{
		if (empty($route)) return false;

		if (!in_array($_SERVER['REQUEST_METHOD'], $request_type)) return false;
		
		$routes = explode('/', trim($route, ' /'));
		$routes = static::analyze_manual_route($routes);
		
		$pieces = static::$pieces;
		$make_reroute = false;
		$params = array();
		$i = 0;

		if (count($routes) == count($pieces))
		{
			foreach ($routes as $route)
			{
				if ($route == $pieces[$i] or (static::parse_params($route, $pieces[$i]) and !is_null($pieces[$i])))
				{
					if (static::parse_params($route, $pieces[$i]))
					{
						$params[] = $pieces[$i];
					}
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
			call_user_func_array($function, $params);
		}
	}

	/**
	 * @TODO: Test! It most probably needs some refactoring
	 */
	protected static function analyze_manual_route ($routes)
	{
		if ($routes[0] == '-self' or $routes[0] == '-this')
		{
			$pieces = static::$pieces;
			$to_add = array();
			$real_pieces = array();

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

			$routes[0] = $real_pieces[0];
			$routes = array_merge($to_add, $routes);
		}

		return $routes;
	}

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
	 * @TODO: Test! It most probably needs some refactoring
	 */
	protected static function check_reroutes ()
	{
		$routes = 'config/routes.php';
		$routes = include($routes);

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
	 * @TODO: Test! It most probably needs some refactoring
	 */
	protected static function make_reroute ()
	{
		$routes = static::$routes;
		$real_route = null;
		
		foreach ($routes as $key=>$val)
		{
			$reroute = $val;
			$route = explode('/', trim($key, ' /'));
			$pieces = static::$pieces;
			
			$i = 0;
			$make_reroute = false;
			foreach ($route as $val)
			{
				if (isset($pieces[$i]) and ($val == $pieces[$i] or (static::parse_params($val, $pieces[$i]) and $pieces[$i] != null)))
				{
					$make_reroute = true;
					$real_route = $reroute;
					unset($pieces[$i]);
				}
				else
				{
					$make_reroute = false;
					$real_route = null;
					break;
				}
				
				$i++;
			}

			if ($make_reroute)
			{
				return array_merge(explode('/', $real_route), $pieces);
			}
		}
		
		return false;
	}
	
}