<?php

namespace Thunbolt\Application;

interface IPresenterMapping {

	/**
	 * @param array $parts
	 * @return string
	 */
	public function format(array $parts);

	/**
	 * @param string $class
	 * @return string|null
	 */
	public function unformat($class);

}
