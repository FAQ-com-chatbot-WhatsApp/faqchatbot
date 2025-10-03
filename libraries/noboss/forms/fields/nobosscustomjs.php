<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

// TODO: DEPRECATED em abri/21. Utilizar o field nobosscustomeditor especificando type='js'

// Carrega arquivo que esta no padrao Joomla 5
use Noboss\Library\Form\Field\NbCustomJsField;

\defined('_JEXEC') or die;

class JFormFieldNobosscustomjs extends NbCustomJsField {
    
    protected $type = "nobosscustomjs";
}
