<?php
namespace Psyche\Core;

/**
 * Mail
 * 
 * Sends emails in text and html mode with attachments.
 *
 * @package Psyche\Core\Mail
 * @author Baki Goxhaj
 * @version 1.0
 * @since 1.0
 */

class Mail {
	
	protected static $to = null;
	protected static $subject = null;
	protected static $message = null;
	protected static $headers = null;
	
	protected static $cc = null;
	protected static $bcc = null;
	protected static $from = null;
	protected static $reply_to = null;
	protected static $attachments = array();
	
	public function __construct($type="html") 
	{
		if( $type = 'text' ){
			// To be implemented	
		}
		else 
		{
			// To be implemented	
		}
	}
	
	public static function send() 
	{
		if (is_null(static::$to)) 
		{
			throw new Exception("Must have at least one recipient.");
		}
		
		if (is_null(static::$from)) 
		{
			throw new Exception("Must have one, and only one sender set.");
		}
		
		if (is_null(static::$subject)) 
		{
			throw new Exception("Subject is empty.");
		}
		
		if (is_null(static::$message)) 		
		{
			throw new Exception("Message is empty.");
		}
		
		static::headers();
		$sent = mail(static::$to, static::$subject, static::$message, static::$headers);
		if(!$sent) {
			$error = "Server couldn't send the email.";
			throw new Exception($error);
		} else {
			return true;
		}
	}
	
	/**
	 * Recipients, comma-separated
	 *
	 * Can be direct email or in the form of: Sender Name <email@host.com>	 
	 * @param string
	 * @return string
	 */
	public static function to($address) 
	{
		static::$to = $address;
		return new static;
	}
	
	/**
	 * Carbon Copy Recipients, comma-separated
	 *
	 * Can be direct email or in the form of: Sender Name <email@host.com>	 
	 * @param string
	 * @return string
	 */	
	public static function cc($address) 
	{
		static::$cc = $address;
		return new static;
	}
	
	public static function bcc($address) {
		static::$bcc = $address;
		return new static;
	}
	
	/**
	 * From address
	 *
	 * Can be direct email or in the form of: Sender Name <email@host.com>	 
	 * @param string
	 * @return string
	 */
	public static function from($from) 
	{
		static::$from = $from . PHP_EOL;
		
		if(is_null(static::$reply_to)) {
			static::$reply_to = $from. PHP_EOL;
		}

		return new static;
	}

	/**
	 * Reply to address
	 *
	 * Can be direct email or in the form of: Sender Name <email@host.com>	 
	 * @param string
	 * @return string
	 */	
	public static function reply_to($address) 
	{
		static::$reply_to = $address . PHP_EOL;
		return new static;
	}

	/**
	 * Email Subject
	 *
	 * @param string
	 * @return string
	 */	
	public static function subject($subject) 
	{
		static::$subject = $subject;
		return new static;
	}

	/**
	 * Email Message
	 *
	 * @param string
	 * @return string
	 */	
	public static function message($message) 
	{
		static::$message = $message;
		return new static;
	}
	
	public static function attachment($file_path) 
	{
		static::$attachments[] = $file_path;
		return new static;
	}
	
