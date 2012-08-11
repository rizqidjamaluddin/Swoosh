# Swoosh Pages Tutorial

Swoosh provides a straightforward and simple way to store client-editable pages, without diving into the complexities of giving your client full reign over web pages.

This is actually a really important thing, because most clients don't actually need that full capacity to splatter around new pages to their will. It's an easy way to break a website. While you *could* do this if you wanted, Swoosh, by default, only provides bare-bones functionality.

So let's say your client want to edit their "About" page a lot. Traditionally, you'd set up a new database table and wire up a form to update that table, and then have the About page read from there. sfPages does exactly this, but without the hassle.

The first step, naturally, is for you to make a new form in your own control panel, and have this form execute some PHP code upon submit. Your client simply types the page contents in a text area, and then submits the form. The code simply summons sfPages:

```php
<?php
	sfPages::set('about', fRequest::get('about_textarea'));
?>
```

And… that's it. Honestly, I can't think of much else you can do. The only possible addition would be to catch for an sfInvalidException, which triggers if the page text is too long for the database field. That, however, has a limit of about 65kb of text, or 65 thousand characters. Your client likely has a bigger issue if they're submitting something that big.

Also note that sfPages doesn't require you to "add" a new page; any time you set a page ID, like 'about' in that example, it either adds a new row or updates the old one.

Next, you go to wherever you handle that About page content (i.e. where you'd usually just type the page in raw HTML). And instead of HTML, you ask sfPages to grab your content:

```php
<?php
	echo sfPages::prepare('about');
?>
```

Note that you have a choice between three functions here (I used 'prepare' above).
- get() will just summon the raw content, as it was saved through set(). This allows you to do further processing, like parsing for BBCode or Markdown.
- encode() will summon the content, but sterilised. Any HTML will be escaped, as if you used htmlentities() in PHP. Use this if your client has no idea what HTML is, and don't want them to break anything. Internally, it uses fHTML::encode().
- prepare() will summon the content and lets HTML tags through, but will encode other things, like HTML entities. Use this if you want to allow HTML (useful for your usual formatting tags), while making sure it's otherwise safe for browsers to consume. Internally, it uses fHTML::prepare().

sfPages has a few other useful functions like list(), which just pulls up a list of pages it has data for. You can use this in your control panel if you have many editable pages. There's also delete($id), which allows you to delete a particular page's content (you probably only need this if you accidentally assign an ID to set(), and don't want to visit the database).