<?php
/**
 * Swoosh User Tools
 * 
 * A set of simple tools for everyday needs of usersystems.
 * 
 * @copyright Copyright Rizqi Djamaluddin
 * @author Rizqi Djamaluddin <rizqidjamaluddin@gmail.com>
 * @license MIT
 * 
 * @package Swoosh
 * 
 */

class sfUsers {

	public static $db;
	private static $hooked = false;
	public static $levels;

	public static function hookDatabase(fDatabase $db)
	{
		self::$db = $db;
		self::$hooked = true;
	}

	/**
	 * Extendibility features here.
	 */
	private static $user_class = 'sfUser';
	public static function setUserClass($class)
	{
		if(!class_exists($class)){
			throw new fProgrammerException("Invalid user class set for sfUsers.");
		}
		if(!is_subclass_of($class, 'sfUser')){
			throw new fProgrammerException("User class for sfUsers must be a subclass of sfUser.");
		}
		self::$user_class = $class;
	}

	/**
	 * Set user level structure. Automatically does the same to fAuthorization.
	 * 
	 * @param array $levels 	An associative array, similar to what flourish would use.
	 */
	public static function setAuthLevels($levels)
	{
		self::$levels = $levels;
		fAuthorization::setAuthLevels($levels);
	}

	/**
	 * Translate an integer level into a string.
	 * 
	 * @throws sfInvalidAuthException 	When no user level for this integer exists
	 * 
	 * @param integer $level 	A user level in integer form.
	 * @return string 			The same level in string form.
	 */
	public static function translateAuthLevelInteger($level)
	{
		$keys = array_keys(self::$levels, $level);
		if(empty($keys)){
			throw new sfInvalidAuthException();
		}
		return $keys[0];
	}

	/**
	 * Translate an integer level from a string to an integer.
	 * Typically for use before storing it in a database.
	 * 
	 * @throws sfInvalidAuthException 	When no user level for this string exists
	 * 
	 * @param string $level 			A user level in string form
	 * @return integer 					The same level in integer form
	 */
	public static function translateAuthLevelString($level)
	{
		if(!array_key_exists($level, self::$levels)){
			throw new sfInvalidAuthException();
		}
		return self::$levels[$level];
	}


	/**
	 * Create a new User and register it directly to the database.
	 * 
	 * @throws sfInvalidException		When a user's data is unacceptable
	 * @throws fProgrammerException		When sfUsers isn't yet hooked to a database
	 * 
	 * @param string $username 			The desired username
	 * @param string $password 			The user's password
	 * @param string $email 			The user's email address
	 * @return sfUser 					A new sfUser object
	 */
	public static function createNewUser($username, $password, $email)
	{

		if(!self::$hooked){
			throw new fProgrammerException('sfUsers not yet hooked to a database.');
		}

		$errors = Array();
		if(fRecordSet::tally('sfUserData', array('username=' => $username))){
			$errors['username'] = sfInvalidException::EXISTING;
		}
		if(fRecordSet::tally('sfUserData', array('email=' => $email))){
			$errors['email'] = sfInvalidException::EXISTING;
		}
		if(!empty($errors)){
			throw new sfInvalidException($errors);
		}

		// key generation
		do {
			// lets be honest with ourselves. The possibility of a collision between
			// two 128-character alphanumeric strings would require, on average,
			// 10^200 generations. Higher chances exist of the world just exploding
			// for no reason what-so-ever. Still, best be safe. We'd want to survive
			// the apocalypse. That'd look great on our portfolio.
			$key = fCryptography::randomString(128);
		} while (fRecordSet::tally('sfUserData', array('key=' => $key)));

		$new_user_data = new sfUserData();
		$new_user_data->setUsername($username);
		$new_user_data->setPassword(sfBcrypt::hash($password));
		$new_user_data->setEmail($email);
		$new_user_data->setKey($key);
		$new_user_data->store();

		return new self::$user_class($new_user_data->getId());
	}

	/**
	 * Fetch a user by ID.
	 * 
	 * @throws fNotFoundException	When no user matching $id is found
	 * 
	 * @param integer $id			A user ID.
	 * @return sfUser 				The User object.
	 */
	public static function fetchUser($id)
	{
		return new self::$user_class($id);
	}

	/**
	 * Fetch a user by username.
	 * 
	 * @throws fNotFoundException	When no user matching $username is found
	 * 
	 * @param string $username		A username.
	 * @return sfUser 				The User object.
	 */
	public static function fetchUserByUsername($username)
	{
		return new self::$user_class(array('username' => $username));
	}

	/**
	 * Fetch a user by email.
	 * 
	 * @throws fNotFoundException	When no user matching $email is found
	 * 
	 * @param string $email			A user email address.
	 * @return sfUser 				The User object.
	 */
	public static function fetchUserByEmail($email)
	{
		return new self::$user_class(array('email' => $email));
	}


	/* --  High-level functions  -- */

