<?php

declare(strict_types=1);

namespace Thunbolt\Application;

class PresenterMapping implements IPresenterMapping {

	/** @var string */
	protected static $classRegex = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

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
		// Front:Shop:Homepage
		return "$parts[0]Bundle\\{$this->module}Module\\$parts[1]Presenter";
	}

	/**
	 * @param string $class
	 * @return string|null
	 */
	public function unformat(string $class): ?string {
		// ShopBundle\FrontModule\BasketPresenter => Shop:Front:Basket
		if (preg_match('#^(' . self::$classRegex . ')Bundle\\\\' . $this->module . 'Module\\\\(' . self::$classRegex . ')Presenter$#', $class , $matches)) {
			return $this->module . ':' . $matches[1] . ':' . $matches[2];
		}

		return NULL;
	}

}
