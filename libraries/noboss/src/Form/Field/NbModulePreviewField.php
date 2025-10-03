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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/* 
 * Exibe um link de pre-visualizacao do modulo
 */
class NbModulePreviewField extends FormField {
  
    protected function getInput(){
        $app = Factory::getApplication();

        // Id do modulo
        $id = $app->input->get('id', 0);

        if(empty($id)){
            return '';
        }

        // Url do preview
        $linkModulePreview = Uri::root()."index.php?option=com_nobossajax&module-id={$id}&load-head=1";

        $html = "<joomla-toolbar-button class='nblinkpreview'>
                    <a href='{$linkModulePreview}' target='_blank' class='btn btn-info' type='button'>
                        ".Text::_('NOBOSS_EXTENSIONS_BUTTON_TEXT')."
                    </a>
                </joomla-toolbar-button>";

        return $html;
    }
}

