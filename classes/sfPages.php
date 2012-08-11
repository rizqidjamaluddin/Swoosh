<?php
/**
 * An implementation of easy-to-edit pages.
 * 
 * This is one of the more CMS-like features. sfPages allows for retrieving page contents from the
 * database, as well as updating them. It includes very little magic functionality, instead being
 * a simple set-get feature.
 * 
 * There are 3 ways to get page contents:
 * get() - get raw page contents, with everything intact
 * encode() - passes through fHTML::encode(), will escape HTML
 * prepare() - passes through fHTML::prepare(), will encode special characters but allow HTML
 * 
 * sfPages caches the pages in both encoded and prepared form for faster rendering. Each is a
 * TEXT field. This does lead to slightly larger database space consumption.
 * 
 * @copyright Copyright Rizqi Djamaluddin
 * @author Rizqi Djamaluddin <rizqidjamaluddin@gmail.com>
 * @license MIT
 * 
 * @package Swoosh
 * 
 */

class sfPages
{
	// currently based on the TEXT MySQL field.
	// seriously, nobody should need more than 65k characters a page.
	protected const MAX_SIZE = 65000;

	/**
	 * Set a page's contents. Will also create a new page row if not yet accessible. This is a slow
	 * function - it is meant to be restricted for editing, not public use.
	 * 
	 * @param string $page 		A page identifier.
	 * @param string $contents 	This page's HTML content.
	 */
	public function set($page, $contents){
		// parse
		$encoded = fHTML::encode($contents);
		$prepared = fHTML::prepare($contents);

		// check size
		if(strlen($encoded) > static::MAX_SIZE || strlen($prepared) > static::MAX_SIZE){
			throw new sfInvalidException(Array('content' => sfInvalidException::TOO_LONG));
		}

		// send to database
		$check_exist = sfCore::$db->query("SELECT count(*) FROM `swoosh_pages` WHERE `id`=%s", $page)->fetchScalar();
		if($check_exist){
			$update = sfCore::$db->query("UPDATE `swoosh_pages` SET `raw_content`=%s, `encoded_content`=%s, `prepared_content`=%s WHERE `id`=%s LIMIT 1",
				$contents, $encoded, $prepared, $page);
		}else{
			$insert = sfCore::$db->query("INSERT INTO `swoosh_pages` (`id`, `raw_content`, `encoded_content`, `prepared_content`) VALUES (%s, %s, %s, %s)",
				$page, $contents, $encoded, $prepared);
		}
	}

	/**
	 * Get a page's raw contents, as it was provided to set().
	 * 
	 * @param string $page 		The page identifier
	 * @return string 			The page contents, unprocessed
	 * 
	 * @throws sfNotFoundException 	If no page by this identifier is found
	 */
	public function get($page){
		$query = sfCore::$db->query("SELECT `raw_content` FROM `swoosh_pages` WHERE `id`=%s LIMIT 1", $page);
		try{
			$query->tossIfNoRows();
		}catch(fNoRowsException $e){
			throw new sfNotFoundException();
		}

		return $query->fetchScalar();
	}

	// encoded and prepared variants.

	public function encode($page){
		$query = sfCore::$db->query("SELECT `encoded_content` FROM `swoosh_pages` WHERE `id`=%s LIMIT 1", $page);
		try{
			$query->tossIfNoRows();
		}catch(fNoRowsException $e){
			throw new sfNotFoundException();
		}
		return $query->fetchScalar();

	}

	public function prepare($page){
		$query = sfCore::$db->query("SELECT `prepared_content` FROM `swoosh_pages` WHERE `id`=%s LIMIT 1", $page);
		try{
			$query->tossIfNoRows();
		}catch(fNoRowsException $e){
			throw new sfNotFoundException();
		}
		return $query->fetchScalar();

	}

	/**
	 * List all available pages, usually for backend functionality.
	 * 
	 * @return array 		List of identifiers
	 */
	public function list(){
		$list = Array();
		$query = sfCore::$db->query("SELECT `id` FROM `swoosh_pages`");
		foreach($query as $page){
			array_push($list, $page);
		}
		return $list;
	}

	/**
	 * Delete a page's data entirely.
	 */
	public function delete($page){

	}

}