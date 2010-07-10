<?php

namespace example\controllers; // <--- plugin's name for the namespace

class ExamplesController extends \lithium\action\Controller {

	public function view() {
		$path = func_get_args();

		if (empty($path)) {
			$path = array('home');
		}
		$this->render(join('/', $path));
	}
}

?>
