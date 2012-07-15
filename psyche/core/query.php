<?php
namespace Psyche\Core;
use Psyche\Core\DB;

/**
 * Factory for initializing the Query Builder with the
 * selected driver. The driver is retrieved automatically
 * from the PDO connection.
 *
 * @package Psyche\Core\Query
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Query
{

	/**
	 * Catches any static call to the Query class and passes
	 * responsability to the driver.
	 * 
	 * @return object
	 */
	public static function __callStatic ($method, $arguments)
	{
		$driver = DB::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME);

		switch ($driver)
		{
			case 'mysql':
				return call_user_func_array(array('\Psyche\Core\Database\Dialects\MySQL', $method), $arguments);
				break;
			case 'oci':
				return call_user_func_array(array('\Psyche\Core\Database\Dialects\Oracle', $method), $arguments);
				break;
			case 'sqlite':
				return call_user_func_array(array('\Psyche\Core\Database\Dialects\SQLite', $method), $arguments);
				break;
			case 'pgsql':
				return call_user_func_array(array('\Psyche\Core\Database\Dialects\PostgreSQL', $method), $arguments);
				break;
			case 'sqlsrv':
				return call_user_func_array(array('\Psyche\Core\Database\Dialects\MsSQL', $method), $arguments);
				break;
		}
	}

}