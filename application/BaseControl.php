<?php

namespace Thunbolt\Application;

use Nette\Application\UI\Control;

abstract class BaseControl extends Control {

	/**
	 * @param string|array $snippets
	 * @param string $link
	 * @param array $args
	 */
	public function redraw($snippets = NULL, $link = 'this', $args = []) {
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
	public function flashMessage($message, $type = 'success') {
		return parent::flashMessage($message, $type);
	}

}
