<?php

namespace Thunbolt\Application;

use Nette;

class PresenterFactory implements Nette\Application\IPresenterFactory {

	use Nette\SmartObject;

	/** @var IPresenterMapping[] */
	private $mapping = [];

	/** @var array */
	private $cache = [];

	/** @var callable */
	private $factory;

	/**
	 * @param callable $factory function (string $class): IPresenter
	 * @param array $mapping
	 */
	public function __construct(callable $factory = NULL, array $mapping = []) {
		$this->factory = $factory ?: function ($class) {
			return new $class;
		};
		$this->setMapping($mapping);
	}

	/**
	 * Creates new presenter instance.
	 * @param string $name presenter name
	 * @return Nette\Application\IPresenter
	 */
	public function createPresenter($name) {
		return call_user_func($this->factory, $this->getPresenterClass($name));
	}

	/**
	 * Generates and checks presenter class name.
	 * @param string $name presenter name
	 * @return string class name
	 * @throws Nette\Application\InvalidPresenterException
	 */
	public function getPresenterClass(& $name) {
		if (isset($this->cache[$name])) {
			return $this->cache[$name];
		}

		if (!is_string($name) || !Nette\Utils\Strings::match($name, '#^[a-zA-Z\x7f-\xff][a-zA-Z0-9\x7f-\xff:]*\z#')) {
			throw new Nette\Application\InvalidPresenterException("Presenter name must be alphanumeric string, '$name' is invalid.");
		}

		$class = $this->formatPresenterClass($name);
		if (!class_exists($class)) {
			throw new Nette\Application\InvalidPresenterException("Cannot load presenter '$name', class '$class' was not found.");
		}

		$reflection = new \ReflectionClass($class);
		$class = $reflection->getName();

		if (!$reflection->implementsInterface(Nette\Application\IPresenter::class)) {
			throw new Nette\Application\InvalidPresenterException(
				"Cannot load presenter '$name', class '$class' is not Nette\\Application\\IPresenter implementor."
			);
		} elseif ($reflection->isAbstract()) {
			throw new Nette\Application\InvalidPresenterException("Cannot load presenter '$name', class '$class' is abstract.");
		}

		$this->cache[$name] = $class;

		if ($name !== ($realName = $this->unformatPresenterClass($class))) {
			trigger_error("Case mismatch on presenter name '$name', correct name is '$realName'.", E_USER_WARNING);
			$name = $realName;
		}

		return $class;
	}

	/**
	 * Sets mapping as pairs [module => IPresenterMapping]
	 * @param array $mapping
	 * @throws PresenterFactoryException
	 */
	public function setMapping(array $mapping) {
		foreach ($mapping as $module => $object) {
			if (!is_object($object)) {
				throw new PresenterFactoryException("Module '$module' must have object as mapping.");
			}
			if (!$object instanceof IPresenterMapping) {
				throw PresenterFactoryException::invalidMapping($object);
			}
			if (!preg_match('#^[A-Z][a-z]+$#', $module)) {
				throw new PresenterFactoryException("Module name '$module' is not valid.");
			}

			$this->mapping[$module] = $object;
		}
	}

	/**
	 * Formats presenter class name from its name.
	 * @param string $presenter
	 * @throws PresenterFactoryException
	 * @return string
	 * @internal
	 */
	public function formatPresenterClass($presenter) {
		$parts = explode(':', $presenter);
		$count = count($parts);
		if ($count < 2 || $count > 3) {
			throw new PresenterFactoryException("Invalid presenter path '$presenter'.");
		}
		$module = array_shift($parts);
		if (!isset($this->mapping[$module])) {
			throw new PresenterFactoryException("Presenter mapping for module '$module' not exists.");
		}

		return $this->mapping[$module]->format($parts);
	}

	/**
	 * Formats presenter name from class name.
	 * @param string $class
	 * @throws PresenterFactoryException
	 * @return string
	 * @internal
	 */
	public function unformatPresenterClass($class) {
		foreach ($this->mapping as $module => $mapping) {
			if ($unformated = $mapping->unformat($class)) {
				return $unformated;
			}
		}

		throw new PresenterFactoryException("Presenter '$class' cannot be converted to mapping.");
	}

}
