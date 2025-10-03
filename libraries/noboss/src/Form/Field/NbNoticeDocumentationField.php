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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

// Exibe uma notificacao no topo da extensao de um modulo para quando eh registro novo
class NbNoticeDocumentationField extends FormField {
    
    protected function getInput(){    
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        // Obtem o texto a ser exibido
        $text = $this->getAttribute('text');

        // Nao exibe se texto nao definido ou modulo ja estiver salvo
        if(empty($text) || (Factory::getApplication()->input->get('id', 0, "INT") > 0)){
            return;
        }

        // Exibe a mensagem no topo da pagina
        $wa->addInlineScript('
        jQuery(document).ready(function (jQuery) {
            jQuery("#system-message-container").before("<div class=\"feedback-notice alert alert-info\" style=\"margin-bottom: 10px !important;\"><div class=\"feedback-notice__content\"><p class=\"feedback-notice__message\">'.Text::_($text).'</p></div></div>");
        });');


        // TODO: podemos analisar de ter um JS que remove a mensagem apos xx segundos
    }
}
