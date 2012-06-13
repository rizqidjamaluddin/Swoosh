<?php
/**
 * A simple blog platform built around Flourish.
 * 
 * @copyright Copyright Rizqi Djamaluddin
 * @author Rizqi Djamaluddin <rizqidjamaluddin@gmail.com>
 * @license MIT
 * 
 * @package Swoosh
 * 
 */

class sfBlog
{

	protected static $posts_per_page = 5;
	/**
	 * Set the number of visible posts on each page.
	 * 
	 * @param integer $posts_per_page 	The number of posts to display on each page.
	 */
	public static function setPostsPerPage($posts_per_page)
	{
		self::$posts_per_page = $posts_per_page;
		return true;
	}

	/**
	 * Generate RSS feed with the accompanying headers.
	 * 
	 */
	public static function generateRSS()
	{

	}

	/**
	 * Search for posts in which the body contains the $term.
	 * 
	 * @param string $term 			Term to search for
	 * @return array 				An array of found posts
	 */
	public static function search($term)
	{

	}

	/**
	 * Search for posts based on a certain post attribute, such as 'author'.
	 * 
	 * @param string $attribute 	Attribute to search through
	 * @param string $term 			Term to search for
	 * @return array 				An array of found posts
	 */
	public static function searchByAttribute($attribute, $term)
	{

	}

	/**
	 * Fetch pages.
	 * 
	 * @param  string 	$posts_per_page Number of posts to displayer per page
	 * @param  string 	$page 			Page (in reverse chronological order) number
	 * @return array 					A structured array consisting of the requested posts
	 * 
	 */
	public static function getPosts($page = 1)
	{

	}
	
	/**
	 * Fetch a single post.
	 * 
	 * @param integer $post_id 			The requested post ID
	 * @return sfBlogPost 				The blog post in object form
	 */
	public static function getSinglePost($post_id)
	{
		$post = sfCore::make('sfBlogPost');
		$post->load($post_id);
		return $post;
	}

	/**
	 * Fetch pages per page based on a category.
	 * 
	 * @param string $category 		Category to filter by
	 * @param integer $page 		Page to fetch
	 * @return array 				An array of posts.
	 */
	public static function getPostsByCategory($category, $page = 1)
	{
		
	}

	/**
	 * Create a new blog post.
	 * 
	 * @throws sfInvalidException 	If a post with this slug already exists
	 * 
	 * @param string $post_slug 	The post slug (permalink)
	 * @param string $post_title 	The post title
	 * @param string $post_body 	The post body
	 * @param sfUser $post_author	The authot's sfUser (or derivative) object
	 * @param string $category 		An optional category for separating blog posts
	 * @return sfBlogPost 			The generated object
	 */
	public static function makePost($slug, $post_title, $post_body, sfUser $post_author, $category = NULL)
	{
		$slug_check = sfCore::$db->query("SELECT count(*) FROM `swossh_blog_posts` WHERE `slug` = %s LIMIT 1;");
		if($slug_check->fetchScalar() == 1)
		{
			throw new sfInvalidException(array('slug' => sfInvalidException::EXISTING));
		}
		$new_post = sfCore::$db->query("INSERT INTO `swoosh_blog_posts` (
			`post_id`, `title`, `author_id`, `timestamp`, `category`, `comments_enabled`, `slug`)
			VALUES (
				NULL, %s, %i, NOW(), %s, 1, %s)",
			$post_title,
			$post_author->getId(),
			$category,
			$slug
			);
		$post_body = sfCore::$db->query("INSERT INTO `swoosh_blog_contents` (
			`post_id`, `contents`)
			VALUES (%i, %s)",
			$new_post->getAutoIncrementedValue(),
			$post_body
			);
		$obj = sfCore::make('sfBlogPost');
		$obj->load($new_post->getAutoIncrementedValue());
		return $obj;
	}
}

class sfBlogPost
{
	/*
		Implementation notice: sfBlogPost(s) use raw SQL processed through fResult
		instead of using fORM features. This decision was made in the interest
		of efficiency, especially because blog posts are rapidly updated and accessed
		via RSS and syndication, as well as their tendency to go viral over other
		parts of a website.
	*/

	protected $id;

	protected $title;
	protected $author_id;
	protected $body;
	protected $timestamp;

	protected $attributes = Array();

	/**
	 * Create a blog post object.
	 * 
	 * This automatically loads in all necessary data into the main protected variables.
	 * Any additional data can later be pulled in through attributes.
	 * 
	 * @throws sfNotFoundException		If no blog post with this ID is found
	 * 
	 * @param integer $id 				ID of blog post to fetch from the database
	 */
	public function load($id)
	{

	}

	/**
	 * Get comments associated with this post.
	 * 
	 * @return array 		An array of sfBlogComment objects
	 */
	public function getComments()
	{
		$comments = Array();
		$result = sfCore::$db->query();
	}
}

class sfBlogComment
{
	protected $swoosh_user = false;
	protected $author;
	protected $timetamp;
	protected $email;
	protected $body;

	/**
	 * Create a blog comment object.
	 * 
	 * @param integer $comment_id 	ID of comment to fetch
	 */
	public function __construct($comment_id)
	{

	}

}

?>