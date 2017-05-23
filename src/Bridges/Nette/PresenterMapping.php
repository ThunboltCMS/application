<?php

declare(strict_types=1);

namespace Thunbolt\Application\Bridges\Nette;

use Thunbolt\Application\IPresenterMapping;

class PresenterMapping implements IPresenterMapping {

	/** @var array */
	private $params;

	/** @var string */
	private $module;

	public function __construct($module, array $params) {
		$this->params = $params;
		$this->module = $module;
	}

	public function format(array $parts): string {
		$mapping = $this->params;
		while ($part = array_shift($parts)) {
			$mapping[0] .= str_replace('*', $part, $mapping[$parts ? 1 : 2]);
		}

		return $mapping[0];
	}

	public function unformat(string $class): ?string {
		$mapping = str_replace(['\\', '*'], ['\\\\', '(\w+)'], $this->params);
		if (preg_match("#^\\\\?$mapping[0]((?:$mapping[1])*)$mapping[2]\\z#i", $class, $matches)) {
			return ($this->module ? $this->module . ':' : '') . preg_replace("#$mapping[1]#iA", '$1:', $matches[1]) . $matches[3];
		}

		return NULL;
	}

}
