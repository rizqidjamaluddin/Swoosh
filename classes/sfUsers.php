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

	public static $levels;

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

		$errors = Array();

		if(strlen($username) < '1'){
			$errors['username'] = sfInvalidException::TOO_SHORT;
		}
		if(strlen($email) < '1'){
			$errors['email'] = sfInvalidException::TOO_SHORT;
		}
		if(strlen($password) < '5'){
			$errors['password'] = sfInvalidException::TOO_SHORT;
		}

		if(strlen($username) > '199'){
			$errors['username'] = sfInvalidException::TOO_LONG;
		}
		if(strlen($email) > '199'){
			$errors['email'] = sfInvalidException::TOO_LONG;
		}
		if(strlen($password) > '199'){
			$errors['password'] = sfInvalidException::TOO_LONG;
		}

		if(sfCore::$db->query("SELECT count(*) FROM `swoosh_users` WHERE `username`=%s LIMIT 1", $username)->fetchScalar()){
			$errors['username'] = sfInvalidException::EXISTING;
		}
		if(sfCore::$db->query("SELECT count(*) FROM `swoosh_users` WHERE `email`=%s LIMIT 1", $email)->fetchScalar()){
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
		} while ( static::keyExists($key) );

		$new_post = sfCore::$db->query("INSERT INTO `swoosh_users` (
			`id`, `username`, `password`, `email`, `level`, `key`)
			VALUES (
				NULL, %s, %s, %s, NULL, %s)",
			$username,
			sfBcrypt::hash($password),
			$email,
			$key
			);

		$obj = sfCore::make('sfUser');
		$obj->loadByUsername($username);
		return $obj;
	}

	/**
	 * Fetch a user by ID.
	 * 
	 * @throws sfNotFoundException	When no user matching $id is found
	 * 
	 * @param integer $id			A user ID.
	 * @return sfUser 				The User object.
	 */
	public static function fetchUser($id)
	{
		$obj = sfCore::make('sfUser');
		$obj->load($id);
		return $obj;
	}

	/**
	 * Fetch a user by username.
	 * 
	 * @throws sfNotFoundException	When no user matching $username is found
	 * 
	 * @param string $username		A username.
	 * @return sfUser 				The User object.
	 */
	public static function fetchUserByUsername($username)
	{
		$obj = sfCore::make('sfUser');
		return $obj->loadByUsername($username);
	}

	/**
	 * Fetch a user by email.
	 * 
	 * @throws sfNotFoundException	When no user matching $email is found
	 * 
	 * @param string $email			A user email address.
	 * @return sfUser 				The User object.
	 */
	public static function fetchUserByEmail($email)
	{
		$obj = fCore::make('sfUser');
		 return $obj->loadByQuery(sfCore::$db->query("SELECT * FROM `swoosh_users` WHERE `email`=%s LIMIT 1", $email));
	}

	/**
	 * Check if a user with a particular key exists.
	 * 
	 * @param string $key 		The key to check
	 * @return boolean 			If this key exists
	 */
	public static function keyExists($key)
	{
		$query = sfCore::$db->query("SELECT count(*) FROM `swoosh_users` WHERE `key`=%s LIMIT 1", $key);
		return $query->fetchScalar();
	}


	/* --  High-level functions  -- */

	/*
		I hook into flourish's built-in fAuthorization and fSession classes for these functions.
		They're designed to extend them, not replace them.
	*/

	protected static $current_user;
	protected static $current_user_object;
	protected static $logged_in;

	/**
	 * Attempt to login, and register through fAuthorization when successful.
	 * 
	 * @throws sfNotFoundException		When no user by provided username exists
	 * @throws sfBadPasswordException	When the given password fails to match
	 * 
	 * @param string $username 			Username for attempted login
	 * @param string $password 			Provided password to match
	 * @return boolean 					True when successful
	 */
	public static function login($username, $password)
	{
		$login_attempt = sfCore::make('sfUser');

		// will throw sfNotFoundException if not available
		$login_attempt->loadByUsername($username);

		if(!$login_attempt->matchPassword($password)){
			throw new sfBadPasswordException();
			return;
		}

		fAuthorization::setUserAuthLevel($login_attempt->getLevel());
		fAuthorization::setUserToken($username);

		static::evaluateSession();
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
		static::$logged_in = false;
		return true;
	}

	/**
	 * Master function to evaluate current user's login-ish-ness.
	 */
	public static function evaluateSession()
	{
		if(!static::isLoggedIn()){ return; }
		static::$logged_in = true;
		static::$current_user = fAuthorization::getUserToken();
	}

	/**
	 * Check if user is logged in. Uses own static variable, not fAuthorization.
	 * 
	 * @return boolean		True when the user is logged in
	 */
	public static function isLoggedIn()
	{
		return fAuthorization::checkLoggedIn();
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
	 * Get current user's username.
	 * 
	 * @return string 		This user's username
	 */
	public static function getCurrentUsername()
	{
		static::evaluateSession();
		return static::$current_user;
	}

	/**
	 * Get current user object.
	 * 
	 * @return sfUser 		The current user's object, for manual access; returns false if not logged in
	 */
	public static function getCurrentUser()
	{
		if(!static::isLoggedIn()) { return false; }
		static::evaluateSession();
		if(isset(static::$current_user_object)){
			return static::$current_user_object();
		}else{
			static::$current_user_object = sfCore::make('sfUser');
			static::$current_user_object->loadByUsername(static::$current_user);
			return static::$current_user_object;
		}
	}

}

