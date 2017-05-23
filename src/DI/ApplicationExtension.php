<?php

declare(strict_types=1);

namespace Thunbolt\Application\DI;

use Nette\Application\IPresenterFactory;
use Nette\DI\CompilerExtension;
use Thunbolt\Application\Bridges\Thunbolt\ErrorPresenterMapping;
use Thunbolt\Application\Bridges\Thunbolt\ThunboltPresenterMapping;
use Thunbolt\Application\PresenterFactory;

class ApplicationExtension extends CompilerExtension {

	public function beforeCompile(): void {
		$builder = $this->getContainerBuilder();

		$def = $builder->getDefinition($builder->getByType(IPresenterFactory::class));
		$def->getFactory()->arguments[] = [
			'Front' => new ThunboltPresenterMapping('Front'),
			'Admin' => new ThunboltPresenterMapping('Admin'),
			'Error' => new ErrorPresenterMapping('Error'),
		];
		$def->setFactory(PresenterFactory::class, $def->getFactory()->arguments);
	}

}
