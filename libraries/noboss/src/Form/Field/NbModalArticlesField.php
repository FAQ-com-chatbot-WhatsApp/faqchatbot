<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\Component\Content\Administrator\Field\Modal\ArticleField;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbModalArticlesField extends ArticleField {

    protected function getInput() {
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        $modalId = 'Article_' . $this->id;
        $modalName = 'ModalEdit' . $modalId;

        // Evento para quando usuario clicar no botao 'editar' para que mude a url do iframe da modal
        // TODO: pelo menos ateh marco de 2021 o Joomla estava com problema no field para editar um artigo selecionado (quebrava a url do iframe e nao abria o artigo para edicao). Por isso fizemos o codigo abaixo para corrigir a url do iframe
        $wa->addInlineScript('
            jQuery(function($) {
                jQuery(document).on("click", "#' . $this->id . '_edit", function(e) {
                    var articleID = jQuery("#'.$this->id.'_id").val();
                    var url = "' . Uri::base() . 'index.php?option=com_content&view=article&layout=modal&tmpl=component&'.Session::getFormToken().'=1&task=article.edit&id=" + articleID;
                    jQuery("#' . $modalName . ' iframe").attr("src", url);
                });
            });

        ');

        return parent::getInput();
    }
} 
