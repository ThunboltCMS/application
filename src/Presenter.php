<?php

declare(strict_types=1);

namespace Thunbolt\Application;

use Kdyby\Translation\LocaleResolver\SessionResolver;
use Kdyby\Translation\Translator;
use Nette\Application\ForbiddenRequestException;
use Nette\Localization\ITranslator;
use Thunbolt\User\User;
use Nette\Application\UI\Form;
use Nette\Application\UI;
use WebChemistry\Utils\Strings;
use WebChemistry\Widgets;

/**
 * @property-read User $user
 * @property-read array $names Lazy
 * @method User getUser()
 */
abstract class Presenter extends UI\Presenter {

	/** @var array */
	private $names = [];

	/** @var Translator|ITranslator */
	protected $translator;

	/** @var SessionResolver */
	private $translatorSession;

	/** @var Widgets\Manager */
	private $widgets;

	/** @var string */
	private $presenterDir;

	/**
	 * @return string
	 */
	public function getPresenterDir(): string {
		if (!$this->presenterDir) {
			$this->presenterDir = dirname($this->getReflection()->getFileName());
		}

		return $this->presenterDir;
	}

	/**
	 * @return UI\ITemplate
	 */
	protected function createTemplate(): UI\ITemplate {
		$template = parent::createTemplate();
		$template->widgets = $this->getWidgets();

		return $template;
	}

	/************************* Names **************************/

	/**
	 * @return array
	 */
	public function getNames(): array {
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

	/************************* Redirects **************************/

	public function redirectRestore($code, $destination = NULL, $args = []): void {
		if ($backlink = $this->getParameter('backlink')) {
			$this->restoreRequest($backlink);
		}

		call_user_func_array([$this, 'redirect'], func_get_args());

	}

	public function redirectStore($code, $destination = NULL, $args = []): void {
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
	public function redraw($snippets = NULL, string $link = 'this', $args = []): void {
		if ($this->isAjax()) {
			foreach ((array) $snippets as $snippet) {
				$this->redrawControl($snippet);
			}
		} else {
			$this->redirect($link, $args);
		}
	}

	/**
	 * @param array $values name => value
	 */
	protected function checkParameters(array $values): void {
		$parameters = $this->getParameters();
		$redirect = FALSE;

		foreach ($values as $name => $value) {
			if (!isset($parameters[$name]) || $parameters[$name] !== Strings::webalize($value)) {
				$parameters[$name] = Strings::webalize($value);
				$redirect = TRUE;
			}
		}

		if ($redirect) {
			$this->redirectPermanent('this', $parameters);
		}
	}

	/************************* Rewrite parent methods **************************/

	/**
	 * @param UI\PresenterComponentReflection $element
	 * @throws ForbiddenRequestException
	 */
	public function checkRequirements($element): void {
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
			if (!$this->getUser()->isAllowed($road)) {
				$module = substr($this->getName(), 0, strpos($this->getName(), ':'));
				$this->flashMessage('core.requirements.isAllowed', 'error');
				$this->redirect('home.' . strtolower($module));
			}
		}
	}

	/**
	 * @return array
	 */
	public function formatTemplateFiles(): array {
		$name = $this->getName();
		$presenter = substr($name, strrpos(':' . $name, ':'));
		$dir = $this->getPresenterDir();
		$paths = [
			"$dir/templates/$presenter/{$this->getView()}.latte"
		];

		return $paths;
	}

	public function formatLayoutTemplateFiles(): array {
		if ($this->getLayout() && preg_match('#/|\\\\#', $this->getLayout())) {
			return [$this->getLayout()];
		}
		$names = $this->getNames();
		$module = $names['module'];
		$presenter = $names['presenter'];
		$layout = $this->getLayout() ? $this->getLayout() : strtolower($module);
		$presenterDir = $this->getPresenterDir();
		$list = [
			"$presenterDir/templates/@layout.latte",
			"$presenterDir/templates/$presenter/@layout.latte",
		];
		$list[] = $this->getContext()->parameters['layoutsDir']. "/@$layout.latte";

		return $list;
	}

	/**
	 * @param Form $form
	 */
	public function errorFormToFlash(Form $form): void {
		foreach ($form->getOwnErrors() as $error) {
			$this->flashMessage($error, 'error');
		}

		foreach ($form->getControls() as $control ) {
			foreach ($control->getErrors() as $error) {
				$this->flashMessage($control->caption . ': ' . $error, 'error');
			}
		}
	}

	// translations

	/**
	 * @param ITranslator $translator
	 * @param SessionResolver|NULL $sessionResolver
	 */
	public function injectTranslator(ITranslator $translator, SessionResolver $sessionResolver = NULL): void {
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
	public function translate(string $message, int $count = NULL, array $parameters = array(), string $domain = NULL, string $locale = NULL): string {
		return $this->translator ? $this->translator->translate($message, $count, $parameters, $domain, $locale) : $message;
	}

	// widgets

	/**
	 * @param Widgets\Factory $factory
	 */
	public function injectWidgets(Widgets\Factory $factory = NULL): void {
		$this->widgets = $factory ? $factory->create() : NULL;
	}

	/**
	 * Saves the message to template, that can be displayed after redirect.
	 *
	 * @param  string
	 * @param  string
	 * @return \stdClass
	 */
	public function flashMessage(string $message, string $type = 'success'): \stdClass {
		return parent::flashMessage($this->translate($message), $type);
	}

	/**
	 * @return Widgets\Manager
	 */
	public function getWidgets(): ?Widgets\Manager {
		return $this->widgets ? $this->getComponent('widgets') : NULL;
	}

	/**
	 * @return Widgets\Manager
	 */
	protected function createComponentWidgets(): Widgets\Manager {
		return $this->widgets;
	}

}
