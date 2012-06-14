<?php
namespace FW\Controllers;
use \FW\Core;
use \FW\Models;

class Index
{

	public function action_index ()
	{
		$errors = null;
		$success = '';

		if (Core\Request::post()) {
			$inputs = Core\Request::all();
			$rules = array(
				'name, Your Name' => 'required',
				'email, Your Email' => 'required, email',
				'department' => 'required, in:Support;Marketing;Technical',
				'about, About Yourself' => 'between:20;200'
			);

			if (Core\Validator::run($inputs, $rules)) {
				$success = __('The form was submited successfully');
			} else {
				$errors = Core\Validator::errors();
			}
		}

		$view = Core\View::open('index');

		$view->title = 'Framework';
		$view->version = 'v1.0';
		$view->errors = $errors;
		$view->success = $success;

		$view->render();

		/*
		 * Second Method: template variables passed as second argument of the open() method 
		 *
		$view = Core\View::open('index', array(
			'title'=>'Framework', 'version'=>'v1.0', 'errors'=>$errors, 'success'=>$success
		))->render();
		*/
		
		/*
		 * Third Method: template variabled passed as array on the set() method.
		 * The set() method supports single template variables too, ex: $view->set('title', 'Framework');
		 *
		$view = Core\View::open('index');
		$view->set(array('title'=>'Framework', 'version'=>'v1.0', 'errors'=>$errors, 'success'=>$success));
		$view->render();
		*/
	}

}