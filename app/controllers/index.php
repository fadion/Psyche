<?php
namespace FW\Controllers;
use FW\Core;
use FW\Models;

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

		$view = Core\Psyc::open('test.php');

		$view->title = 'Framework';
		$view->version = 'v1.0';
		$view->errors = $errors;
		$view->success = $success;

		$view->render();
	}

}