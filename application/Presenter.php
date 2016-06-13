<?php

namespace Thunbolt\Application;

use Nette, Kdyby, Doctrine, WebChemistry;
use Thunbolt\Application\ShortCuts\TPresenter;

/**
 * @property-read WebChemistry\User\User $user
 * @property-read array $names
 * @property-read WebChemistry\Parameters\Provider $settings
 */
abstract class Presenter extends Nette\Application\UI\Presenter {

	use WebChemistry\Forms\Traits\TSuggestion;
	use TPresenter;

	/** @var Kdyby\Doctrine\EntityManager @inject */
	public $em;

	/** @var array */
	private $names = [];

	/** @var Kdyby\Translation\Translator */
	protected $translator;

	/** @var Kdyby\Translation\LocaleResolver\SessionResolver */
	private $translatorSession;

	/** @var WebChemistry\Parameters\Provider */
	private $parametersProvider;

	/** @var WebChemistry\Widgets\Manager */
	private $widgets;

	/**
	 * @return WebChemistry\Widgets\Manager
	 */
	public function getWidgets() {
		return $this->widgets ? $this->getComponent('widgets') : NULL;
	}

	/**
	 * @return WebChemistry\Widgets\Manager
	 */
	protected function createComponentWidgets() {
		return $this->widgets;
	}

	/**
	 * @return Nette\Application\UI\ITemplate
	 */
	protected function createTemplate() {
		$template = parent::createTemplate();
		$template->widgets = $this->getWidgets();

		return $template;
	}

	/************************* Names **************************/

	/**
	 * @return array
	 */
	public function getNames() {
		if (!$this->names) {
			$explode = explode(':', $this->name);
			$this->names = [
				'module'    => count($explode) === 2 ? $explode[0] : NULL,
				'presenter' => end($explode),
				'action' => $this->action
			];
		}

		return $this->names;
	}

	/************************* Injectors **************************/

	/**
	 * @param WebChemistry\Widgets\Factory $factory
	 */
	public function injectWidgets(WebChemistry\Widgets\Factory $factory = NULL) {
		$this->widgets = $factory ? $factory->create() : NULL;
	}

	/**
	 * @param WebChemistry\Parameters\Provider $parametersProvider
	 */
	public function injectProviders(WebChemistry\Parameters\Provider $parametersProvider) {
		$this->parametersProvider = $parametersProvider;
	}

	/**
	 * @param Kdyby\Translation\Translator $translator
	 * @param Kdyby\Translation\LocaleResolver\SessionResolver $sessionResolver
	 */
	public function injectTranslator(Kdyby\Translation\Translator $translator = NULL, Kdyby\Translation\LocaleResolver\SessionResolver $sessionResolver = NULL) {
		$this->translator = $translator;
		$this->translatorSession = $sessionResolver;
	}

	/**
	 * @return WebChemistry\Parameters\Provider
	 */
	public function getSettings() {
		return $this->parametersProvider;
	}

	/**
	 * @param string $message
	 * @param int $count
	 * @param array $parameters
	 * @param string $domain
	 * @param string $locale
	 * @return string
	 */
	public function translate($message, $count = NULL, array $parameters = array(), $domain = NULL, $locale = NULL) {
		return $this->translator ? $this->translator->translate($message, $count, $parameters, $domain, $locale) : $message;
	}

	/************************* Redirects **************************/

	public function redirectRestore($backLink, $code, $destination = NULL, $args = array()) {
		$this->restoreRequest($backLink);
		$args = func_get_args();

		call_user_func_array(array($this, 'redirect'), array_slice($args, 1));
	}

	public function redirectStore($code, $destination = NULL, $args = array()) {
		if (!$args) {
			$destination['backlink'] = $this->storeRequest();
		} else {
			$args['backlink'] = $this->storeRequest();
		}

		$this->redirect($code, $destination, $args);
	}

	/************************* Other methods **************************/

