<?php
/**
 * A simple file storage solution for websites.
 * 
 * @copyright Copyright Rizqi Djamaluddin
 * @author Rizqi Djamaluddin <rizqidjamaluddin@gmail.com>
 * @license MIT
 * 
 * @package Swoosh
 */

class sfFileStorage
{
	public static $directory = NULL;
	protected static $size_limit = '100MB';
	protected static $MIME_types = array(
		'image/gif',
		'image/jpeg',
		'image/png',
		'video/mp4',
		'video/quicktime',
		'application/pdf',
		'application/vnd.ms-excel',
		'application/vnd.ms-powerpoint',
		'application/msword',
		'application/zip',
		'application/x-tar',
		'application/x-rar-compressed',
		'application/x-compress',
		'application/x-gzip',
		'application/x-bzip2',
		'text/plain'
		);

	/**
	 * Set a directory to be used for file storage.
	 * 
	 * @throws fProgrammerException 	If the directory cannot be written to
	 * 
	 * @param string $directory 		The directory for file storage, including trailing slash
	 */
	public static function setDirectory($directory)
	{
		self::$directory = new fDirectory($directory);
		if(!self::$diretory->isWritable()){
			throw new fProgrammerException("sfFileStorage cannot write to the specified directory.");
		}
	}

	public static function getDirectory()
	{
		return self::$directory->getPath();
	}

	/**
	 * Upload a new file into storage. Extracts data from sfUsers as well.
	 * 
	 * @param string $field 			The form field name of the upload element
	 * @param mixed $auth_requirement	An auth level string or integer
	 * @return sfFileStorageItem 		The uploaded file
	 */
	public static function upload($field, $auth_requirement = 0)
	{
		if(!self::$directory)
		{
			throw new fProgrammerException("sfFileStorage needs a directory to save to.");
		}
		$upload = new fUpload();
		$upload->setMaxSize(self::$size_limit);
		$upload->setMIMETypes(self::$MIME_types);
		$file = $upload->move(self::$directory, $field);

		// attempt to define a non 0-100 integer auth level
		if(!is_int($auth_requirement) || $auth_requirement < 0 || $auth_requirement > 100)
		{
			$auth_requirement = sfUsers::translateAuthLevelString($auth_requirement);
		}

		$user = sfUser::getCurrentUser();
		if(!$user){
			$user = NULL;
		}

		// insert data to database
		$insert = sfCore::db->query(
			"INSERT INTO `swoosh_file_storage` (
				`id`, `filename`, `upload_date`, `upload_user`, `auth_requirement`, `downloads`) 
			VALUES (
				NULL, %s, NOW(), %i, %i, 0);",
			$file->getName(),
			$user,
			$auth_requirement
		);

		// return item
		$item = sfCore::make('sfFileStorageItem');
		$item->load($insert->getAutoIncrementedValue());
		return $item;
	}

	/**
	 * Set a maximum file size limit for uploads.
	 * 
	 * @param string $filesize 		The file size in a string for fUpload::setMaxSize()
	 */
	public static function setMaxSize($filesize)
	{
		self::$size_limit = $filesize;
	}


}

class sfFileStorageItem
{
	protected $fFile;
	protected $id;

	protected $downloads;
	protected $uploader;
	protected $date;

	protected $auth_requirement;

	/**
	 * Accept a file ID.
	 * 
	 * @throws fNotFoundException 	If the item is not found in the database
	 * 
	 * @param integer $id 			The database ID of this item
	 */
	public function load($id)
	{
		$search = sfCore::db->query("SELECT * FROM `swoosh_file_storage` WHERE `id`='%i' LIMIT 1", $id);
		try{
			$search->tossIfNoRows();
		}catch(fNoRowsException $e){
			throw new fNotFoundException();
		}
		$data = $search->fetchRow();

		$this->fFile = new fFile(self::$directory->getPath() . $data['filename']);
		$this->id = $id;
		$this->downloads = $data['downloads'];
		$this->uploader = $data['upload_user'];
		$this->date = $data['upload_date'];
		$this->auth_requirement = $data['auth_requirement'];
	}

	/**
	 * Offer a download to the browser using the correct headers and best practices.
	 * 
	 * @throws sfAuthorizationException 	If the user lacks the required auth requirement
	 */
	public function download()
	{
		if($this->auth_requirement != 0)
		{
			if(sfUsers::translateAuthLevelString(sfUsers::getUserAuthLevel()) < $this->auth_requirement)
			{
				throw new sfAuthorizationException();
			}
		}

		$update = sfCore::db->query("UPDATE `swoosh_file_storage` SET `downloads` = `downloads`+1 WHERE `id` = '%i' LIMIT 1;",
			$this->id);
		fSession::close();
		$this->fFile->output(true, true);
	}

	/**
	 * Get number of downloads for this file.
	 * 
	 * @return integer 	Number of downloads
	 */
	public function getDownloadCount()
	{
		return $this->downloads;
	}

	/**
	 * Get the user who uploaded this file.
	 * 
	 * @return sfUser 	The uploader user object
	 */
	public function getUploader()
	{
		return sfUsers::fetchUser($this->uploader);
	}

	/**
	 * Get the timestamp of when this file was uploaded.
	 * 
	 * Internally, flourish attemps to convert these into fTimestamp items and then returns them as a formatted
	 * string. Swoosh simply returns the raw fTimetamp item to avoid excessive converting; they can also be
	 * convereted straight into strings (__toString()) either way.
	 * 
	 * @return fTimetamp 	A representation of the upload date
	 */
	public function getUploadDate()
	{
		return new fTimetamp($this->date);
	}

	/**
	 * Get the minimum auth level required for this item.
	 * 
	 * @return string  	The required auth level
	 */
	public function getAuthRequirement()
	{
		return sfUsers::translateAuthLevelInteger($this->auth_requirement);
	}

	/**
	 * Set the minimum auth level required for this item.
	 * 
	 * @param string $auth 	The minimum auth level string, as previously set through sfUsers
	 */
	public function setAuthRequirement($auth)
	{
		$auth_requirement = sfUsers::translateAuthLevelString($auth);
		sfCore::db->query("UPDATE `swoosh_file_storage` SET `auth_requirement`='%i' WHERE `id` = '%i' LIMIT 1;",
			$auth_requirement,
			$this->id);
		return $this;
	}


	/**
	 * Delete a file from file storage. This is a permanent action.
	 */
	public function delete()
	{
		sfCore::db->query("DELETE FROM `swoosh_file_storage` WHERE `id`='%i';", $this->id);
		$this->fFile->delete();
	}
}


?>