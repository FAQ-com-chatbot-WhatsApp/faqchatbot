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
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbRequestJsConstantsField extends FormField {
    
    protected function getInput(){
        // pega as constantes que devem ser passadas para o js
        $constants = array_map('trim', explode(",", $this->getAttribute('constants')));
        // percorre cada constante
        foreach ($constants as $constant) {
            // adiciona ela ao js
            Text::script($constant);
        }
    }
}
