<?php declare(strict_types=1);

namespace Thunbolt\Application;

use Nette\Application;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\Helpers;
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

	/** @var string */
	private $presenterDir;

	/** @var ControlUtils */
	protected $_utils;

	protected function startup() {
		$this->_utils = new ControlUtils($this);

		parent::startup();
	}


	/************************* Redirects **************************/

	public function backLink(string $destination, array $params = []): string {
		$params['_backlink'] = $this->link('this', ['_backlink' => null]);

		return $this->link($destination, $params);
	}

	public function redirectWithBackLink(string $destination, array $params = []) {
		$params['_backlink'] = $this->link('this', ['_backlink' => null]);

		$this->redirect($destination, $params);
	}

	public function redirectBack(string $destination, array $params = []): void {
		if ($backlink = $this->getParameter('_backlink')) {
			$this->redirectUrl($backlink);
		} else {
			$this->redirect($destination, $params);
		}
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
			if (!isset($parameters[$name]) || $parameters[$name] !== Strings::webalize((string) $value)) {
				$parameters[$name] = Strings::webalize((string) $value);
				$redirect = true;
			}
		}

		if ($redirect) {
			$this->redirectPermanent('this', $parameters);
		}
	}

	private function getPresenterDir(): string {
		if (!$this->presenterDir) {
			$this->presenterDir = dirname($this->getReflection()->getFileName());
		}

		return $this->presenterDir;
	}

	/************************* Rewrite parent methods **************************/

	/**
	 * @return string[]
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

	/**
	 * @return string[]
	 */
	public function formatLayoutTemplateFiles(): array {
		if ($this->getLayout() && preg_match('#/|\\\\#', $this->getLayout())) {
			return [$this->getLayout()];
		}
		[$module, $presenter] = Helpers::splitName($this->getName());

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
		return parent::flashMessage($message, $type);
	}

}
