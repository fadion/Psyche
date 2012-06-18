<?php
namespace FW\Core;

class DB {

	protected static $templates;
	protected static $dbh;
	protected static $results;
	protected static $values;

	public static function connect ($template = null)
	{
		static::read_templates();

		if (!is_null($template) and isset(static::$templates[$template]))
		{
			$template = static::$templates[$template];
		}
		else
		{
			$template = array_values(static::$templates);
			$template = $template[0];
		}

		try
		{
			static::$dbh = new \PDO($template['type'].':host='.$template['host'].';dbname='.$template['name'], $template['user'], $template['password']);
			static::$dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);  
		}
		catch (\PDOException $e)
		{
			trigger_error('Database: '.$e->getMessage(), FATAL);
		}
	}

	public static function query ($sql)
	{
		try
		{
			static::$results = static::$dbh->prepare($sql);
			static::$results->setFetchMode(\PDO::FETCH_OBJ);

			$args = func_get_args();
			unset($args[0]);

			if (is_array($args[1]) and count($args[1]))
			{
				static::$results->execute($args[1]);
			}
			else
			{
				foreach ($args as $key => $val)
				{
					static::$results->bindParam($key, $val);
				}

				static::$results->execute();
			}

			static::$values = static::$results->fetchAll();

			return static::$values;
		}
		catch (\PDOException $e)
		{
			trigger_error('Database: '.$e->getMessage(), FATAL);
		}
	}

	protected static function read_templates ()
	{
		if (isset(static::$templates))
		{
			return;
		}

		$file = 'config/database.php';

		if (!file_exists($file))
		{
			trigger_error("Database configuration not found", FATAL);
			return;
		}

		static::$templates = include($file);
	}

}