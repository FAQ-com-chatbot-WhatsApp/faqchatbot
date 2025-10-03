<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\RadioField;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

// Sobreescreve o field do joomla para setar layout especifico no Joomla 4
class NbRadioField extends RadioField {

	protected function getInput() {
        $this->layout = 'joomla.form.field.radio.switcher';
        
        return '<div class="radio">'.parent::getInput()."</div>";
	}
}
