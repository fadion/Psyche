<?php
namespace Psyche\Controllers;
use Psyche\Core;
use Psyche\Models;

class Index
{

	public function action_drill ()
	{
		/* ----------------------------- */
		/* Start a Page model with id=10 */
		$page = new Models\Pages(10);

		// Set fields
		$page->title = 'About Us';
		$page->date = date('Y-m-d');

		// Save them
		$page->save();

		/* ------------------ */
		/* Start a Page model */
		$page = new Models\Pages;

		$page->title = 'Contact Us';
		$page->slug = 'contact-us';
		$page->date = date('Y-m-d');

		$page->save();

		// After an insertion, the page ID is
		// retreived automagically and can be
		// used to update the row we just inserted

		$page->title = 'Please, Contact Us';

		// Saves modifications to the freshly inserted page
		$page->save();

		/* --------------  */
		/* Delete a page */
		$page = new Models\Pages(10);
		$page->trash();

		/* ------------------------------------- */
		/* Use a page method for automated tasks */
		$page = new Models\Pages(10);
		$page->update_me();
	}

}