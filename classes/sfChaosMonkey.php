<?php
/**
 * The Chaos Monkey [http://www.codinghorror.com/blog/2011/04/working-with-the-chaos-monkey.html]
 * 
 * What it says on the tin.
 * 
 * Designed to be run on cron against testing servers, the chaos monkey randomly sends malformed
 * requests to the designated addresses. It is fed with raw URLs with placeholders within, but it
 * doesn't necessarily follow those rules. Additionally, it may be given directories in which it may
 * delete files at absolutely random. 
 * 
 * @author Rizqi Djamaluddin <rizqidjamaluddin@gmail.com>
 * 
 * @package Swoosh
 */

class sfChaosMonkey
{

	protected static $url_base;

	/**
	 * Set a URL base to prefix all requests.
	 * 
	 * @param string $url 		The URL base, including protocol
	 */
	public static function setURLBase($base)
	{
		self::$url_base = $base;
	}

}

?>