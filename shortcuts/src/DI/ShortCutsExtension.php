<?php

namespace Thunbolt\Application\DI;

use Nette\DI\CompilerExtension;

class ShortCutsExtension extends CompilerExtension {

	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 *
	 * @return void
	 */
	public function loadConfiguration() {
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('shortCuts'))
				->setClass('Thunbolt\Application\ShortCuts\ShortCuts', [$this->getConfig()]);
	}

}
