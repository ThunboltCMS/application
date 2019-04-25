<?php

declare(strict_types=1);

namespace Thunbolt\Application;

use Nette\Application\ForbiddenRequestException;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Localization\ITranslator;
use ProLib\Efficiency\Utils\ControlUtils;
use Thunbolt\User\User;
use Nette\Application\UI;
use Nette\Utils\Strings;

/**
 * @property-read User $user
 * @property-read array $names Lazy
 * @property-read Template|\stdClass $template
 * @method User getUser()
 */
abstract class Presenter extends UI\Presenter {

	/** @var ITranslator|null */
	protected $translator;

	/** @var array */
	private $names = [];

	/** @var string */
	private $presenterDir;

	/** @var ControlUtils */
	protected $_utils;

	protected function startup() {
		$this->_utils = new ControlUtils($this);

		parent::startup();
	}

	/**
	 * @return string
	 */
	public function getPresenterDir(): string {
		if (!$this->presenterDir) {
			$this->presenterDir = dirname($this->getReflection()->getFileName());
		}

		return $this->presenterDir;
	}

	/************************* Names **************************/

	/**
	 * @return array
	 */
	public function getNames(): array {
		if (!$this->names) {
			$explode = explode(':', $this->getName());
			$this->names = [
				'module' => count($explode) === 2 ? $explode[0] : null,
				'presenter' => end($explode),
				'action' => $this->action,
			];
		}

		return $this->names;
	}

	/************************* Redirects **************************/

	public function backLink(string $destination, array $params = []): string {
		$params['backlink'] = $this->link('this', ['backlink' => null]);

		return $this->link($destination, $params);
	}

	public function redirectBackLink(string $destination, array $params = []): string {
		$params['backlink'] = $this->link('this', ['backlink' => null]);

		$this->redirect($destination, $params);
	}

	public function redirectRestore($code, $destination = null, $args = []): void {
		if ($backlink = $this->getParameter('backlink')) {
			$this->restoreRequest($backlink);
		}

		call_user_func_array([$this, 'redirect'], func_get_args());
	}

	public function redirectStore($code, $destination = null, $args = []): void {
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
	public function redraw($snippets = null, string $link = 'this', $args = []): void {
		if ($this->isAjax()) {
			foreach ((array)$snippets as $snippet) {
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
		$redirect = false;

		foreach ($values as $name => $value) {
			if (!isset($parameters[$name]) || $parameters[$name] != Strings::webalize((string) $value)) {
				$parameters[$name] = Strings::webalize((string) $value);
				$redirect = true;
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
		$user = (array)$element->getAnnotation('user');

		// @user loggedIn
		if (in_array('loggedIn', $user, true) && !$this->getUser()->isLoggedIn()) {
			$this->flashMessage('core.requirements.loggedIn', 'error');
			$this->redirect('home.front');
		}

		// @user loggedOut
		if (in_array('loggedOut', $user, true) && $this->getUser()->isLoggedIn()) {
			$this->flashMessage('core.requirements.loggedOut', 'error');
			$this->redirect('home.front');
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
			"$dir/templates/$presenter/{$this->getView()}.latte",
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
		$list[] = $this->getContext()->parameters['layoutsDir'] . "/@$layout.latte";

		return $list;
	}

	/**
	 * Saves the message to template, that can be displayed after redirect.
	 *
	 * @param  string
	 * @param  string
	 * @return \stdClass
	 */
	public function flashMessage($message, string $type = 'success'): \stdClass {
		return parent::flashMessage($this->translate($message), $type);
	}

}
