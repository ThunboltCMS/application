<?php

namespace Thunbolt\Application\ShortCuts;

trait TPresenter {

	/** @var array */
	private $shortDestinations;

	/**
	 * @param ShortCuts $shortCuts
	 */
	public function injectShortDestinations(ShortCuts $shortCuts) {
		$this->shortDestinations = $shortCuts->getShortcuts();
	}

	protected function createRequest($component, $destination, array $args, $mode) {
		$pos = strrpos($this->getName(), ':');
		$presenter = substr($this->getName(), $pos === FALSE ? 0 : $pos + 1);
		// Short tags
		$destinations = $this->shortDestinations + [
			'current' => $presenter . ':'
		];

		if (is_scalar($destination) && isset($destinations[$destination])) {
			$destination = $destinations[$destination];
			$component = $component->getPresenter();
		}

		return parent::createRequest($component, $destination, $args, $mode);
	}

}
