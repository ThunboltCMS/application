<?php

declare(strict_types=1);

namespace Thunbolt\Application\Bridges\Thunbolt;

use Thunbolt\Application\PresenterMapping;

class ThunboltPresenterMapping extends PresenterMapping {

	/** @var string */
	private $defaultBundle;

	/** @var int */
	private $length;

	public function __construct(string $module, string $defaultBundle = 'AppBundle') {
		parent::__construct($module);

		$this->defaultBundle = $defaultBundle;
		$this->length = strlen($this->defaultBundle) + 1; // + \
	}

	public function format(array $parts): string {
		// Front:Homepage => AppBundle\FrontModule\HomepagePresenter
		if (count($parts) === 1) {
			return "{$this->defaultBundle}\\{$this->module}Module\\$parts[0]Presenter";
		}

 		return parent::format($parts);
	}

	public function unformat(string $class): ?string {
		// AppBundle\FrontModule\HomepagePresenter => Front:Homepage
		if (substr($class, 0, $this->length) === $this->defaultBundle . '\\') {
			if (preg_match('#' . $this->module . 'Module\\\\(' . self::CLASS_REGEX . ')Presenter$#', $class, $matches)) {
				return $this->module . ':' . $matches[1];
			}
		}

		return parent::unformat($class);
	}

}
