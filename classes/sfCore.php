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

/**
 * @version 1
 */
class sfCore
{

	public static $strict = false;

	/**
	 * The class listing, for extendibility. Some of these are the non-static sub-classes used for
	 * granular control, to allow users to define what sub-class an implemented class should 
	 * generate. Others are fully-fledged classes, used when a class is referring to another.
	 */
	public static $classes = Array(
		'sfUsers' => 'sfUsers',
		'sfBlog' => 'sfBlog',

		'sfFileStorageItem' => 'sfFileStorageItem',
		'sfUser' => 'sfUser',
		'sfBlogPost' => 'sfBlogPost'
	);

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

	/**
	 * Make an object of the requested class.
	 * 
	 * This automatically generates an extended class if defined. Constructors should NOT
	 * have any arguments; instead, use a load() method to load basic properties.
	 * 
	 * Example:
	 * $newPost = sfCore::make('sfBlogPost');
	 * $newPost->load($id); // access the sfBlogPost class members.
	 * // this allows for...
	 * class myBlogPost extends sfBlogPost {}
	 * sfCore::extend('sfBlogPost', 'myBlogPost');
	 * // so any other script can do this
	 * $newPost = sfCore::make('sfBlogPost'); // will make a myBlogPost object instead
	 * $newPost->load($id); // invoke myBlogPost::load, if defined
	 * 
	 * @param string $class 	The class to make, will load extension if set
	 * @return mixed 			The created object
	 */
	public static function make($class)
	{
		$obj = self::$classes[$class];
		return new $obj();
	}

	/**
	 * Get a reference to a static class.
	 * 
	 * Similar to make(), except this doesn't create a new object. When any internal class
	 * wants to refer to another, it should use this instead of an explicit call. This allows
	 * users to extend behavior, and any calling classes would use those extensions instead.
	 * 
	 * Example:
	 * $sfUsers = sfCore::getClass('sfUsers')[]
	 * $sfUsers->fetchUser($id); // access sfUsers class members
	 * // this allows for...
	 * class myUserSystem extends sfUsers {}
	 * sfCore::extend('sfUsers', 'myUserSystem');
	 * // and thus, any other script that invokes this...
	 * $sfUsers = sfCore::getClass('sfUsers');
	 * $sfUsers->fetchUser($id); // ... will be calling myUserSystem::fetchUser instead!
	 * 
	 * This means myUserSystem can replace any functionality normally done through sfUsers, even
	 * calls from other scripts and within Swoosh.
	 * 
	 * @param string $class 	The static class to get
	 * @return string 			The class name
	 */
	public static function getClass($class)
	{
		return self::$classes[$class];
	}
}

require_once('sfExceptions.php');
require_once('sfSecurity.php');
require_once('sfUsers.php');
require_once('sfBlog.php');
require_once('sfPageCache.php');
require_once('sfBcrypt.php');


?>