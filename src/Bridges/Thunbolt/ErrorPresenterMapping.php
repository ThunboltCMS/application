<?php

namespace Thunbolt\Application\Bridges\Thunbolt;

use Thunbolt\Application\PresenterMapping;

class ErrorPresenterMapping extends PresenterMapping {

	public function format(array $parts) {
		return 'App\Presenters\ErrorPresenter';
	}

	public function unformat($class) {
		if ($class === 'App\Presenters\ErrorPresenter') {
			return 'Error:Error';
		}
	}

}
