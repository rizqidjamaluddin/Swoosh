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
		static::$posts_per_page = $posts_per_page;
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
		
		$adjusted_page = $page - 1;
		$array = Array();
		$posts = sfCore::$db->query("SELECT * FROM `swoosh_blog_posts` LIMIT %i, %i;",
			$adjusted_page * static::$posts_per_page, static::$posts_per_page)->asObjects();	
		try{
			$posts->throwIfNoRows();
		}catch(fNoRowsException $e){
			throw new sfNotFoundException();
		}
		foreach($posts as $post)
		{
			$new = sfCore::make('sfBlogPost');
			$new->loadFromObject($post);
			$array[] = $new;
		}
		return $array;
	}

	/**
	 * Fetch a post based on its slug (permalink string).
	 * 
	 * @throws sfNotFoundException 		If no post with this slug is found
	 * 
	 * @param string $slug 				The requested post's slug
	 * @return sfBlogPost 				The blog post in object form
	 */
	public static function getSinglePostFromSlug($slug)
	{
		$post = sfCore::make('sfBlogPost');
		$post->loadFromQuery(sfCore::$db->query("SELECT * FROM `swoosh_blog_posts` WHERE `slug`=%s LIMIT 1", $slug));
		return $post;
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
	 * @throws sfNotFoundException 	If no posts were found
	 * 
	 * @param string $category 		Category to filter by
	 * @param integer $page 		Page to fetch
	 * @return array 				An array of posts.
	 */
	public static function getPostsByCategory($category, $page = 1)
	{
		$adjusted_page = $page - 1;
		$array = Array();
		$posts = sfCore::$db->query("SELECT * FROM `swoosh_blog_posts` WHERE `category`=%s LIMIT %i, %i;",
			$category, $adjusted_page * static::$posts_per_page, static::$posts_per_page)->asObjects();	
		try{
			$posts->throwIfNoRows();
		}catch(fNoRowsException $e){
			throw new sfNotFoundException();
		}
		foreach($posts as $post)
		{
			$new = sfCore::make('sfBlogPost');
			$new->loadFromObject($post);
			$array[] = $new;
		}
		return $array;
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
			`post_id`, `content`)
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
	protected $slug;

	protected $title;
	protected $author_id;
	protected $timestamp;
	protected $category;
	protected $comments_enabled;

	/**
	 * These are lazy-loaded upon request.
	 */
	protected $author;
	protected $body;

	protected $attributes = Array();

	/**
	 * Create a blog post object.
	 * 
	 * This automatically loads in all necessary data into the main protected variables.
	 * Any additional data can later be pulled in through attributes. Blog post bodies are
	 * NOT fetched automatically.
	 * 
	 * @throws sfNotFoundException		If no blog post with this ID is found
	 * 
	 * @param integer $id 				ID of blog post to fetch from the database
	 */
	public function load($id)
	{
		$this->loadFromQuery(sfCore::$db->query("SELECT * FROM `swoosh_blog_posts` WHERE `id`=%i LIMIT 1", $id));
		return $this;
	}
	
	/**
	 * Create a blog post object from a finished fDatabase query. This is the actual "core"
	 * load function; the load() method itself is simply a wrapper, as load functions are
	 * expected to accept an identifier.
	 *
	 * This function in and of itself is simply a wrapper to loadFromObject.
	 *
	 * @throws sfNotFoundException 		If no blog post is found as per the query result
	 *
	 * @param fResult $query_result 	Completed fDatabase query call
	 */
	public function loadFromQuery(fResult $query_result)
	{
		try{
			$query_result->throwIfNoRows();
		}catch(fNoRowsExcpetion $e){
			throw new sfNotFoundException();
		}
		$query_result = $query_result->asObjects();
		$data = $query_result->fetchRow();
		$this->loadFromObject($data);
		return $this;
	}
	
	/**
	 * Create a blog post from a raw stdClass object. Note that this does not actually ensure
	 * that a post by this data exists; it is up to a calling function to guarantee this.
	 *
	 * 
	 * @param stdClass $post_object 	Raw object containing details of this post
	 */
	public function loadFromObject(stdClass $post_object)
	{
		$this->id = $post_object->id;
		$this->title = $post_object->title;
		$this->author_id = $post_object->author_id;
		$this->timestamp = $post_object->timestamp;
		$this->category = $post_object->category;
		$this->comments_enabled = $post_object->comments_enabled;
		$this->slug = $post_object->slug;
		return $this;
	}

	/**
	 * Get comments associated with this post.
	 * 
	 * Instead of using sfBlogComment's native load function, this invokes the non-querying
	 * loadFromObject method. By passing a stdClass object, sfBlogComment doesn't need to send
	 * another query via the database.
	 *
	 * @return array 		An array of sfBlogComment objects
	 */
	public function getComments()
	{
		$comments = Array();
		$result = sfCore::$db->query("SELECT * FROM `swoosh_blog_comments` WHERE `post_id`=%i", $this->id)->asObjects();
		foreach($result as $comment)
		{
			$obj = sfCore::make('sfBlogComment');
			$obj->loadFromObject($comment);
			$comments[] = $obj;
		}
		return $comments;
	}
	
	/**
	 * The expected slew of getters and setters.
	 */
	public function getCategory()
	{
		return $this->category;
	}
	
	public function setCategory($category)
	{
		return sfCore::$db->query("UPDATE `swoosh_blog_posts` SET `category`=%s WHERE `id`=%i", $category, $this->id);
	}
	
	public function getTitle()
	{
		return $this->title;
	}
	
	public function setTitle($title)
	{
		return sfCore::$db->query("UPDATE `swoosh_blog_posts` SET `title`=%s WHERE `id`=%i", $title, $this->id);
	}
	
	public function getCommentsEnabled()
	{
		return $this->comments_enabled;
	}
	
	public function setCommentsEnabled($bool)
	{
		return sfCore::$db->query("UPDATE `swoosh_blog_posts` SET `comments_enabled`=%b WHERE `id`=%i", $bool, $this->id);
	}
	
	public function getTimetamp()
	{
		return $this->timestamp;
	}
	
	/**
	 * Get this post's body; will load body if it hasn't been requested.
	 */
	public function getBody()
	{
		if(!isset($this->body))
		{
			return $this->loadBody();
		}else{
			return $this->body;
		}
	}
	
	/**
	 * Directly loads this post's body. It will refresh it if called twice.
	 *
	 * @throws sfNotFoundException 	If this data is unavailable
	 *
	 * @return string 				The post body
	 */
	public function loadBody()
	{
		$result = sfCore::$db->query("SELECT `content` FROM `swoosh_blog_contents` WHERE `post_id`=%i", $this->id);
		try{
			$result->tossIfNoRows();
		}catch(fNoRowsException $e){
			throw new sfNotFoundException();
		}	
		$this->body = $result->fetchScalar();
		return $this->body;
	}
	
	public function setBody($body)
	{
		return sfCore::$db->query("UPDATE `swoosh_blog_contents` SET `content`=%s WHERE `post_id`=%i", $body, $this->id);
	}
	
	/**
	 * Get this post's author; will create a sfUser object if one hasn't been prepared.
	 */
	public function getAuthor()
	{
		if(!isset($this->author)){
			return $this->loadAuthor();
		}else{
			return $this->author;
		}
	}
	 
	/**
	 * Load and cache this post's author as a sfUser object.
	 *
	 */
	public function loadAuthor()
	{
		$sfUsers = sfCore::getClass('sfUsers');
		return $sfUsers::fetchUser($this->author_id);
	}
	
	
}

