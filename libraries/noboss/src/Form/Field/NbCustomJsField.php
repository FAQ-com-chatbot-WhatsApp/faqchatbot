<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

// TODO: DEPRECATED em abri/21. Utilizar o field nobosscustomeditor especificando type='js'

namespace Noboss\Library\Form\Field;

use Noboss\Library\Form\Field\NbCustomEditorField;
// use Joomla\CMS\Factory;
// use Joomla\CMS\Language\Text;
// use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbCustomJsField extends NbCustomEditorField {
    
    protected function getInput(){
        $this->element['editor_type'] = 'js';

        return parent::getInput();
    }  

}
