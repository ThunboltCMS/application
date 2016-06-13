<?php

namespace Thunbolt\Application\ShortCuts;

class ShortCuts {

	/** @var array */
	private $shortcuts = [];

	/**
	 * @param array $shortcuts
	 */
	public function __construct(array $shortcuts = []) {
		$this->shortcuts = $shortcuts;
	}

	/**
	 * @return array
	 */
	public function getShortcuts() {
		return $this->shortcuts;
	}

}
