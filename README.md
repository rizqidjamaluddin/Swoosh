Swoosh
======

A PHP un-framework for developers who like doing things their way, built on Flourish.

Please note that Swoosh is currently in pre-pre-alpha. I don't even guarantee that it will run at
all. It's in a state of rapid development; ***I do not suggest using it in any production
projects yet.***

This is merely a snapshot to get an idea of what Swoosh can do, and if it's the right un-framework
for you. See also: [Flourish](http://flourishlib.com), the framework that Swoosh lives upon. If you
like Flourish, you may also like Swoosh!

## A Quick Intro

Swoosh is a PHP un-framework: **it's a collection of scripts and tools to help you get your work done
faster and safer**, *without having to adapt* to a library's personal style. It leaves nothing up
to "magic"; it gives you control.

Swoosh gives you the tools you need to make the basics of any website:

* Users - sign up, login, edit data, programmtically, safely
* A blog system - with comments, perfect for blogs and news
* Page caching - and you stay in control of how, where and when it works
* File storage - let your clients upload their files, serve downloads
* Photo storage - crop, watermark, resize, whatever your images

Of note: **Swoosh does not have any views or routers**. It is *not* a content management system. 
It simply gives you a programmatic way to do this *straight from your code*. You write your PHP
code as you like it, using any software and libraries you like - just, instead of writing a
database query, sanitizing it, managing sessions, and re-doing a user system all on your own, you 
call ``sfUsers::login()``.

## Database

Unlike Flourish, Swoosh **uses a built-in database**. This stores all the data tied to Swoosh
features. Installation will be made possible manually (through a SQL file), or, later, through
an automated mechanism.

## Requirements

Swoosh requires:
* [Flourish](http://flourishlib.com)
* PHP 5.3.0 or later
* MySQL is the only supported database driver

## Concepts

Swoosh is **aimed at developers**. It doesn't try to push you around. It also has all safeguards
off by default, unless turned on (``sfCore::enableStrictMode()``). Otherwise, Swoosh won't do
unnecessarily slow sanity checks, assuming that you, the developer, already tested it in secure
mode.

Originally a simple collection of scripts, Swoosh **covers what your average website might need**.
It isn't meant to be all-encompassing, and isn't meant to be minimalist. This also prevents Swoosh
from being completely modular; classes are constantly contacting each other, passing around data,
like how sfFileStorage automatically records the logged in user as the uploader.

Therefore, invoking Swoosh is merely as simple as including the core file, and doing configuration
commands. More on this later in complete documentation.

## Example

Swoosh is an "unframework" - it's all about giving you a foundation, not telling you to do it in
some way.

### A Blog

To make the front page, you'd usually make it this way...

```php
 <?php
 $posts = mysql_query("SELECT * FROM `blog_posts` LIMIT " . mysql_real_escape_string($_POST['page']).", 30");
 foreach( // ...
 ?>
```

In Swoosh:

```php
 <?php
 sfBlog::getPosts($_POST['page']);
 ?>
```

You can also...


```php
 <?php
 // do searches!
 $results = sfBlog::search($_POST['search']);
 // get a single post!
 $post = sfBlog::getSinglePost(56);
 // get post from a slug!
 $post = sfBlog::getPostFromSlug('banana');
 // work with post objects!
 $title = $post->getTitle();
 $time = $post->getTimestamp();
 // even comments!
 $comments = $post->getComments();
 // and work on those comments!
 foreach($comments as $comment){
 	$comment_body = $comment->getBody();
 }
 // generate RSS!
 $xml = sfBlog::generateRSS();
 ?>
```

Swoosh classes also tie into each other. For example, sfBlog can have comments made by sfUsers entries,
in which case they'll automatically hook up with that user's author data!

Note that **Swoosh is careful not to sacrifice performance for flexibility**. It doesn't have everything
built-in, and it doesn't try to be super-flexible at the cost for complexity. For example, blog posts
only have a title, (single) category, timestamp, author, post body, and an array of comments. I felt
that few enough use cases ever needed the old concepts of "tags", so I threw those out. 

Instead, since Swoosh is for developers, it's built with the idea that *developers can implement
changes on their own.* I conidered having some sort of "meta" field for blog posts, where you could
insert arbitrary field names and field values for pretty much any extra metadata, but that would just
make that column an ugly, unsearchable, unmaintainable, counter-productive hunk of data.

So if you want to, say, implement tags...

```php
<?php
class myCustomPost extends sfBlogPost {
	public static function setTags($tag_array){
		foreach($tag_array as $tag){
			// this uses the database Swoosh uses, it's a normal flourish fDatabase object.
			// of course, this is just an example. You'd want to remove any existing tags first.
			$insert = sfCore::$db->query("INSERT INTO `my_tags` (`parent`, `tag`) VALUES (%s, %s)", $this->id, $tag);
		}
	}
	public static function getTags(){
		// etc etc
	}
}
sfCore::extend('sfBlogPost', 'myCustomPost');

// everywhere in Swoosh will now use your class instead!
$post = sfBlog::getPostFromSlug('2012-vacation');
$tags = $post->getTags();

?>
```


Which leads me to... 


## Extending 

Yes, **Swoosh allows you to modify each and every aspect of what it does**. You can mold it to do
whatever you want it to.

Swoosh doesn't like the confabulated "hooks" model of "plug-ins." We're *developers* here - we
already have a way to extend functionality: we *extend* classes.

Simply extend a class as such:

```php
 <?php
 class myBlog extends sfBlog {
 	public static function makePost($params){
 		// do something before submitting the post to Swoosh
 		parent::makePost($params);
 		// parameters simplified
 	}
 }
 ?>
```

And later on in your code, simply call your own function instead of Swoosh's!

There's just one more step:

```php
 <?php
 sfCore::extend('sfBlog', 'myBlog');
 ?>
```

This tells Swoosh that you're extending the sfBlog class. This way, whenever any part of Swoosh
is trying to contact sfBlog, it'll contact your class instead. This also works for generated
classes, like sfBlogPost, so any classes making it will make your class, instead of Swoosh's.

Extending Swoosh is easy, providing you know what function to extend and where it lies. Swoosh
documentation is coming up soon - for now, there's a lot of documentation in the source!