<?php

$a = Tag::open('a')->href('https://github.com/fadion/Psyche')->html('click here')->attr('target', '_blank');
$span = Tag::open('span')->html('nothing');

$h1 = Tag::open('h1')->id('header')->html('Psyche PHP Framework');
$p = Tag::open('p')->_class('pgraph')->html("It's quite amazing. You should give it a go.");
$p = Tag::open('p')->_class('pgraph')->html("To follow it's development, ", $a->get());
$footer = Tag::open('footer')->html('Copyright ', $span->get());

/* Traversing

$next = $h1->next();
$next->html('replaced content');

$prev = $footer->prev();

$children = $footer->children();

$parent = $a->parent();

$siblings = $p->siblings();

*/

/* Searching

$f = Tag::find('h1');
$f = Tag::find('#header');
$f = Tag::find('.pgraph');
$f = Tag::find('p.pgraph');

$f = Tag::find('.pgraph')->all();
$f = Tag::find('.pgraph')->first();
$f = Tag::find('.pgraph')->last();
$f = Tag::find('.pgraph')->eq(1);
$f = Tag::find('.pgraph')->index(2);

$f = Tag::find('.pgraph')->each(function($e)
	{
		$e.html('replaced all');
	});

*/

echo Tag::render();

?>