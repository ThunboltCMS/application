<?php

namespace Thunbolt\Application\DI;

use Nette\Application\IPresenterFactory;
use Nette\DI\CompilerExtension;
use Thunbolt\Application\Bridges\Thunbolt\ThunboltPresenterMapping;
use Thunbolt\Application\PresenterFactory;

class ApplicationExtension extends CompilerExtension {

	public function beforeCompile() {
		$builder = $this->getContainerBuilder();

		$def = $builder->getDefinition($builder->getByType(IPresenterFactory::class));
		$def->getFactory()->arguments[] = [
			'Front' => ThunboltPresenterMapping::class . '("Front")',
			'Admin' => ThunboltPresenterMapping::class . '("Admin")',
		];
		$def->setFactory(PresenterFactory::class, $def->getFactory()->arguments);
	}

}