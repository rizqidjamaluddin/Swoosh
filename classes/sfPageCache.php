<?php
/**
 * A wrapper around fCache (Flourish Library) to simplify page caching features.
 * 
 * It is basically a combination of forced-directory-based fCache, and fBuffer for generating
 * cached files.
 * 
 * @copyright Copyright Rizqi Djamaluddin
 * @author Rizqi Djamaluddin <rizqidjamaluddin@gmail.com>
 * @license MIT
 * 
 * @package Swoosh
 * 
 */

class sfPageCache
{
	/**
	 * Authorization override is a shortcut for disabling sfPageCache for authorized (logged-in)
	 * users. There is no magic behavior here; it simply disables all cache functions if
	 * fAuthorization says the user is logged in.
	 */
	protected static $authorized_override = false;

	protected static $enabled = false;
	protected static $cache;

	protected static $ttl = 21600;

	protected static $started = false;
	protected static $identifier;

	/**
	 * Set a directory to be used as the page cache. Also enables the cache system.
	 * 
	 * @param string $directory 	The directory in which to store the cache.
	 */
	public static function setDirectory($directory)
	{
		if(self::$authorized_override){ return false; }
		self::$cache = new fCache('directory', $directory);

		self::$enabled = true;
		return true;
	}

	/**
	 * Use a custom fCache object to serve caches. Any fCache object works, so if the directory
	 * cache isn't working well, developers should test different cache options and find one that
	 * works best.
	 * 
	 * @param fCache $cache 		The fCache object to serve as a cache
	 */
	public static function setCustomCache(fCache $cache)
	{
		if(static::$authorized_override){ return false; }
		static::$enabled = true;
		static::$cache = $cache;
	}

	/**
	 * Set how long a cache should be stored. The default (if not set by this function)
	 * is 6 hours (21600 seconds).
	 * 
	 * @param integer $ttl 	The Time To Live value in seconds
	 */
	public static function setTimeToLive($ttl)
	{
		self::$ttl = $ttl;
	}

	/**
	 * Force disable all cache functions if the session is logged in as per
	 * fAuthorization::checkLoggedIn();
	 */
	public static function disableForAuthorized()
	{
		if(fAuthorization::checkLoggedIn())
		{
			self::$authorized_override = true;
		}
	}

	/**
	 * Obtain the cached version of a certain identifier. By convention, this identifier is
	 * a URI.
	 * 
	 * IMPORTANT: since it's assumed that nothing else should be done after a cached file is
	 * displayed, this function simply exits after echoing it.
	 * 
	 * @param string $identifier  	An identifier string
	 * @return boolean 				False if not found
	 */
	public static function load($identifier)
	{
		if(self::$authorized_override){ return false; }
		$cached = self::$cache->get($identifier);
		if($cached){
			echo $cached;
			exit();
		}
		return false;
	}

	/**
	 * Start output buffering for saving into a new cache file.
	 * 
	 * @param string $identifier 	An identifier to be saved as, by convention a URI
	 * @return boolean 				If the creation was successful
	 */
	public static function create($identifier)
	{

		if(self::$authorized_override){ return false; }
		if(!self::$enabled){ return false; }

		self::$started = true;
		self::$identifier = $identifier;
		fBuffer::start();
		return true;
	}

	/**
	 * Stop output buffering and save the cache to the fCache directory, and then display
	 * it to the browser.
	 * 
	 * @return boolean 				If saving was successful
	 */
	public static function save()
	{
		if(self::$authorized_override){ return false; }
		if(!self::$enabled){ return false; }

		$contents = fBuffer::get();
		fBuffer::stop();
		self::$cache->set($identifier, $contents, self::$ttl);

		return true;
	}

	/**
	 * Delete a particular page cache. Useful, for example, when updating a page.
	 * 
	 * @param string $identifier 	The cache identifier for this particular page
	 */
	public static function delete($identifier)
	{
		if(!self::$enabled){ return false; }
		self::$cache->delete($identifier);
	}

	/**
	 * Clear the entire cache, starting anew.
	 */
	public static function clear()
	{
		self::$cache->clear();
		return true;
	}

}





?>