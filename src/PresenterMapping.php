<?php

declare(strict_types=1);

namespace Thunbolt\Application;

class PresenterMapping implements IPresenterMapping {

	/** @var string */
	protected const CLASS_REGEX = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

	/** @var string */
	protected $module;

	/** @var string|null */
	protected $namespace;

	/**
	 * @param string $module
	 * @param string|null $namespace
	 */
	public function __construct(string $module, ?string $namespace = null) {
		$this->module = $module;
		$this->namespace = $namespace;

		if ($this->namespace) {
			$this->namespace = rtrim($this->namespace, '\\') . '\\';
		}
	}

	/**
	 * @param array $parts
	 * @return string
	 */
	public function format(array $parts): string {
		// Front:Homepage
		return "{$this->namespace}{$this->module}Bundle\\Presenters\\$parts[0]Presenter";
	}

	/**
	 * @param string $class
	 * @return string|null
	 */
	public function unformat(string $class): ?string {
		// FrontBundle\Presenters\HomepagePresenter => Front:Basket
		if (preg_match('#^' . preg_quote((string) $this->namespace) . preg_quote($this->module) . 'Bundle\\\\Presenters\\\\(' . self::CLASS_REGEX . ')Presenter$#', $class, $matches)) {
			return $this->module . ':' . $matches[1];
		}

		return null;
	}

}
