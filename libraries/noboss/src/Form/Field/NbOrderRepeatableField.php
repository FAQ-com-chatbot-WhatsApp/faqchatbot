<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\NumberField;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbOrderRepeatableField extends NumberField {

	protected function getInput() {
		//Verifica se a versão é maior que 3.7
		if (JVERSION >=  '3.7') {
			return;
		}
		return parent::getInput();
	}

	protected function getLabel() {
		//Verifica se a versão é maior que 3.7
		if (JVERSION >=  '3.7') {
			$this->description ='';
			return;
		}
		return parent::getLabel();
	}
}
