<?php declare(strict_types = 1);

namespace Thunbolt\Application;

use Nette\ComponentModel\IComponent;
use ProLib\Efficiency\Traits\TComponentAnnotation;
use ProLib\Efficiency\Traits\TFormAnnotation;
use ProLib\Efficiency\Traits\TGetEntityPresenter;

abstract class AdvancedPresenter extends Presenter {

	use TComponentAnnotation;
	use TFormAnnotation;
	use TGetEntityPresenter;

	protected function createComponent(string $name): ?IComponent {
		if ($component = $this->formAnnotation($name)) {
			return $component;
		}
		if ($component = $this->componentAnnotation($name)) {
			return $component;
		}

		return parent::createComponent($name);
	}

}
