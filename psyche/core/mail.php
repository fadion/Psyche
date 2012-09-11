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
	
	//Necessary
	protected $to = null;
	protected $subject = null;
	protected $message = null;
	protected $headers = null;
	
	//Optional
	protected $cc = null;
	protected $bcc = null;
	protected $from = null;
	protected $reply_to = null;
	protected $attachments = array();

	/**
	 * Constructor. Sets the email handler.
	 * 
	 * @param string $from Path to the image file
	 */	
	public function __construct ($input) 
	{
 		$args = func_num_args();
		if($args = 1) 
		{
			if( is_array( $input ) )
			{
				$this->to = implode(', ', $input);	
			}
			else 
			{
				$this->to = $input;
			}
		} 
		elseif($args >= 2)
		{	
			$this->to = '';
			for($i=0; $i < $args; $i++)
			{
                $this->to .= func_get_arg($i);
            }			
				
		} 
		else 
		{
			trigger_error('Mail cannot figure out where to send your mail.', E_USER_WARNING);
		}

	}

	/**
	 * Recipients, comma-separated
	 *
	 * Can be direct email or in the form of: Sender Name <email@host.com>	 
	 * @param string
	 * @return string
	 */
	public static function to($input) 
	{
		return new static($input);
	}
	
	/**
	 * Carbon Copy Recipients, comma-separated
	 *
	 * Can be direct email or in the form of: Sender Name <email@host.com>	 
	 * @param string
	 * @return string
	 */	
	public function cc($address) 
	{
		$this->cc = $address;
		return $this;
	}
	
	public function bcc($address) {
		$this->bcc = $address;
		return $this;
	}

	/**
	 * From address
	 *
	 * Can be direct email or in the form of: Sender Name <email@host.com>
	 * @param string
	 * @return string
	*/
	public function from($from)
	{
		$this->from = $from;
	
		if(is_null($this->reply_to)) 
		{
			$this->reply_to = $from;
		}
	
		return $this;
	}
		
	/**
	 * Reply to address
	 *
	 * Can be direct email or in the form of: Sender Name <email@host.com>	 
	 * @param string
	 * @return string
	 */	
	public function reply_to($address) 
	{
		$this->reply_to = $address;
		return $this;
	}

	/**
	 * Email Subject
	 *
	 * @param string
	 * @return string
	 */	
	public function subject($subject) 
	{
		$this->subject = $subject;
		return $this;
	}

	/**
	 * Email Message
	 *
	 * @param string
	 * @return string
	 */	
	public function message($message) 
	{
		$this->message = $message;
		return $this;
	}

	/**
	 * Email Attachments
	 *
	 * @param string
	 * @return string
	 */		
	public function attachment($file_path) 
	{
		$this->attachments[] = $file_path;
		return $this;
	}
	
	protected function headers() 
	{
		if(!$this->headers) {
			$this->headers = "MIME-Version: 1.0" . PHP_EOL;
			$this->headers .= 'Content-type: text/html; charset=iso-8859-1' . PHP_EOL;	
			$this->headers .= "To: " . $this->to . PHP_EOL;
			$this->headers .= "From: " . $this->from . PHP_EOL;
			$this->headers .= "Reply-To: " . $this->reply_to . PHP_EOL;
			$this->headers .= "Return-Path: " . $this->from . PHP_EOL;
			
			if($this->cc) {
				$this->headers .= "Cc: " . $this->cc . PHP_EOL;
			}
			
			if($this->bcc) {
				$this->headers .= "Bcc: " . $this->bcc . PHP_EOL;
			}
			
			$str = "";
			if($this->attachments) {
				$random_hash = md5(date('r', time()));
				$this->headers .= "Content-Type: multipart/mixed; boundary=\"PHP-mixed-".$random_hash."\"" . PHP_EOL;
				
				$pos = strpos($this->message, "<html>");
				if ($pos === false) {
					$str .= "--PHP-mixed-$random_hash" . PHP_EOL;
					$str .= "Content-Type: text/plain; charset=\"utf-8\"" . PHP_EOL;
					$str .= "Content-Transfer-Encoding: 7bit" . PHP_EOL;
					$str .= $this->message . PHP_EOL;
				}
				
				if ($pos == 0) {
					$str .= "--PHP-mixed-$random_hash" . PHP_EOL;
					$str .= "Content-Type: text/html; charset=\"utf-8\"" . PHP_EOL;
					$str .= "Content-Transfer-Encoding: 7bit" . PHP_EOL;
					$str .= $this->message . PHP_EOL;
				}
				
				if ($pos > 0) {
					$str .= "Content-Type: multipart/alternative; boundary=\"PHP-alt-".$random_hash."\"" . PHP_EOL;
					$str .= "--PHP-alt-$random_hash" . PHP_EOL;
					$str .= "Content-Type: text/plain; charset=\"utf-8\"" . PHP_EOL;
					$str .= "Content-Transfer-Encoding: 7bit";
					$str .= substr($this->message, 0, $pos);
					$str .= PHP_EOL;
					$str .= "--PHP-alt-$random_hash" . PHP_EOL;
					$str .= "Content-Type: text/html; charset=\"utf-8\"" . PHP_EOL;
					$str .= "Content-Transfer-Encoding: 7bit";
					$str .= substr($this->message, $pos);
					$str .= "--PHP-alt-$random_hash--" . PHP_EOL;
				}
				
				foreach ($this->attachments as $key => $value) {
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
				$pos = strpos($this->message, "<html>");
				if ($pos === false) {
					$headers .= "Content-Type: text/plain; charset=\"utf-8\"" . PHP_EOL;
					$headers .= "Content-Transfer-Encoding: 7bit";
					$str .= $this->message . PHP_EOL;
				}
				
				if ($pos === 0) {
					$headers .= "Content-Type: text/html; charset=\"utf-8\"" . PHP_EOL;
					$headers .= "Content-Transfer-Encoding: 7bit";
					$str .= $this->message . PHP_EOL;
				}
				
				if ($pos > 0) {
					$random_hash = md5(date('r', time()));
					$headers .= "Content-Type: multipart/alternative; boundary=\"PHP-alt-".$random_hash."\"" . PHP_EOL;
					$str .= "--PHP-alt-$random_hash" . PHP_EOL;
					$str .= "Content-Type: text/plain; charset=\"utf-8\"" . PHP_EOL;
					$str .= "Content-Transfer-Encoding: 7bit";
					$str .= substr($this->message, 0, $pos);
					$str .= PHP_EOL;
					$str .= "--PHP-alt-$random_hash" . PHP_EOL;
					$str .= "Content-Type: text/html; charset=\"utf-8\"" . PHP_EOL;
					$str .= "Content-Transfer-Encoding: 7bit";
					$str .= substr($this->message, $pos);
					$str .= "--PHP-alt-$random_hash--" . PHP_EOL;
				}
			}
			$this->message = $str;
		}
	}

	/**
	 * Send method
	 *
	 * Checks everything is right and sends email to destination.
	 */
	 	
	public function send() 
	{
		if (is_null($this->to)) 
		{
			trigger_error('No recipient specified.', E_USER_WARNING);
		}
		
		if (is_null($this->from)) 
		{
			trigger_error('No sender specified.', E_USER_WARNING);
		}
			
		if (is_null($this->message)) 		
		{
			trigger_error('Message is empty.', E_USER_WARNING);
		}
		
		$this->headers();
		$sent = mail($this->to, $this->subject, $this->message, $this->headers);
		if(!$sent) {
			trigger_error('Server cannot send the email.', E_USER_WARNING);
		} else {
			return true;
		}
	}
		
}
