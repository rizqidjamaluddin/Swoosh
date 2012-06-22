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
		$msg = "sfInvalidException was triggered for these parameters: \n";
		foreach($error_array as $key => $error){
			$msg .= $key . ": " . $error . "\n";
		}
		$this->message = $msg;
	}
}

class sfExpectedException extends fExpectedException{}
class sfDiagnosticsException extends sfExpectedException
{
	const MISSING = 'MISSING';
	const INVALID = 'INVALID';

	public $errors;

	public function __construct($error_array)
	{
		$this->errors = $error_array;
	}
	public function getErrors(){
		return $this->errors;
	}
}

class sfBadPasswordException extends Exception {}
class sfProgrammerException extends fPRogrammerException {}
class sfAuthorizationException extends fAuthorizationException {}
class sfInvalidAuthException extends sfAuthorizationException {}
class sfBycryptException extends sfAuthorizationException {}

class sfNotFoundException extends fNotFoundException{}

?>