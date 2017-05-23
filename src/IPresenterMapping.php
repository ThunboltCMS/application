<?php

declare(strict_types=1);

namespace Thunbolt\Application;

interface IPresenterMapping {

	/**
	 * @param array $parts
	 * @return string
	 */
	public function format(array $parts): string;

	/**
	 * @param string $class
	 * @return string|null
	 */
	public function unformat(string $class): ?string;

}