class sfUser {

	protected $id;
	protected $username;
	protected $email;
	protected $level;

	/**
	 * These are secure variables that should only be loaded when needed.
	 * In total, they consume 200 bytes of data.
	 */
	protected $key;
	protected $password;

	/**
	 * Basic load function based on ID
	 */
	public function load($id)
	{
		$this->loadFromQuery(sfCore::$db->query("SELECT `id`,`username`,`email`,`level` FROM `swoosh_users` WHERE `id`=%i LIMIT 1", $id));
		return $this;
	}

	public function loadByUsername($username)
	{
		$this->loadFromQuery(sfCore::$db->query("SELECT `id`,`username`,`email`,`level` FROM `swoosh_users` WHERE `username`=%s LIMIT 1", $username));
		return $this;
	}

	public function loadFromQuery(fResult $result)
	{
		try{
			$result->tossIfNoRows();
		}catch(fNoRowsException $e){
			throw new sfNotFoundException();
		}
		$result = $result->asObjects();
		$this->loadFromObject($result->fetchRow());
		return $this;
	}

	public function loadFromObject(stdClass $object)
	{
		$this->id = $object->id;
		$this->username = $object->username;
		$this->email = $object->email;
		$this->level = $object->level;
		return $this;
	}

	/**
	 * Various getters and setters for built-in user parameters.
	 */

	public function getId()
	{
		return $this->id;
	}

	public function getUsername()
	{
		return $this->username;
	}

	public function getLevel()
	{
		$sfUsers = sfCore::getClass('sfUsers');
		return $sfUsers::translateAuthLevelInteger($this->level);
	}

	/**
	 * Sets a user's level.
	 * 
	 * @param string $level 	The desired user level
	 * @return string 			Same as input
	 */
	public function setLevel($level)
	{	
		$sfUsers = sfCore::getClass('sfUsers');
		sfCore::$db->query("UPDATE `swoosh_users` SET `level`=%i WHERE `id`=%i",
			$sfUsers::translateAuthLevelString($level), $this->id);
		return $level;

	}

	/**
	 * Load a user's key and password hash in. Lazy-loaded to avoid unnecessary data
	 * being stores when not in use.
	 */
	public function loadPrivateData()
	{
		if(isset($this->key)){ return false; }
		$result = sfCore::$db->query("SELECT `password`, `key` FROM `swoosh_users` WHERE `id`=%i LIMIT 1", $this->id)
			->asObjects();
		$object = $result->fetchRow();
		$this->password = $object->password;
		$this->key = $object->key;
	}

	/**
	 * Get a user's secret key.
	 * 
	 * TODO: lazy load keys
	 * 
	 * @return string 		128-character secret key
	 */
	public function getKey()
	{
		$this->loadPrivateData();
		return $this->key;
	}

	/**
	 * Generate a new random key for this user.
	 * 
	 * @return string 		128-character generated key
	 */
	public function generateKey()
	{
		$sfUsers = sfCore::getClass('sfUsers');
		do {
			$key = fCryptography::randomString(128);
		} while ( $sfUsers::keyExists() );

		sfCore::$db->query("UPDATE `swoosh_users` SET `key`=%s WHERE `id`=%i",
			$key, $this->id);
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
		$this->loadPrivateData();
		return $key == $this->key;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function setEmail($email_address)
	{
		sfCore::$db->query("UPDATE `swoosh_users` SET `email`=%s WHERE `id`=%i",
			$email_address, $this->id);
	}


	/**
	 * Matches a given password with the one stored for this user.
	 * 
	 * @param string $passowrd	The password to match against
	 * @return boolean 			If the password matches the user
	 */
	public function matchPassword($password)
	{
		$this->loadPrivateData();
		return sfBcrypt::check($password, $this->password);
	}

	/**
	 * Sets a user's password.
	 * 
	 * @param string $password 	The new password to set
	 * @return boolean 			Always true
	 */
	public function setPassword($password)
	{
		sfCore::$db->query("UPDATE `swoosh_users` SET `password`=%s WHERE `id`=%i",
			sfBcrypt::hash($password), $this->id);
		return true;
	}

	public function __toString()
	{
		return $this->getId();
	}

}

/*
class sfUserData extends fActiveRecord
{
	protected function configure(){}
}

// other flourish hooks
fORM::mapClassToTable('sfUserData', 'swoosh_users');
*/
?>