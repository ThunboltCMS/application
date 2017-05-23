<?php

declare(strict_types=1);

namespace Thunbolt\Application;

class PresenterFactoryException extends \Exception {

	public static function invalidMapping($val): self {
		return new self(sprintf('Class %s must implements interface %s.', get_class($val), IPresenterMapping::class));
	}

}
