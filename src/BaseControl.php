<?php

declare(strict_types=1);

namespace Thunbolt\Application;

use Nette\Application\UI\Control;

abstract class BaseControl extends Control {

	/**
	 * @param string|array $snippets
	 * @param string $link
	 * @param array $args
	 */
	public function redraw($snippets = NULL, string $link = 'this', array $args = []): void {
		if ($this->getPresenter()->isAjax()) {
			foreach ((array) $snippets as $snippet) {
				$this->redrawControl($snippet);
			}
		} else {
			$this->getPresenter()->redirect($link, $args);
		}
	}

	/**
	 * Saves the message to template, that can be displayed after redirect.
	 *
	 * @param  string
	 * @param  string
	 * @return \stdClass
	 */
	public function flashMessage(string $message, string $type = 'success'): \stdClass {
		return parent::flashMessage($message, $type);
	}

}
