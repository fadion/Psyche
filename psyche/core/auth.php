<?php
namespace Psyche\Core;


/**
 * Authentication Class
 * 
 * Secure login/logout functionality 
 *
 * @package    Psyche\Core\Auth
 * @author     Baki Goxhaj 
 * @since		1.0
 * @since		1.0
 */

class Auth {
	
	/**
	* @var Key - the first of two salts we will use in the class.
	*/
	private static $_key;
	private static $_table;
	
	public function __construct() 
	{
		static::$_key = config('salt');	
		static::$_table = config('table');	
	}

	/**
	 * Generates a Random String
	 *
	 * The method makes use of the String class.
	 * @return string
	 */
	private function random_string( $length = 50 )
	{
		return Core\String::random('mix', 40);
	}
	
	/**
	 * Generates a keyed hash value using the HMAC method
	 *
	 * @return string
	 */
	private function generate_hash( $data ) 
	{
		return hash_hmac( 'sha512', $data, static::$_key ); 
	}

	public static function login( $username, $password )
	{
		/**
		 * Select user raw from database based on username.
		 */
		echo '<pre>'; print_r( $_table );
		
		$username = Query::select()->from(static::$_table)->where('email = ' . $username)->first();

		
		if( $username ) {		
			
			//Hash Password		
			$hashword = $result['salt'] . $password;
			$hashword = static::generate_hash( $hashword );
				
			if( $hashword === $result['password'] ) 
			{
				//Implement is_verified and is_active - for later date
				
				$user_id = $result['id'];
				$random = static::random_string();
				$token = $_SERVER['HTTP_USER_AGENT'] . $random;
				$token = static::generate_hash( $token );
				
				//Setup Session and Cookie vars
				setcookie('user_id', $user_id, time()+3600 );
				setcookie('token', $token, time()+3600 );

				$_SESSION['user_id'] = $user_id;
				$_SESSION['token'] = $token;
				$session_id = session_id();
				
				//Delete old logged_in_member records for user
				Db::delete("DELETE FROM sessions WHERE user_id=$user_id");
			
				//Insert new logged_in_member record for user
				$data = array(
					'user_id' => $user_id,
					'session_id' =>$session_id,
					'token' => $token 
				);
				$logged_in = Db::save('sessions', $data );			
				
				if( $logged_in ) 
				{
					return true;
				} 
				else 
				{
					return 'Could not save session';
				}			
			}
			else 
			{
				return 'Incorrect Password';
			}
		}
		else 
		{
			return 'Incorrect Username';
		}	
	}
	
	/**
	 * Check if user is logged in.
	 *
	 * @return true|false
	 */
	public static function is_logged_in()
	{	
		
		if( isset( $_SESSION['user_id'] ) && $_SESSION['user_id'] != '' && isset( $_SESSION['token'] ) && $_SESSION['token'] != '' )
		{
			$user_id = $_SESSION['user_id'];
			$token = $_SESSION['token'];
			$select = Db::one("SElECT * FROM sessions WHERE user_id='$user_id'");
		}
		elseif( isset( $_COOKIE['user_id'] ) && $_COOKIE['user_id'] != '' && isset( $_COOKIE['token'] ) && $_COOKIE['token'] != '' ) 
		{
			$user_id = $_COOKIE['user_id'];
			$token = $_COOKIE['token'];
			$select = Db::one("SElECT * FROM sessions WHERE user_id='$user_id'");
		}

		if( isset( $select ) && $select != false ) 
		{
			//Check ID and Token
			if( session_id() == $select['session_id'] && $_SESSION['token'] == $select['token'] ) 
			{
				//Id and token match, refresh the session for the next request
				static::refresh_session();
				return true;
			}
		}
		return false;
	}

	/**
	 * Sessions Fixation solution
	 */
	private function refresh_session()
	{
		//Regenerate id
		session_regenerate_id();
			
		$random = static::random_string();
		$token = $_SERVER['HTTP_USER_AGENT'] . $random;
		$token = static::generate_hash( $token );
		
		//Setup Session and Cookie vars
		setcookie('token', $token, time()+3600 );
		$_SESSION['token'] = $token;
		$session_id = session_id();

		$data = array(
			'session_id' =>$session_id,
			'token' => $token 
		);
		$logged_in = Db::save('sessions', $data, 'user_id', $_SESSION['user_id'] );
		
		return;			

	}

	/**
	* A crude logout function which doesn't validate any information before removing any of the information.
	*
	* @return none
	*/
	public static function logout() 
	{
		setcookie( 'user_id', '', time() - 3600 );
		setcookie( 'token', '', time() - 3600 );
		session_destroy();
		session_unset();
		header('Location: ' . APP_URL );
	} 
	
	public static function test() 
	{
		echo 'Works' . "\n\n";
		echo 'Key: ' . static::$_key;

	}

}