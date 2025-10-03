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
use Noboss\Library\Util\NbJsConstantsUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbModalField extends FormField {

    protected function getLabel(){
        // Parametro de versao minima requirida para o joomla esta definido e versao atual eh menor: nao exibe a modal
        if((!empty($this->getAttribute('minimumversionjoomlarequired'))) && (JVERSION < $this->getAttribute('minimumversionjoomlarequired'))){
            return;
        }

        $label = $this->getAttribute('label');

        // Nao tem label definido: fecha html e carrega botao alinhado na esqueda
        if(empty($label)){
            return '</div><div>'.$this->getModal();
        }
        else{
            return parent::getLabel();
        }
    }

    protected function getInput(){
        $label = $this->getAttribute('label');

        // Tem label definido: carrega bt no input
        if(!empty($label)){
            return $this->getModal();
        }
    }

    function getModal(){
        // Adiciona constantes padroes do JS
        NbJsConstantsUtil::addConstantsDefault();
        
        // Obtem a tag do idioma que esta sendo navegado
        $currentLanguage = Factory::getLanguage()->getTag();

        // Obtem valor
        $json = !empty($this->value) ? htmlspecialchars($this->value) : '';
        
        // Obtem texto label
        $label = Text::_($this->getAttribute('label'));
        // Texto para botão de abrir a modal
        $buttonOpenModal = $this->getAttribute('button');
        // Se o texto do botao nao estiver definido, pega o label e se nao tiver label, coloca valor default
        $buttonOpenModal = empty($buttonOpenModal) ? (empty($label) ? Text::_('NOBOSS_EXTENSIONS_MODAL_ITEMS_CUSTOMIZATION_BUTTON') : $label) : Text::_($buttonOpenModal);

        // Caminho para o arquivo xml
        $xmlPath = $this->getAttribute('formsource');
        $fullXmlPath = JPATH_ROOT."/".$xmlPath;
        
        $attrNotFoundMessage = $this->getAttribute('not_found_message');

        $html = "";

        // Arquivo XML existe: exibe botao para abrir modal
        if(file_exists($fullXmlPath)){
            $html .= "<a data-id='noboss-modal' class='btn btn-nb btn-primary'>
                        {$buttonOpenModal}
                      </a>";
        }
        // Arquivo XML nao existe e foi definida mensagem para qnd nao for encontrado
        else if (!empty($attrNotFoundMessage)){
            $html = Text::_($attrNotFoundMessage);
        }
        // Arquivo XML nao existe e aplica mensagen default de recurso nao incluso no plano
        else{
            $html = "<div class='alert' style='padding: 10px; margin: 0 0 0 10px; display: inline-block;'><span class='icon-minus-circle'></span> &nbsp;".Text::_('LIB_NOBOSS_BLOCK_FIELD_MODAL_COMPLETE_SIDE_FIELD')."</div>";
        }

        // Definido no xml que devemos obter o valor de um campo de ID do formulario onde a modal eh chamada e enviar junto na requisicao ajax da modal
        if(!empty($this->getAttribute('aliasfieldid'))){
            $aliasFieldId = $this->getAttribute('aliasfieldid');
            $valueFieldId = $this->form->getData()->get($aliasFieldId);
        }

        $html .= "<input type='hidden' data-formsource='{$xmlPath}' name='{$this->name}' data-id='noboss-modal-input-hidden' data-modal-name='{$this->getAttribute('name')}' ".((!empty($valueFieldId) ? "data-valuefieldid='{$valueFieldId}'" : ''))." value='{$json}' class='{$this->class}'/>";

        $app = Factory::getApplication();
        $doc = Factory::getDocument();
        $wa = $app->getDocument()->getWebAssetManager();

        // Joomla 3 e 4
        if(version_compare(JVERSION, '5', '<')){
            // Adiciona variaveis usadas do j5 que nao existem no j3 e j4
            $wa->addInlineStyle('
                :root {
                    --main-card-bg: #fff;
                    --admin-background: #f0f4fb;
                    --header-bg: hsl(214,40%,20%);
                    --template-bg-dark-10: hsl(214,40%,90%);
                }
            ');
        }

        $wa->registerAndUseScript('nobossmodal', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobossmodal.min.js");
        $wa->registerAndUseStyle('nobossmodal', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobossmodal.min.css");
        
        // adiciona ao js a versao do joomla
        Factory::getDocument()->addScriptOptions('nobossmodal', array(
            'lowerJVersion' => version_compare(JVERSION, '3.7.3', '<'),
            'higherJVersion' => version_compare(JVERSION, '3.8.0', '>=')
        ));

        // Adiciona as constantes de traducao
        $wa->addInline("script", '
                if(!codeLanguage){
                    var codeLanguage = "'.$currentLanguage.'";
                }

                if(!translationConstants){
                    var translationConstants = {};  
                }
                translationConstants.nobossmodal = {};
                
                translationConstants.nobossmodal.LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_CANCEL_LABEL = "'. Text::_('LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_CANCEL_LABEL').'";
                translationConstants.nobossmodal.LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_CANCEL_DESC = "'. Text::_("LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_CANCEL_DESC").'";
                translationConstants.nobossmodal.LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_RESET_LABEL = "'. Text::_('LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_RESET_LABEL').'";
                translationConstants.nobossmodal.LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_RESET_DESC = "'. Text::_("LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_RESET_DESC").'";
                translationConstants.nobossmodal.LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_CONFIRM_CANCEL_BUTTON = "'. Text::_("LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_CONFIRM_CANCEL_BUTTON").'";
                translationConstants.nobossmodal.LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_CONFIRM_CONFIRM_BUTTON = "'. Text::_("LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_CONFIRM_CONFIRM_BUTTON").'";
                translationConstants.nobossmodal.LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_CONNECTION_ERROR_TITLE = "'. Text::_("LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_CONNECTION_ERROR_TITLE").'";
                translationConstants.nobossmodal.LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_CONNECTION_ERROR_CONTENT = "'. Text::_("LIB_NOBOSS_FIELD_NOBOSSMODAL_MODAL_CONNECTION_ERROR_CONTENT").'";
                ', array('name' => ".nobossmodal"));
        
        return $html;
    }
}
