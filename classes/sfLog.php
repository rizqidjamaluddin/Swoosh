<?php
/**
 * A general-purpose logging class, integrated with other Swoosh classes.
 *
 * Logging is currently a planned feature for integration: Swoosh classes will automatically invoke
 * this functionality whenever a notable event is triggered (the notability is built into Swoosh,
 * although extensions can naturally customize this behavior). sfLogging can then mute or decide upon
 * a logging mechanism.
 *
 * Currently, sfLog only notes highly notable events such as:
 *  - [Planned] sfUsers login rate limiting alerts (potential security issue)
 *  - [Planned] sfDbVersioning checkouts
 *
 * Future sfLog versions will included expanded logging features such as automated emailing of logs
 * and automatic log management.
 *
 * @author Rizqi Djamaluddin
 */


class sfLog {

	protected static $session_opened = false;
	protected static $in_block = false;

	public static function print()
	{
	
	}

	public static function openBlock()
	{
	
	}
	
	public static function closeBlock()
	{
	
	}

	public static function openSession()
	{
		if(static::$session_opened){ return; }
		static::$session_opened = true;
	}
	
}

?>