<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\FormField;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbSpacerField extends FormField {

	protected function getInput() {
		return;
	}

	protected function getLabel() {
		$html = '<span class="spacer' . (($this->element["class"]) ? " " . $this->element["class"] : "") .  '"' . (($this->element["height"]) ? ' style="height: ' . $this->element["height"] . 'px"' : "") . ">";
		if($this->element["hr"]) {
			$html .= "<hr />";
		}
		$html .= "</span>";
		return $html;
	}

	protected function getTitle() {
		return $this->getLabel();
	}
}
