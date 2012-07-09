<?php

$a = Tag::open('a')->href('https://github.com/fadion/Psyche')->html('click here')->attr('target', '_blank');
$span = Tag::open('span')->html('nothing');

$h1 = Tag::open('h1')->id('header')
					 ->attr('lang', 'en')
					 ->css("border:1px solid red; padding:5px;")
					 ->html('Psyche PHP Framework');

$p = Tag::open('p')->_class('para')->html("It's quite amazing. You should give it a go.");
$p = Tag::open('p')->_class('para')->html("It uses the best of PHP 5.3");
$p = Tag::open('p')->html("To follow it's development, ", $a->get());

$footer = Tag::open('footer')->html('Copyright ', $span->get());

/* Traversing ==================

$next = $h1->next();
$next->html('replaced content');

$prev = $footer->prev();

$children = $footer->children();

$parent = $a->parent();

$siblings = $p->siblings();

*/

/* Manipulating ================

$h1->html('content1', 'content2');
$h1->attr('title', 'nice header', 'lang', 'en');
$h1->attr(array('title' => 'nice header', 'lang' => 'en'));
$h1->id('my_id');
$h1->_class('some_class');
$h1->title('nice header');
$h1->css('border: 1px solid red;');

$a->href('somepage.html');
$img->src('myimage.png');

$h1->append('some content');
$h1->prepend('some other content');

$contents = $h1->contents();

*/

/*

/* Searching ===================

$f = Tag::find('h1');
$f = Tag::find('#header');
$f = Tag::find('.para');
$f = Tag::find('p.para');
$f = Tag::find('h1[lang="en"]');

$f = Tag::find('.para')->all();
$f = Tag::find('.para')->first();
$f = Tag::find('.para')->last();
$f = Tag::find('.para')->eq(1);
$f = Tag::find('.para')->index(2);

$f = Tag::find('p')->not('.para')->all();

$f = Tag::find('.para')->each(function($e)
	{
		$e.html('replaced');
	});
*/

echo Tag::render();

?>