class sfBlogComment
{
	protected $id;
	protected $parent_id;
	protected $parent;

	protected $swoosh_user = false;
	protected $author_id;
	protected $timetamp;
	protected $body;

	protected $anonymous_name = NULL;
	protected $anonymous_email = NULL;

	protected $author = NULL;

	/**
	 * Create a blog comment object.
	 * 
	 * @param integer $comment_id 	ID of comment to fetch
	 */
	public function load($comment_id)
	{
		return $this->loadFromQuery(sfCore::$db->query("SELECT * FROM `swoosh_blog_comments` WHERE `id`=%i LIMIT 1", $comment_id));
	}

	public function loadFromQuery(fResult $result)
	{
		try{
			$result->throwIfNoRows();
		}catch(fNoRowsException $e){
			throw new sfNotFoundException();
		}
		$result = $result->asObjects();
		$this->loadFromObject($result->fetchRow());
		return $this;
	}
	
	/**
	 * Create a blog comment object based on an stdClass object.
	 *
	 * Note that this is an unvalidated comment; this function doesn't guarantee that this comment
	 * actually exists. 
	 *
	 * @param stdClass $comment_data 	The comment's raw data
	 */
	public function loadFromObject(stdClass $comment_data)
	{
		$this->id = $comment_data->comment_id;
		$this->parent_id = $comment_data->post_id;
		$this->timestamp = $comment_data->timestamp;

		if($comment_data->is_authorized){
			$this->$swoosh_user = true;
			$this->author_id = $comment_data->author_id;
		}else{
			$this->anonymous_name = $comment_data->anon_name;
			$this->anonymous_email = $comment_data->anon_email;
		}

		$this->body = $comment_data->body;

		return $this;
	}
	
