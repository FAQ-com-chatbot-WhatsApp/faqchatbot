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
use Noboss\Library\Util\NbUrlUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbThemeField extends FormField {
    
    private $rawExtensionName;

  	protected function getInput(){
        // Adiciona constantes padroes do JS
        NbJsConstantsUtil::addConstantsDefault();

        // Obtem valor
        $jsonValue = json_decode(htmlspecialchars_decode($this->value));

        // Caso esteja vazio, cria um objeto para impedir erros
        if(empty($jsonValue)){
            $jsonValue = new \stdClass();
            $jsonValue->theme = '';
            $jsonValue->sample = new \stdClass();
            $jsonValue->sample->id = '';
            $jsonValue->sample->img = '';
        }
        
		// Obtem texto label
        $label = Text::_($this->getAttribute('label'));
        
        // Texto para botão de abrir a modal
        $buttonOpenModal = $this->getAttribute('button');
        // Se o texto do botao nao estiver definido, pega padrao da constante de traducao
        $buttonOpenModal = empty($buttonOpenModal) ? Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_MODAL_BUTTON') : Text::_($buttonOpenModal);
        
        $this->rawExtensionName = (!empty($this->form->getData()->get('module'))) ? str_replace('mod_noboss', '', $this->form->getData()->get('module')) : '';

        // Nome do modulo sem o prefixo
        if(empty($this->rawExtensionName)){
            // Nome da extensao foi localizada no xml
            if (!empty($this->element['ext_name'])){
                // Define o nome da extensoa a partir do elemento no xml
                $this->rawExtensionName = $this->element['ext_name'];
            }
            else{
                $formNameArray = explode('.', substr($this->form->getName(), 10));
                $this->rawExtensionName = $formNameArray[0];
            }
        }

        $app = Factory::getApplication();
        $doc = Factory::getDocument();
        $wa = $app->getDocument()->getWebAssetManager();
        
        // adiciona ao js o nome da extensao e a versao do joomla
        Factory::getDocument()->addScriptOptions('nobosstheme', array(
            'extName' => $this->rawExtensionName,
            'lowerJVersion' => version_compare(JVERSION, '3.7.3', '<'),
            'langCode' => Factory::getLanguage()->get('tag')
        ));

        // guarda os subforms que devem ser substituidos alem dos de itens
        $subforms = (!empty($this->element['subforms'])) ? array_map('trim', explode(",", $this->element['subforms'])) : array();
       
        // guarda as modais que devem ser substituidas alem das de area externa e itens
        $modals = (!empty($this->element['modals'])) ? array_map('trim', explode(",", $this->element['modals'])) : array();

        // guarda as fields que devem ser substituidas
        $fields = (!empty($this->element['fields'])) ? array_map('trim', explode(",", $this->element['fields'])) : array();

        // transforma em json
        $subforms = json_encode($subforms);
        // Adiciona ao js
        Factory::getDocument()->addScriptOptions('nobosstheme', array(
            'subforms' => $subforms
        ));

        // transforma em json
        $modals = json_encode($modals);
        // Adiciona ao js
        Factory::getDocument()->addScriptOptions('nobosstheme', array(
            'modals' => $modals
        ));

        // transforma em json
        $fields = json_encode($fields);
        // Adiciona ao js
        Factory::getDocument()->addScriptOptions('nobosstheme', array(
            'fields' => $fields
        ));
                
        $html = "<span class='input-append {$this->class}' style='display: flex;'>
                    <input type='text' required='required' value='{$jsonValue->theme}' data-id='noboss-theme-selected' readonly='readonly' class='input-medium form-control' style='flex: 2; border-top-right-radius: 0;     border-bottom-right-radius: 0;'>

                    <a role='button' class='btn btn-nb btn-primary' data-id='noboss-theme-button' style='border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important;'>
                        <span class='icon-list icon-white'></span>
                        {$buttonOpenModal}
                    </a>
                </span>";
 
        $html .= "<input type='hidden' data-id='theme-modal-input' data-language='".Factory::getLanguage()->getTag()."' data-load-sample-data='{$this->getAttribute('loadsampledata', '1')}' data-load-sample-items='{$this->getAttribute('loadsampleitems', '1')}' data-modal-prefix='{$this->getAttribute('modalsprefix')}' data-load-legend='{$this->getAttribute('loadlegend', '0')}' name='{$this->name}' value='{$jsonValue->theme}' data-value='{$this->value}' id='{$this->id}' />";
        
        // Inclui o html da modal de tema, escondida
        ob_start();
        require("Nbtheme/NbthemeLayout.php");
        $html .= ob_get_clean();
        
        // Adiciona constantes para o js com replaces
        NbJsConstantsUtil::sprintfJS('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_TOKEN_SUPPORT_UPDATES_EXPIRATED', NbUrlUtil::getUrlNbExtensions());
        NbJsConstantsUtil::sprintfJS('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_TOKEN_PLAN_NOT_INLCUDED', NbUrlUtil::getUrlNbExtensions());

        // Adiciona constantes para o js sem replaces
        Text::script('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_TOKEN_NOT_VALID');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSTHEME_NOT_FIELD_LICENSE_MESSAGE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_UNREACHABLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_JSON_PARSE_LOCAL');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSTHEME_MODAL_CANCEL_BUTTON');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSTHEME_MODAL_CONFIRM_BUTTON');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSTHEME_MODAL_RESET_VALUES_LABEL');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSTHEME_MODAL_RESET_VALUES_DESC');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSTHEME_MODAL_UNAVAILABLE_SAMPLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSTHEME_MODAL_UNAVAILABLE_SAMPLE_EXPIRED');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSTHEME_MODAL_NBSERVER_CONNECTION_ERROR');
        
        // Carrega os js e css
        $wa->registerAndUseStyle('nobossmodal', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobossmodal.min.css");
        $wa->registerAndUseStyle('nobosstheme', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobosstheme.min.css");
        $wa->registerAndUseScript('nobosstheme', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobosstheme.min.js");

        return $html;
    }
    
    /**
     * Retorna um array com todos os itens cadastrados como themes no xml
     *
     * @return void
     */
    protected function getThemes(){
        $fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
		$themes   = array();

        // Percorre as options do tema no xml
		foreach ($this->element->xpath('theme') as $option){
            $requires = (string) $option['requires'];

			// Filter requirements
			if ($requires = explode(',', $requires)){
                $value = (string) $option['value'];
                $text  = trim((string) $option) != '' ? trim((string) $option) : $value;

                // Pega o numero de colunas qeu a listagem deve ter
                $columns = empty($option['columns']) ? '1' : $option['columns'];

                $disabled = (string) $option['disabled'];
                $disabled = ($disabled == 'true' || $disabled == 'disabled' || $disabled == '1');
                $disabled = $disabled || ($this->readonly && $value != $this->value);

                $checked = (string) $option['checked'];
                $checked = ($checked == 'true' || $checked == 'checked' || $checked == '1');

                $selected = (string) $option['selected'];
                $selected = ($selected == 'true' || $selected == 'selected' || $selected == '1');

                $tmp = array(
                        'value'     => $value,
                        'text'      => Text::alt($text, $fieldname),
                        'columns'   => $columns,
                        'disable'   => $disabled,
                        'class'     => (string) $option['class'],
                        'selected'  => ($checked || $selected),
                        'checked'   => ($checked || $selected)
                );
                
                // Cria um objeto de exemplo default 
                $basicSample = new \stdClass();
                $basicSample->title = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_EXAMPLE_BASIC');
                $basicSample->id = "demo_{$this->rawExtensionName}_{$value}_default";
                // Cria o array com os objetos de exemplo
                $tmp['samples'] = array(0 => $basicSample);

                // Add the option object to the result set.
                $themes[] = (object) $tmp;
            }
		}
		reset($themes);

        return $themes;
    }
}
