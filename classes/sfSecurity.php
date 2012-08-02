<?php
/**
 * Various security implements for common websites.
 * 
 * Throttling - the ability to throttle client requests. This is useful for blocking brute-force
 * password attacks, spam, and denial of service attacks. It uses an fCache with a TTL set to how
 * long an event should be throttled.
 * 
 */

class sfSecurity
{

	/**
	 * Limit an event from executing more than once for every $ttl seconds. This will throw a
	 * sfThrottleException if the event is called again too soon.
	 * 
	 * Example:
	 * function addComment(){
	 * 		try{
	 * 			sfSecurity::throttle($username.'::addpost', 60); // tie event to user, not global
	 * 			// business code here
	 * 		}catch(sfThrottleException $e){
	 * 			echo "You can only comment once per minute.";
	 * 		}
	 * }
	 * 
	 * A fCache can be set via setThrottleCache. By default, it uses a built-in database table
	 * from Swoosh.
	 * 
	 * Depending on the cache type and how often throttling is used, it can be a resource-intensive
	 * action on its own, especially if many events are used. Faster caches lead to much better
	 * performance. Slow caches, especially ones that tax other valuable resources like the
	 * database, should be very selective in what to protect and how often they're invoked. For
	 * example, a database fCache uses 1 row per held-up event.
	 * 
	 * More on throttling:
	 * http://www.codinghorror.com/blog/2009/02/rate-limiting-and-velocity-checking.html
	 * 
	 * @param string $event 			Event identifier
	 * @param integer $ttl 				Seconds to throttle
	 * @throws sfThrottleException 		If event occurs under throttle limit
	 */
	public static function throttle($event, $ttl)
	{
		if(!isset(static::$throttle_cache)){
			static::setDefaultThrottleCache();
		}
		$check = static::$throttle_cache->get($event, NULL);
		if(isset($check)){
			throw new sfThrottleException($check);
		}
		static::$throttle_cache->set($event, time(), $ttl);
	}

	protected static $throttle_cache = NULL;
	public static function setThrottleCache($cache)
	{
		if(sfCore::$strict){
			if(!is_class($cache, 'fCache')){
				throw new sfProgrammerException('sfSecurity::throttle requires a fCache object.');
			}
		}
		static::$throttle_cache = $cache;
	}

	public static function setDefaultThrottleCache()
	{
		static::$throttle_cache = new fCache('database', sfCore::$db, array(
			'table' => 'swoosh_security_throttle',
			'key_column' => 'event',
			'value_column' => 'flag',
			'value_data_type' => 'string',
			'ttl_column' => 'expire'
		));
	}

}

?>