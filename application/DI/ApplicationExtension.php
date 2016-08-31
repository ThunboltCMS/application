<?php

namespace Thunbolt\Application\DI;

use Nette\Application\IPresenterFactory;
use Nette\DI\CompilerExtension;
use Thunbolt\Application\PresenterFactory;

class ApplicationExtension extends CompilerExtension {

	public function beforeCompile() {
		$builder = $this->getContainerBuilder();

		$def = $builder->getDefinition($builder->getByType(IPresenterFactory::class));
		$def->setFactory(PresenterFactory::class, $def->getFactory()->arguments);
	}

}