	private static function headers() 
	{
		if (!static::$headers) {
			static::$headers = "MIME-Version: 1.0" . PHP_EOL;
			static::$headers .= "To: " . static::$to . PHP_EOL;
			static::$headers .= "From: " . static::$from . PHP_EOL;
			static::$headers .= "Reply-To: " . static::$reply_to . PHP_EOL;
			static::$headers .= "Return-Path: " . static::$from . PHP_EOL;
			
			if (static::$cc) {
				static::$headers .= "Cc: " . static::$cc . PHP_EOL;
			}
			
			if (static::$bcc) {
				static::$headers .= "Bcc: " . static::$bcc . PHP_EOL;
			}
			
			$str = "";
			if (static::$attachments) {
				$random_hash = md5(date('r', time()));
				static::$headers .= "Content-Type: multipart/mixed; boundary=\"PHP-mixed-".$random_hash."\"" . PHP_EOL;
				
				$pos = strpos(static::$message, "<html>");
				if ($pos === false) {
					$str .= "--PHP-mixed-$random_hash" . PHP_EOL;
					$str .= "Content-Type: text/plain; charset=\"utf-8\"" . PHP_EOL;
					$str .= "Content-Transfer-Encoding: 7bit" . PHP_EOL;
					$str .= static::$message . PHP_EOL;
				}
				
				if ($pos == 0) {
					$str .= "--PHP-mixed-$random_hash" . PHP_EOL;
					$str .= "Content-Type: text/html; charset=\"utf-8\"" . PHP_EOL;
					$str .= "Content-Transfer-Encoding: 7bit" . PHP_EOL;
					$str .= static::$message . PHP_EOL;
				}
				
				if ($pos > 0) {
					$str .= "Content-Type: multipart/alternative; boundary=\"PHP-alt-".$random_hash."\"" . PHP_EOL;
					$str .= "--PHP-alt-$random_hash" . PHP_EOL;
					$str .= "Content-Type: text/plain; charset=\"utf-8\"" . PHP_EOL;
					$str .= "Content-Transfer-Encoding: 7bit";
					$str .= substr(static::$message, 0, $pos);
					$str .= PHP_EOL;
					$str .= "--PHP-alt-$random_hash" . PHP_EOL;
					$str .= "Content-Type: text/html; charset=\"utf-8\"" . PHP_EOL;
					$str .= "Content-Transfer-Encoding: 7bit";
					$str .= substr(static::$message, $pos);
					$str .= "--PHP-alt-$random_hash--" . PHP_EOL;
				}
				
				foreach (static::$attachments as $key => $value) {
					$mime_type = mime_content_type($value);
					//$mime_type = "image/jpeg";
					$attachment = chunk_split(base64_encode(file_get_contents($value)));
					$fileName = basename("$value");
					$str .= "--PHP-mixed-$random_hash" . PHP_EOL;
					$str .= "Content-Type: $mime_type; name=\"$fileName\"" . PHP_EOL;
					$str .= "Content-Disposition: attachment" . PHP_EOL;
					$str .= "Content-Transfer-Encoding: base64" . PHP_EOL;
					$str .= PHP_EOL;
					$str .= "$attachment";
					$str .= PHP_EOL;
				}
				$str .= "--PHP-mixed-$random_hash--" . PHP_EOL;
			} else {
				$pos = strpos(static::$message, "<html>");
				if ($pos === false) {
					$headers .= "Content-Type: text/plain; charset=\"utf-8\"" . PHP_EOL;
					$headers .= "Content-Transfer-Encoding: 7bit";
					$str .= static::$message . PHP_EOL;
				}
				
				if ($pos === 0) {
					$headers .= "Content-Type: text/html; charset=\"utf-8\"" . PHP_EOL;
					$headers .= "Content-Transfer-Encoding: 7bit";
					$str .= static::$message . PHP_EOL;
				}
				
				if ($pos > 0) {
					$random_hash = md5(date('r', time()));
					$headers .= "Content-Type: multipart/alternative; boundary=\"PHP-alt-".$random_hash."\"" . PHP_EOL;
					$str .= "--PHP-alt-$random_hash" . PHP_EOL;
					$str .= "Content-Type: text/plain; charset=\"utf-8\"" . PHP_EOL;
					$str .= "Content-Transfer-Encoding: 7bit";
					$str .= substr(static::$message, 0, $pos);
					$str .= PHP_EOL;
					$str .= "--PHP-alt-$random_hash" . PHP_EOL;
					$str .= "Content-Type: text/html; charset=\"utf-8\"" . PHP_EOL;
					$str .= "Content-Transfer-Encoding: 7bit";
					$str .= substr(static::$message, $pos);
					$str .= "--PHP-alt-$random_hash--" . PHP_EOL;
				}
			}
			static::$message = $str;
		}
	}
}
