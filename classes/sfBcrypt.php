<?php
/**
 * Bcrypt implementation for advanced hashing.
 * 
 * Based on this gist [https://gist.github.com/1053158] by marcoarment <me@marco.org>. It also
 * introduces strict hashing (Bcrypt only) without the option to set a different hash handler.
 * 
 * The original script used openssl_random_pseudo_bytes. I decided to switch to fCryptography's
 * provided random string generator instead for integrity. The operation is also known to be
 * extremely slow on some Windows systems.
 * 
 * @author Rizqi Djamaluddin <rizqidjamaluddin@gmail.com>
 * 
 * @version 1
 * 
 * @package Swoosh
 */

class sfBcrypt
{
	private static $work_factor = 8;

	/**
	 * Set a work factor to use for hashing in this session. This only affects hash(), as
	 * check() will automatically parse the hash and use the correct work factor.
	 * 
	 * @param integer $work_factor 	The work factor to use
	 */
	public static function set_work_factor($work_factor)
	{
		self::$work_factor = $work_factor;
	}

	/**
	 * Hash a string for storage. Automatically generates a salt.
	 * 
	 * @param string $password 		The string to hash
	 * 
	 * @return string 				A crypt()-compatible string
	 */
	public static function hash($password, $work_factor = 0)
		{
		if (version_compare(PHP_VERSION, '5.3') < 0) throw new Exception('Bcrypt requires PHP 5.3 or above');

		if($work_factor < 4 || $work_factor > 31)
		{
			$work_factor = self::$work_factor;
		}

		// $salt = 
		// 	'$2a$' . str_pad($work_factor, 2, '0', STR_PAD_LEFT) . '$' .
		// 	substr(strtr(base64_encode(openssl_random_pseudo_bytes(16)), '+', '.'), 0, 22);

		$salt = 
			'$2a$' . str_pad($work_factor, 2, '0', STR_PAD_LEFT) . '$' .
			fCryptography::randomString(22);

		return crypt($password, $salt);
	}

	/**
	 * Match a hash with a provided string.
	 * 
	 * @param string $password 		The raw string to match against
	 * @param string $hash 			The sfBcrypt::hash() generated hash string
	 * 
	 * @return bool 				If the hashes match
	 */
	public static function check($password, $stored_hash)
		{
		if (version_compare(PHP_VERSION, '5.3') < 0) throw new Exception('Bcrypt requires PHP 5.3 or above');
		if(substr($stored_hash, 0, 4) != '$2a$')
		{
		throw new sfBycryptException('Provided hash is not a bcrypt hash.');
		}

		return crypt($password, $stored_hash) == $stored_hash;
	}

}

?>