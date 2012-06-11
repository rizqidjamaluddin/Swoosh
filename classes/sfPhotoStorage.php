<?php
/**
 * A simplified photo storage solution for websites.
 * 
 * @copyright Copyright Rizqi Djamaluddin
 * @author Rizqi Djamaluddin <rizqidjamaluddin@gmail.com>
 * @license MIT
 * 
 * @package Swoosh
 */

class sfPhotoStorage
{
	public static $directory = NULL;
	protected static $size_limit = '5MB';
	protected static $MIME_types = array(
		'image/gif',
		'image/jpeg',
		'image/png'
		);

		/**
	 * Set a directory to be used for photo storage.
	 * 
	 * @throws fProgrammerException 	If the directory cannot be written to
	 * 
	 * @param string $directory 		The directory for file storage, including trailing slash
	 */
	public static function setDirectory($directory)
	{
		self::$directory = new fDirectory($directory);
		if(!self::$diretory->isWritable()){
			throw new fProgrammerException("sfPhotoStorage cannot write to the specified directory.");
		}
	}


}

?>