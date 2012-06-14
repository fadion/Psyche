<?php
namespace FW\Core;
use FW\Core\Response;
use FW\Core\CFG;

class Router {

	private static $pieces = array();
	private static $path;
	private static $path_extra;
	private static $routes;

	public static function start () {
		if (isset($_GET['s'])) {
			$url = rtrim($_GET['s'], ' /');

			if ($url != '') {
				static::$pieces = explode('/', $url);
			}
		}

		static::run();
	}

	public static function reroute ($controller) {
		if (empty($controller)) return false;
		
		$pieces = explode('/', $controller);
		static::$pieces = $pieces;
		static::$path_extra = '';
		
		static::run();
	}

	private static function run () {
		static::$path = CFG::CONTROLLERS_PATH;

		if (static::check_reroutes()) {
			$reroute = static::make_reroute();
			if ($reroute) {
				static::$pieces = $reroute;
			}
		}
		
		$pieces = static::find_directories();
		$params = $pieces;
		$show_error = true;
		$blocked_methods = array('route', 'before', 'after');

		$controller = CFG::DEFAULT_CONTROLLER;
		if (count($pieces)) {
			$controller = $pieces[0];
			unset($params[0]);
		}

		$controller_path = static::$path.static::$path_extra.$controller.'.php';
		$method = 'action_'.CFG::DEFAULT_METHOD;

		if (!file_exists($controller_path)) {
			$controller = CFG::DEFAULT_CONTROLLER;
			$controller_path = static::$path.static::$path_extra.$controller.'.php';

			if (isset($pieces[0])) {
				$method = 'action_'.$pieces[0];
			}
		} else {
			if (isset($pieces[1])) {
				$method = 'action_'.$pieces[1];
				unset($params[1]);
			}
		}

		$params = array_values($params);

		if (file_exists($controller_path)) {
			require_once $controller_path;

			$controller = 'FW\\Controllers\\'.$controller;
			$controller = new $controller;
		
			if (is_object($controller)) {
				if (!in_array($method, $blocked_methods)) {
					if (method_exists($controller, 'route')) {
						$method = 'route';
					}

					if (method_exists($controller, $method)) {
						$reflection = new \ReflectionMethod($controller, $method);
						$method_parameters = $reflection->getParameters();
						$parameters = null;

						if (count($method_parameters)) {
							$i = 0;
							foreach ($method_parameters as $p) {
								if (isset($params[$i])) {
									$param_value = $params[$i];
								} else {
									$param_value = null;
								}

								$parameters[] = $param_value;
							    $i++;
							}
						}

						if ($reflection->isPublic()) {
							$show_error = false;

							if (method_exists($controller, 'before')) {
								Response::write($controller, 'before');
							}

							Response::write($controller, $method, $parameters);

							if (method_exists($controller, 'after')) {
								Response::write($controller, 'after');
							}
						}
					}
				}
			}
		}

		if ($show_error) {
			Response::error();
		}
	}

	private static function find_directories () {
		$pieces = static::$pieces;
		$real_pieces = array();
		
		foreach ($pieces as $piece) {
			if (is_dir(static::$path.static::$path_extra.$piece)) {
				static::$path_extra .= $piece.'/';
			} else {
				$real_pieces[] = $piece;
			}
		}

		return $real_pieces;
	}

	public static function get ($route, $function) {
		static::manual_route($route, $function, array('GET'));
	}

	public static function post ($route, $function) {
		static::manual_route($route, $function, array('POST'));
	}

	public static function put ($route, $function) {
		static::manual_route($route, $function, array('PUT'));
	}

	public static function any ($route, $function) {
		static::manual_route($route, $function);
	}

	private static function manual_route ($route, $function, $request_type = array('GET', 'POST', 'PUT')) {
		if (empty($route)) return false;

		if (!in_array($_SERVER['REQUEST_METHOD'], $request_type)) return false;
		
		$routes = explode('/', trim($route, ' /'));
		$routes = static::analyze_manual_route($routes);
		
		$pieces = static::$pieces;
		$make_reroute = false;
		$params = array();
		$i = 0;

		if (count($routes) == count($pieces)) {
			foreach ($routes as $route) {
				if ($route == $pieces[$i] or (static::parse_params($route, $pieces[$i]) and !is_null($pieces[$i]))) {
					if (static::parse_params($route, $pieces[$i])) {
						$params[] = $pieces[$i];
					}
					$make_reroute = true;
				} else {
					$make_reroute = false;
					break;
				}

				$i++;
			}
		}

		if ($make_reroute) {
			call_user_func_array($function, $params);
		}
	}

	private static function analyze_manual_route ($routes) {
		if ($routes[0] == '-self' or $routes[0] == '-this') {
			$pieces = static::$pieces;
			$to_add = array();
			$real_pieces = array();

			foreach ($pieces as $piece) {
				if (is_dir(static::$path . $piece)) {
					$to_add[] = $piece;
				} else {
					$real_pieces[] = $piece;
				}
			}

			$routes[0] = $real_pieces[0];
			$routes = array_merge($to_add, $routes);
		}

		return $routes;
	}

	private static function parse_params ($param, $value) {
		if ($param == '-any') {
			return true;
		} elseif ($param == '-num') {
			if (is_numeric($value)) {
				return true;
			}
		} elseif ($param == '-int') {
			if (filter_var($value, FILTER_VALIDATE_INT)) {
				return true;
			}
		}
		
		return false;
	}
	
	private static function check_reroutes () {
		$routes = 'config/routes.php';
		$routes = include($routes);

		if (!count($routes)) {
			return false;
		}
		
		if (!count(self::$pieces)) {
			return false;
		}
		
		static::$routes = $routes;
		
		return true;
	}
	
	private static function make_reroute () {
		$routes = static::$routes;
		$real_route = null;
		
		foreach ($routes as $key=>$val) {
			$reroute = $val;
			$route = explode('/', trim($key, ' /'));
			$pieces = static::$pieces;
			
			$i = 0;
			$make_reroute = false;
			foreach ($route as $val) {
				if (isset($pieces[$i]) and ($val == $pieces[$i] or (static::parse_params($val, $pieces[$i]) and $pieces[$i] != null))) {
					$make_reroute = true;
					$real_route = $reroute;
					unset($pieces[$i]);
				} else {
					$make_reroute = false;
					$real_route = null;
					break;
				}
				
				$i++;
			}

			if ($make_reroute) {
				return array_merge(explode('/', $real_route), $pieces);
			}
		}
		
		return false;
	}
	
}