	/*
		I hook into flourish's built-in fAuthorization and fSession classes for these functions.
		They're designed to extend them, not replace them.
	*/

	public static $current_user;
	private static $logged_in;

	/**
	 * Attempt to login, and register through fAuthorization when successful.
	 * 
	 * @throws fNotFoundException		When no user by provided username exists
	 * @throws sfBadPasswordException	When the given password fails to match
	 * 
	 * @param string $username 			Username for attempted login
	 * @param string $password 			Provided password to match
	 * @return boolean 					True when successful
	 */
	public static function login($username, $password)
	{
		try{
			$login_attempt = new sfUser(array('username' => $username));
		}catch(fNotFoundException $e){
			throw new fNotFoundException();
			return;
		}

		if(!$login_attempt->matchPassword($password)){
			throw new sfBadPasswordException();
			return;
		}

		self::$current_user = $login_attempt;
		self::$logged_in = true;
		fAuthorization::setUserAuthLevel(
			self::translateAuthLevelInteger(self::$current_user->getLevel()));
		fAuthorization::setUserToken($username);
		return true;
	}

	/**
	 * Logout and destroy user info. Piggybacking on fAuthorization.
	 * 
	 * @return boolean		Always true
	 */
	public static function logout()
	{
		fAuthorization::destroyUserInfo();
		self::$logged_in = false;
		return true;
	}

	/**
	 * Check if user is logged in. Uses own static variable, not fAuthorization.
	 * 
	 * @return boolean		True when the user is logged in
	 */
	public static function isLoggedIn()
	{
		return self::$logged_in;
	}

	/**
	 * Get user's authentication level, based on fAuthorization.
	 * 
	 * @return integer 		User's auth level
	 */
	public static function getUserAuthLevel()
	{
		return fAuthorization::getUserAuthLevel();
	}


	/**
	 * Get current user object.
	 * 
	 * @return sfUser 		The current user's object, for manual access
	 */
	public static function getCurrentUser()
	{
		return self::$current_user;
	}

}

class sfUser {

	// extendibility
	protected $sfClass = 'sfUsers';

	protected $main_user_data;

	// remember, onlyy primary keys accepted (or ID column)
	public function __construct($fActiveRecord_selector)
	{

		try{
			$main_user_data = new sfUserData($fActiveRecord_selector);
		}catch(exception $e){
			throw new fNotFoundException('No user matching this data was found.');
		}
		$this->main_user_data = $main_user_data;
	}

	/**
	 * Various getters and setters for built-in user parameters.
	 */

	public function getUsername()
	{
		return $this->main_user_data->getUsername();
	}

	public function getLevel()
	{
		return $sfClass::translateAuthLevelInteger($this->main_user_data->getLevel());
	}

	/**
	 * Sets a user's level.
	 * 
	 * @param string $level 	The desired user level
	 * @return string 			Same as input
	 */
	public function setLevel($level)
	{	
		$this->main_user_data->setLevel($sfClass::translateAuthLevelStribg($level));
		$this->main_user_data->store();
		return $level;

	}

	/**
	 * Get a user's secret key.
	 * 
	 * @return string 		128-character secret key
	 */
	public function getKey()
	{
		return $this->main_user_data->getKey();
	}

	/**
	 * Generate a new random key for this user.
	 * 
	 * @return string 		128-character generated key
	 */
	public function generateKey()
	{
		do {
			$key = fCryptography::randomString(128);
		} while (fRecordSet::tally('sfUserData', array('key=' => $key)));
		$this->main_user_data->setKey($key);
		$this->main_user_data->store();
		return $key;
	}

	/**
	 * Matches a given key with the stored key. Use for validating API requests,
	 * XSRF attacks, and side-authentication.
	 *
	 * @param string $key 		The key to test against
	 * @return boolean			If the key matches the stored one
	 */
	public function matchKey($key)
	{
		return $key == $this->main_user_data->getKey();
	}

	public function getEmail()
	{
		return $this->main_user_data->getEmail();
	}

	public function setEmail($email_address)
	{
		$this->main_user_data->setEmail($email_address);
		$this->main_user_data->store();
	}


	/**
	 * Matches a given password with the one stored for this user.
	 * 
	 * @param string $passowrd	The password to match against
	 * @return boolean 			If the password matches the user
	 */
	public function matchPassword($password)
	{
		return sfBcrypt::check($password, $this->main_user_data->getPassword());
	}

	/**
	 * Sets a user's password.
	 * 
	 * @param string $password 	The new password to set
	 * @return boolean 			Always true
	 */
	public function setPassword($password)
	{
		$this->main_user_data->setPassword(sfBcrypt::hash($password));
		$this->main_user_data->store();
		return true;
	}

}

class sfUserData extends fActiveRecord
{
	protected function configure(){}
}

// other flourish hooks
fORM::mapClassToTable('sfUserData', 'swoosh_users');

?>