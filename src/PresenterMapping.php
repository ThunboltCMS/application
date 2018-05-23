<?php

declare(strict_types=1);

namespace Thunbolt\Application;

class PresenterMapping implements IPresenterMapping {

	/** @var string */
	protected const CLASS_REGEX = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

	/** @var string */
	protected $module;

	/**
	 * @param string $module
	 */
	public function __construct(string $module) {
		$this->module = $module;
	}

	/**
	 * @param array $parts
	 * @return string
	 */
	public function format(array $parts): string {
		// Front:Homepage
		return "{$this->module}Bundle\\Presenters\\$parts[0]Presenter";
	}

	/**
	 * @param string $class
	 * @return string|null
	 */
	public function unformat(string $class): ?string {
		// FrontBundle\Presenters\HomepagePresenter => Front:Basket
		if (preg_match('#^(' . self::CLASS_REGEX . ')Bundle\\\\Presenters\\\\(' . self::CLASS_REGEX . ')Presenter$#', $class, $matches)) {
			return $this->module . ':' . $matches[2];
		}

		return null;
	}

}
