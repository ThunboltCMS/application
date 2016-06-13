<?php

namespace Thunbolt\Application;

class Control extends \Nette\Application\UI\Control {

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
