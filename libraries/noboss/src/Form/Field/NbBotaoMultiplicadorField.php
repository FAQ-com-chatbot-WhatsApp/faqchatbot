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
// use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
// use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbBotaoMultiplicadorField extends FormField {

	protected function getInput() {
		$excluir = Text::_("COM_NBFORMMAIL_EXCLUIR");
		$class = "delete hide" . (($this->__get("class") != "") ? " " . $this->__get("class") : "");
		$html =
		<<<HTML
			<div>
				<button name='{$this->getAttribute("name")}' class='repeat-fields' data-name='{$this->getAttribute("campos_multiplicar")}'>
					{$this->getAttribute('label')} <span class='fa fa-plus-circle'></span>
				</button>
			</div>
			<div class='{$class}'>
				<button class='delete-field' data-id='0' data-name='{$this->getAttribute("campos_multiplicar")}'>
					{$excluir} <span class='fa fa-minus-circle'></span>
				</button>
			</div>
HTML;
		return $html;
	}

	protected function getLabel() {
		return;
	}
}
