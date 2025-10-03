<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Util\NbJsConstantsUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbItemsLoadModeField extends ListField {

    protected function getInput(){
        // Adiciona constantes padroes do JS
        NbJsConstantsUtil::addConstantsDefault();

        $classSelect = "form-select";
        
        $attr = '';

		// Initialize some field attributes.
		$attr .= !empty($this->class) ? ' class="' . $this->class . '"' : '';
		$attr .= !empty($this->size) ? ' size="' . $this->size . '"' : '';
		$attr .= $this->multiple ? ' multiple' : '';
		$attr .= $this->required ? ' required aria-required="true"' : '';
		$attr .= $this->autofocus ? ' autofocus' : '';
        
        // To avoid user's confusion, readonly="true" should imply disabled="true".
		if ((string) $this->readonly == '1' || (string) $this->readonly == 'true' || (string) $this->disabled == '1'|| (string) $this->disabled == 'true')
		{
			$attr .= ' disabled="disabled"';
		}

		// Initialize JavaScript field attributes.
		$attr .= $this->onchange ? ' onchange="' . $this->onchange . '"' : '';

        // Inicializa o campo
        $html = "<select id='itemsloadmode' name='{$this->name}' class='{$classSelect}' ".trim($attr).">";
        // Pega todos os options para colocar no array
        $options = $this->getOptions();
                
        // Caso não exista valor (eh novo registro de modulo), seta o primeiro item como selecionado
        if(empty($this->value)){
            // Campo multiplo
            if ($this->multiple){
                $this->value[] = reset($options)->value;
            }
            else{
                $this->value = empty($this->value) ? reset($options)->value : $this->value;
            }
        }

        // Cria o html das options
        foreach($options as $option){
            // Campo multiplo
            if ($this->multiple){
                $selected = in_array($option->value, $this->value);
            }
            else{
                $selected = $this->value == $option->value;
            }

            $html .= "<option value='{$option->value}' ".( $selected ? 'selected="selected"' : '')." data-subform='{$option->subform}'  data-themes='{$option->themes}'>".Text::_($option->text)."</option>";
        }
        
        // Fecha o select
        $html .= "</select>";
        
        $html .= "<div style='margin-top:10px; display: none;' class='itemsloadmode_alert_msg' id='itemsloadmode_alert_msg'>".Text::sprintf("LIB_NOBOSS_FIELD_NOBOSSITEMSLOADMODE_NOT_INCLUDED_LABEL", Text::_("NOBOSS_EXTENSIONS_URL_SITE_CONTACT"))."</div>";
                
        // passa constantes para o js
        Text::script('LIB_NOBOSS_FIELD_NOBOSSITEMSLOADMODE_NOT_INCLUDED_LABEL');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSITEMSLOADMODE_OPT_UNAVAILABLE_LICENSE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSITEMSLOADMODE_NOT_INCLUDED_ALERT_TITLE');
        Text::script('NOBOSS_EXTENSIONS_URL_SITE_CONTACT');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSITEMSLOADMODE_OPT_UNAVAILABLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSITEMSLOADMODE_NO_OPTIONS_ALERT');
        
        $app = Factory::getApplication();
        $doc = Factory::getDocument();
        $wa = $app->getDocument()->getWebAssetManager();
        
        // Carrega os js e css
        // $wa->registerAndUseStyle('nobossitemsloadmode', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobossitemsloadmode.min.css");
        $wa->registerAndUseScript('nobossitemsloadmode', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobossitemsloadmode.min.js");
        
        $this->loadTranslationConstants($doc);

        return $html;
    }

    protected function getOptions(){
        $fieldname = (!empty($this->fieldname)) ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname) : '';

        // Percorre as options do
		foreach ($this->element->xpath('option') as $option){
			// Filter requirements
			if ($requires = explode(',', (string) $option['requires'])){

                $value = (string) $option['value'];
                $text  = trim((string) $option) != '' ? trim((string) $option) : $value;

                $disabled = (string) $option['disabled'];
                $disabled = ($disabled == 'true' || $disabled == 'disabled' || $disabled == '1');
                //$disabled = $disabled || ($this->readonly && $value != $this->value);

                $checked = (string) $option['checked'];
                $checked = ($checked == 'true' || $checked == 'checked' || $checked == '1');

                $selected = (string) $option['selected'];
                $selected = ($selected == 'true' || $selected == 'selected' || $selected == '1');

                $tmp = array(
                        'value'     => $value,
                        'text'      => Text::alt($text, $fieldname),
                        'disable'   => $disabled,
                        'class'     => (string) $option['class'],
                        'selected'  => ($checked || $selected),
                        'checked'   => ($checked || $selected),
                        'plan'      => $option['plan'],
                        'themes'    => $option['themes'],
                        'subform'   => $option['subform']
                );
                // Add the option object to the result set.
                $options[] = (object) $tmp;
            }
		}
		reset($options);

        return $options;
    }

    /**
     * Inclui um objeto js com as constantes de tradução na página
     *
     * @param JDocument $doc objeto jdocument do joomla
     */
    private function loadTranslationConstants($doc){
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        if(empty($doc)){
            $doc = Factory::getDocument();
        }
        
        // Adiciona as constantes de traducao
        $wa->addInline("script", '
                if(!translationConstants){
                    var translationConstants = {};
                }
                translationConstants.'.$this->type.' = {};
                
                translationConstants.'.$this->type.'.LIB_NOBOSS_FIELD_NOBOSSTHEME_LAYOUT_CHOOSED_LABEL = "'. Text::_("LIB_NOBOSS_FIELD_NOBOSSTHEME_LAYOUT_CHOOSED_LABEL").'";
                ', array('name' => ".{$this->type}"));
    }
}

