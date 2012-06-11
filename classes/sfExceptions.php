<?php

class sfInvalidException extends Exception
{
	const TOO_SHORT		= 'TOO_SHORT';
	const TOO_LONG		= 'TOO_LONG';
	const REQUIRED		= 'REQUIRED';
	const EXISTING		= 'EXISTING';
	const BAD_FORMAT	= 'BAD_FORMAT';
	const BAD_PASSWORD	= 'BAD_PASSWORD';

	public $errors;

	public function __construct($error_array)
	{
		$this->errors = $error_array;
	}
}

class sfBadPasswordException extends Exception {}
class sfProgrammerException extends fPRogrammerException {}
class sfAuthorizationException extends fAuthorizationException {}
class sfInvalidAuthException extends sfAuthorizationException {}
class sfBycryptException extends sfAuthorizationException {}

class sfNotFoundException extends fNotFoundException{}

?>