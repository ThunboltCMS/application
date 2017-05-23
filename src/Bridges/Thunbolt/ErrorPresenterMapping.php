<?php

declare(strict_types=1);

namespace Thunbolt\Application\Bridges\Thunbolt;

use Thunbolt\Application\PresenterMapping;

class ErrorPresenterMapping extends PresenterMapping {

	public function format(array $parts): string {
		return 'App\Presenters\ErrorPresenter';
	}

	public function unformat(string $class): ?string {
		if ($class === 'App\Presenters\ErrorPresenter') {
			return 'Error:Error';
		}

		return NULL;
	}

}
