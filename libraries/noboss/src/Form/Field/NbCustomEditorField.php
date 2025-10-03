<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\TextareaField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Util\NbJsConstantsUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbCustomEditorField extends TextareaField{

    protected function getInput(){
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        // Seta como default a altura como 20 rows
        $this->rows = (!empty($this->rows)) ? $this->rows : 20;

        // Classe css default
        $this->class .= " nb-custom-editor";

        // Tipo de editor definido  ('js', 'css', 'html')
        switch ($this->element['editor_type']) {
            case 'js':
                // Placeholder nao definido: seta default
                if(empty($this->hint)){
                    $this->hint = '$(document).ready(function(){\n      // Code... \n});';
                }

                // Classe css que identifica o tipo
                $this->class .= " nb-custom-editor_js";
                
                // Realiza replace no description com base no class name
                $this->description = str_replace('#class_name#', ".{$this->element['class_name']}", Text::_('NOBOSS_EXTENSIONS_JS_OVERWRITE_DESC'));
                break;
            
            case 'css':
                // Placeholder nao definido: seta default
                if(empty($this->hint)){
                    $this->hint = '.example{\n     border: 2px; \n}';
                }

                // Classe css que identifica o tipo
                $this->class .= " nb-custom-editor_css";
                break;
                
            case 'html':
                // Classe css que identifica o tipo
                $this->class .= " nb-custom-editor_html";
            default:
                // Placeholder nao definido: seta default
                if(empty($this->hint)){
                    $this->hint = '<p>\n      code... \n</p>';
                }
                break;
        }
        

        // Adiciona constantes padroes do JS
        NbJsConstantsUtil::addConstantsDefault();

        $wa->registerAndUseStyle('nobosscustomeditor', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobosscustomeditor.min.css");
        $wa->registerAndUseScript('jquery-ui-1.12.1', 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js');
        $wa->registerAndUseScript('nobosscustomeditor', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobosscustomeditor.min.js");

        return parent::getInput();
    }  

}
