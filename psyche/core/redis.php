<?php
namespace Psyche\Core;

class Redis
{

	/**
	 * @var resource The active socket connection.
	 */
	public $connection;

	/**
	 * @var string Redis commands are terminated with a CRLF.
	 */
	protected $crlf = "\r\n";

	/**
	 * Reads the redis configuration for templates and makes the connection.
	 * 
	 * @param string $template
	 */
	public function __construct ($template = null)
	{
		$templates = config('redis:');

		// If a template is defined as parameter.
		if (isset($template))
		{	
			// If the template exists in the redis config, read it.
			// Otherwise throw an exception.
			if (isset($templates['templates'][$template]))
			{
				$template = $templates['templates'][$template];
			}
			else
			{
				throw new \Exception(sprintf("Template %s doesn't exist in the Redis configuration.", $template));
			}
		}
		else
		{
			$template = $templates['templates'][$templates['use']];
		}

		// Try making a socket connection to the server.
		$this->connection = fsockopen('tcp://'.$template['host'], $template['port'], $errNo, $errStr);

		if (!$this->connection)
		{
			throw new \Exception("Couldn't connect to the Redis server: $errStr");
		}

		// If a password is set in the config, send an AUTH command.
		if ($template['password'] !== '')
		{
			$this->auth($template['password']);
		}

		// If a DB is set, send a SELECT command.
		if ($template['db'] !== '' and $template['db'] !== 0)
		{
			$this->select($template['db']);
		}
	}

	/**
	 * Factory static method.
	 * 
	 * @param string $template
	 * @return Redis
	 */
	public static function connect ($template = null)
	{
		return new static($template);
	}

	/**
	 * Disconnects on object destruction.
	 */
	public function __destruct ()
	{
		$this->disconnect();
	}

	/**
	 * Disconnects from the Redis server.
	 * 
	 * @return void
	 */
	public function disconnect ()
	{
		fclose($this->connection);
	}

	/**
	 * Sends a command to the Redis server.
	 * 
	 * @return mixed
	 */
	public function __call ($method, $arguments)
	{
		// Commands are uppercase. It is also added
		// to the arguments list, as in the Redis protocol
		// the command is the first argument.
		$method = strtoupper($method);
		array_unshift($arguments, $method);

		// *[number of arguments]
		$cmd = '*'.count($arguments).$this->crlf;

		// Each argument is iterated to build the specific
		// command portion.
		foreach ($arguments as $argument)
		{
			// $[number of bytes of argument]
			$cmd .= '$'.strlen($argument).$this->crlf;
			// [argument data]
			$cmd .= $argument.$this->crlf;
		}

		fwrite($this->connection, $cmd);

		return $this->reply();
	}

	/**
	 * Reads the reply from the Redis server.
	 * 
	 * @return mixed
	 */
	public function reply ()
	{
		$reply = trim(fgets($this->connection));

		// Gets the first character of the reply, which
		// is the status.
		$status = substr($reply, 0, 1);

		// Gets the actual reply data.
		$result = trim(substr($reply, 1));

		// Single line reply.
		if ($status == '+')
		{
			return $result;
		}
		// Error message.
		elseif ($status == '-')
		{
			throw new \Exception("Redis: ".$result);
		}
		// Integer reply.
		elseif ($status == ':')
		{
			return (int) $result;
		}
		// Bulk reply.
		elseif ($status == '$')
		{
			// Empty replies should return NULL.
			if($reply === '$-1')
			{
				return null;
			}

			return substr(fread($this->connection, substr($reply, 1) + strlen($this->crlf)), 0, - strlen($this->crlf));
		}
		// Multi-bulk reply.
		elseif ($status == '*')
		{
			// Empty replies should return NULL.
			if($reply === '*-1')
			{
				return null;
			}

			$data = array();

			// Iterate through the number of replies and
			// get the result for each one into an array.
			for($i = 0; $i < $result; $i++)
			{
				$data[] = $this->reply();
			}

			return $data;
		}
		// No reply.
		else
		{
			throw new \Exception("The Redis server didn't send a reply.");
		}
	}

	/**
	 * Shortcut to send commands via a static call using the
	 * default redis template.
	 * 
	 * @return mixed
	 */
	public static function send ()
	{
		if (!func_num_args())
		{
			return false;
		}

		$arguments = func_get_args();

		// The command is the first argument.
		$command = $arguments[0];
		unset($arguments[0]);

		return call_user_func_array(array(static::connect(), $command), $arguments);
	}

	/**
	 * Another shortcut to send commands directly via static
	 * methods using the default redis template.
	 * 
	 * @return mixed
	 */
	public static function __callStatic ($method, $arguments)
	{
		return call_user_func_array(array(static::connect(), $method), $arguments);
	}

}