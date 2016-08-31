<?php

namespace Thunbolt\Application;

use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\LocaleResolver\SessionResolver;
use Kdyby\Translation\Translator;
use Nette\Application\ForbiddenRequestException;
use Thunbolt\Application\ShortCuts\TPresenter;
use Thunbolt\User\User;
use WebChemistry\Forms\Form;
use WebChemistry\Forms\Traits\TSuggestion;
use WebChemistry\Parameters;
use Nette\Application\UI;
use WebChemistry\Utils\Strings;
use WebChemistry\Widgets;

/**
 * @property-read User $user
 * @property-read array $names Lazy
 */
abstract class Presenter extends UI\Presenter {

	use TSuggestion;
	use TPresenter;

	/** @var EntityManager @inject */
	public $em;

	/** @var array */
	private $names = [];

	/** @var Translator */
	protected $translator;

	/** @var SessionResolver */
	private $translatorSession;

	/** @var Parameters\Provider */
	protected $settings;

	/** @var Widgets\Manager */
	private $widgets;

	/** @var string */
	private $presenterDir;

	/**
	 * @return string
	 */
	public function getPresenterDir() {
		if (!$this->presenterDir) {
			$this->presenterDir = dirname($this->getReflection()->getFileName());
		}

		return $this->presenterDir;
	}

	/**
	 * @return Widgets\Manager
	 */
	public function getWidgets() {
		return $this->widgets ? $this->getComponent('widgets') : NULL;
	}

	/**
	 * @return Widgets\Manager
	 */
	protected function createComponentWidgets() {
		return $this->widgets;
	}

	/**
	 * @return UI\ITemplate
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
	 * @param Widgets\Factory $factory
	 */
	public function injectWidgets(Widgets\Factory $factory = NULL) {
		$this->widgets = $factory ? $factory->create() : NULL;
	}

	/**
	 * @param Parameters\Provider $parametersProvider
	 */
	public function injectProviders(Parameters\Provider $parametersProvider = NULL) {
		$this->settings = $parametersProvider;
	}

	/**
	 * @param Translator $translator
	 * @param SessionResolver $sessionResolver
	 */
	public function injectTranslator(Translator $translator = NULL, SessionResolver $sessionResolver = NULL) {
		$this->translator = $translator;
		$this->translatorSession = $sessionResolver;
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

	public function redirectRestore($code, $destination = NULL, $args = array()) {
		if ($backlink = $this->getParameter('backlink')) {
			$this->restoreRequest($backlink);
		}

		call_user_func_array([$this, 'redirect'], func_get_args());

	}

	public function redirectStore($code, $destination = NULL, $args = array()) {
		if (!$args) {
			$destination['backlink'] = $this->storeRequest();
		} else {
			$args['backlink'] = $this->storeRequest();
		}

		if (func_num_args() < 3) {
			$this->redirect($code, $destination);
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
			if (!isset($parameters[$name]) || $parameters[$name] !== Strings::webalize($value)) {
				$parameters[$name] = Strings::webalize($value);
				$redirect = TRUE;
			}
		}

		if ($redirect === TRUE) {
			$this->redirect('this', $parameters);
		}
	}

	/************************* Rewrite parent methods **************************/

	/**
	 * @param UI\PresenterComponentReflection $element
	 * @throws ForbiddenRequestException
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
		$dir = $this->getPresenterDir();
		$paths = [
			"$dir/../Resources/templates/$presenter/$this->view.latte"
		];

		return $paths;
	}

	public function formatLayoutTemplateFiles() {
		if (preg_match('#/|\\\\#', $this->getLayout())) {
			return [$this->getLayout()];
		}
		$names = $this->getNames();
		$module = $names['module'];
		$presenter = $names['presenter'];
		$layout = $this->getLayout() ? $this->getLayout() : strtolower($module);
		$presenterDir = $this->getPresenterDir();
		$dir = $this->getContext()->parameters['appDir'] . '/layouts';
		$list = [
			"$presenterDir/templates/@layout.latte",
			"$presenterDir/templates/$presenter/@layout.latte",
		];
		$list[] = "$dir/@$layout.latte";

		return $list;
	}

	/**
	 * @param Form $form
	 */
	public function errorFormToFlash(Form $form) {
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
