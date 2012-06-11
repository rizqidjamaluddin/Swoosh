<?php

function __autoload($class_name)
{
    // Customize this to your root Flourish directory
    $flourish_root = $_SERVER['DOCUMENT_ROOT'] . '/flourish/';
    
    $file = $flourish_root . $class_name . '.php';
 
    if (file_exists($file)) {
        include $file;
        return;
    }
    
    throw new Exception('The class ' . $class_name . ' could not be loaded');
}

class sfCore
{

	public static $strict = false;

	public static $db = NULL;
	public static function attach(fDatabase $db)
	{
		self::$db = $db;
	}

	/**
	 * Enable strict mode for development purposes. While in this mode, extra checks will be done
	 * that may throw sfProgrammerExceptions. These checks may unnecessarily bog down runtime
	 * speed during practical deployment, so leaving strict mode off can be useful.
	 */
	public static function enableStrictMode()
	{
		self::$strict = true;
	}

	/**
	 * Extend a built-in Swoosh class. Other functions will look here when generating new objects
	 * as outputs.
	 * 
	 * @param string $base_class 		The class to extend
	 * @param string $extension_class 	The user-defined class to extend with
	 */
	public static function extend($base_class, $extension_class)
	{
		if(self::$strict)
		{
			if(!class_exists($base_class) || !class_exists($extension_class))
			{
				throw new sfProgrammerException(
					"Classes for extension ($base_class, $extension_class) are missing.");
			}
			if(!is_subclass_of($extension_class, $base_class))
			{
				throw new sfProgrammerException(
					"$extension_class is not a subclass of $base_class.");
			}
		}
		self::$classes[$base_class] = $extension_class;
	}
}

require_once('sfExceptions.php');
require_once('sfUsers.php');
require_once('sfBlog.php');
require_once('sfPageCache.php');
require_once('sfBcrypt.php');


?>