	/**
	 * Check if a comment was posted anonymously or by a registered user.
	 *
	 * @return boolean 				If this comment was posted anonymously
	 */
	public function isAnonymous()
	{
		return !$this->$swoosh_user;
	}
	
	/**
	 * Load in user data, if a registered user
	 */
	public function loadAuthorData()
	{
		if(!$this->swoosh_user){ return false; }
		if(isset($this->author)){ return $this->author; }
		$this->author = sfCore::make('sfUser');
		$this->author->load($this->author_id);
		return $this->author;
	}
	
	/**
	 * Get this comment's author
	 *
	 * @return string 			Comment poster's name
	 */
	public function getAuthorName()
	{
		if($this->swoosh_user)
		{
			if($this->author === NULL){ $this->loadAuthorData(); }
			return $this->author->getUsername();
		}else{
			return $this->anonymous_name;
		}
	}
	
	
	/**
	 * Get this comment author's email
	 *
	 * I'm not completely sure that this has any practical purpose; one shouldn't display emails
	 * in plain text, anyway. Still, left in for completeness.
	 *
	 * @return string 			Comment poster's email
	 */
	public function getAuthorEmail()
	{
		if($this->swoosh_user)
		{
			if($this->author === NULL){ $this->loadAuthorData(); }
			return $this->author->getEmail();
		}else{
			return $this->anonymous_email;
		}
	}

	/**
	 * Lazy-loading of parent post, just for when it's necessary.
	 */
	public function loadParentPost()
	{
		if(isset($this->parent)){
			return $this->parent;
		}
		$this->parent = sfCore::make('sfBlogPost');
		$this->parent->load($this->parent_id);
		return $this->parent;
	}

	/**
	 * Get this comment's author, only if it was made by a registered user.
	 * 
	 * @return boolean, sfUser 		False if not registered
	 */
	public function getAuthor()
	{
		if($this->isAnonymous) return false;

	}

	public function getTimetamp()
	{
		return $thos->timestamp;
	}

	public function getBody()
	{
		return $this->body;
	}

	public function getParent()
	{
		return $this->loadParentPost();
	}

	/**
	 * Utilities
	 */
	public function isPostAuthor()
	{
		if($this->isAnonymous){ return false; }
		$this->loadParentPost();
		return $this->parent->getAuthor()->getId() == $this->author->getId();
	}


}

?>