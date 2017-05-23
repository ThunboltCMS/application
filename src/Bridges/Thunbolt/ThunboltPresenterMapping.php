<?php

namespace Thunbolt\Application\Bridges\Thunbolt;

use Thunbolt\Application\PresenterMapping;

class ThunboltPresenterMapping extends PresenterMapping {

	/** @var string */
	private $defaultBundle;

	/** @var int */
	private $length;

	public function __construct($module, $defaultBundle = 'AppBundle') {
		parent::__construct($module);
		$this->defaultBundle = $defaultBundle;
		$this->length = strlen($this->defaultBundle) + 1; // + \
	}

	public function format(array $parts) {
		// Front:Homepage => AppBundle\FrontModule\HomepagePresenter
		if (count($parts) === 1) {
			return "{$this->defaultBundle}\\{$this->module}Module\\$parts[0]Presenter";
		}

 		return parent::format($parts);
	}

	public function unformat($class) {
		// AppBundle\FrontModule\HomepagePresenter => Front:Homepage
		if (substr($class, 0, $this->length) === $this->defaultBundle . '\\') {
			if (preg_match('#' . $this->module . 'Module\\\\(' . self::$classRegex . ')Presenter$#', $class, $matches)) {
				return $this->module . ':' . $matches[1];
			}
		}

		parent::unformat($class);
	}

}
