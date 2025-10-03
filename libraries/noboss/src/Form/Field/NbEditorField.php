<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\EditorField;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
// use Joomla\CMS\Language\Text;
// use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbEditorField extends EditorField{

    protected function getInput(){
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();
        $input = $app->input;

        // Obtem parametros do componente que estiver carregado na pagina onde campo eh carregado
        $paramsComponent = ComponentHelper::getParams($input->get('option'));

        // Adiciona arquivo JS que customiza cores de textos brancos
        $wa->registerAndUseScript('nobosseditor', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobosseditor.min.js");

        /* Obtem editor setado na config do componente atual (caso campo existe e caso valor esteja definido)
         * Qnd nao estiver definido, segue regra padrao do joomla que eh pririozar:
         *      1 - Opcao definida no XML pelo parametro 'editor'
         *      2 - Opcao definida nas configuracoes do usuario ou das configuracoes globais do site
         */
        if(!empty($paramsComponent->get('preferred_editor', ''))){
            $this->editorType = array($paramsComponent->get('preferred_editor', ''));
        }

        $style = '';

        if(!empty($this->width)){
            $style = "max-width: {$this->width}; width: 100%;";
        }

        // Adiciona div externa ao editor para podermos setar estilos de largura
        $html = "<div style='{$style}' class='nb-editor {$this->class}'>";

        $html .= parent::getInput();

        // Fecha o html do editor
        $html .= "</div>";
            
        return $html;
    }

}
