<?php
/**
 * Versioning tool for MySQL.
 *
 * This works by keeping a log of database changescripts within a filesystem directory.
 * Each file within the directory is named according to the timestamp of when the script was
 * submitted. sfDbVersioning::checkout() will scan the directory, compare the latest available
 * version with the one stored in the database, and execute any missing ones. It will iteratively
 * scan from the first file and catch any in-between changescripts, and execute them all in order.
 * A full checkout, with synchronized directories, should ensure a stable database.
 *
 * A danger does exist from out-of-sync directories, leading to multiple changescripts being
 * generated by different developers, each of which attempts to do the same modifications. For this
 * reason, please ensure that a recent database backup is always available. Additionally,
 * sfDbVersioning will issue a report on every checkout execution. If a script fails to execute,
 * it will be clearly included in the report.
 *
 * Note: checkouts are memory- and processor-intensive. They are best executed from within a web
 * interface (e.g. Poof) or from the console.
 *
 * Security warning: sfDbVersion does NOT do any database sanitizing. It expects clean SQL
 * in the versioning directory and in each submit.
 * 
 * @author Rizqi Djamaluddin
 * @version 1.DEI.20
 */

class sfDbVersioning {
	
	public static $directory;
	
	/**
	 * Set a directory for versioning information.
	 *
	 * @param string $directory 	The directory in which to keep versioning information.
	 */
	public static function setDirectory($directory)
	{
		static::$directory = new fDirectory($directory);
		return;
	}
	
	/**
	 * Obtain the current database version.
	 */
	public static function getCurrentVersion()
	{
		$query = sfCore::$db->query("SELECT `value` FROM `swoosh_db_versioning_meta` WHERE `key`='current_version' LIMIT 1");
		return $query->fetchScalar();
	}
	
	/**
	 * Check database version and bring it up to date if not currently so.
	 *
	 * @return stdClass 		A report object containing diagnostics of the current operation
	 */
	public static function checkout()
	{
		// pull all database changelog versions
		$versions = static::$directory->scan('/([0-9]*).sql/');
		
		$result = sfCore::$db->query("SELECT `version` FROM `swoosh_db_versioning_history`");
		$registered_versions = Array();
		foreach($result as $history_row)
		{
			$registered_versions[] = $history_row['version'];
		}
		
		$current_version = static::getCurrentVersion();
		sfCore::$db->query("UPDATE `swoosh_db_versioning_meta` WHERE `key`='fallback_version' SET `value`=%s LIMIT 1", $current_version);
		
		// fDirectory automatically orders files in natural sort order, so this always goes from
		// oldest to newest.
		foreach($versions as $version)
		{
			$filename = $version->getName(true);
			// only execute if no history of this file was found
			if(!in_array($filename, $registered_versions)){
				$sql = $version->getContents();
				try{
					// attempt to execute SQL as requested
					sfCore::$db->query($sql);
					// log execution
					sfCore::$db->query("INSERT INTO `swoosh_db_versioning_history` (`version`, `timetsamp`) VALUES (%s, %i)", $filename, time());
				}catch(Exception $e){
					// TODO: catch exceptions
					sfCore::$db->query("UPDATE `swoosh_db_versioning_meta` WHERE `key`='last_exception_version' SET `value`=%s LIMIT 1", $filename);
				}
			}
		}
		
		// end of process
	}
	
	/**
	 * Add a new changescript to the end of the database, and execute it.
	 *
	 * Use this after preparing a new changescript, but before executing it to the live database.
	 *
	 */
	public static function append()
	{
		
	}
}

?>