	/**
	 * @param string|array $snippets
	 * @param string $link
	 * @param array $args
	 */
	public function redraw($snippets = NULL, $link = 'this', $args = []) {
		if ($this->presenter->isAjax()) {
			foreach ((array) $snippets as $snippet) {
				$this->redrawControl($snippet);
			}
		} else {
			$this->presenter->redirect($link, $args);
		}
	}

	/**
	 * @param array $values name => value
	 */
	protected function checkParameters(array $values) {
		$parameters = $this->getParameters();
		$redirect = FALSE;

		foreach ($values as $name => $value) {
			if (!isset($parameters[$name]) || $parameters[$name] !== Nette\Utils\Strings::webalize($value)) {
				$parameters[$name] = Nette\Utils\Strings::webalize($value);
				$redirect = TRUE;
			}
		}

		if ($redirect === TRUE) {
			$this->redirect('this', $parameters);
		}
	}

	/************************* Rewrite parent methods **************************/

	/**
	 * @param Nette\Application\UI\PresenterComponentReflection $element
	 * @throws Nette\Application\ForbiddenRequestException
	 */
	public function checkRequirements($element) {
		$user = (array) $element->getAnnotation('user');

		// @user loggedIn
		if (in_array('loggedIn', $user, TRUE) && !$this->getUser()->isLoggedIn()) {
			$this->flashMessage('core.requirements.loggedIn', 'error');
			$this->redirect('home.front');
		}

		// @user loggedOut
		if (in_array('loggedOut', $user, TRUE) && $this->getUser()->isLoggedIn()) {
			$this->flashMessage('core.requirements.loggedOut', 'error');
			$this->redirect('home.front');
		}

		// @isAllowed resource:privilege
		$isAllowed = (array) $element->getAnnotation('isAllowed');

		foreach ($isAllowed as $road) {
			if (!$this->user->isAllowed($road)) {
				$module = substr($this->getName(), 0, strpos($this->getName(), ':'));
				$this->flashMessage('core.requirements.isAllowed', 'error');
				$this->redirect('home.' . strtolower($module));
			}
		}
	}

	/**
	 * @return array
	 */
	public function formatTemplateFiles() {
		$name = $this->getName();
		$presenter = substr($name, strrpos(':' . $name, ':'));
		$dir = dirname($this->getReflection()->getFileName());
		$paths = [
			"$dir/templates/$presenter/$this->view.latte"
		];
		if ($this instanceof IExpand) {
			$dir = dirname((new \ReflectionClass(get_parent_class($this)))->getFileName());
			$paths[] = "$dir/templates/$presenter/$this->view.latte";
		}

		return $paths;
	}

	public function findLayoutTemplateFile() {
		$names = $this->getNames();
		$module = $names['module'];
		$presenter = $names['presenter'];
		$layout = $this->layout ? $this->layout : strtolower($module);
		$presenterDir = dirname($this->getReflection()->getFileName());
		$dir = $this->context->parameters['appDir'] . '/layouts';
		$list = [
			"$presenterDir/templates/@layout.latte",
			"$presenterDir/templates/$presenter/@layout.latte",
		];
		$list[] = "$dir/@$layout.latte";

		foreach ($list as $file) {
			if (file_exists($file)) {
				return $file;
			}
		}
	}

	/**
	 * @param \WebChemistry\Forms\Form $form
	 */
	public function errorForm(WebChemistry\Forms\Form $form) {
		foreach ($form->getOwnErrors() as $error) {
			$this->flashMessage($error, 'error');
		}

		foreach ($form->getControls() as $control ) {
			foreach ($control->getErrors() as $error) {
				$this->flashMessage($control->caption . ': ' . $error, 'error');
			}
		}
	}

	/**
	 * Saves the message to template, that can be displayed after redirect.
	 *
	 * @param  string
	 * @param  string
	 * @return \stdClass
	 */
	public function flashMessage($message, $type = 'success') {
		return parent::flashMessage($this->translate($message), $type);
	}

}
