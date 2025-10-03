<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\RadioField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Form\FormHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

// Field que permite apenas exibir imagens representativas ou ainda definir preenchimento automatico de outros campos
class NbRadioThemeField extends RadioField {
	
	protected function getInput() {
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        // Seta classe para ser inserida no fieldset
        $this->class = "nobossradiotheme";

        // Classe css adicional foi definida, acrescenta junto no fieldset
        if(!empty($this->element->attributes()->class)){
            $this->class .= " ".$this->element->attributes()->class;
        }

        // CSS e JS do field
		$wa->registerAndUseStyle('nobossradiotheme', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobossradiotheme.min.css", ['version' => '']);
        $wa->registerAndUseScript('nobossradiotheme', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobossradiotheme.min.js", ['version' => '']);
        
        return parent::getInput();
	}

    protected function getOptions(){
        $app = Factory::getApplication();
		$options   = array();

        // Adiciona altura maxima para imagem, caso definido
        $styleSize = (!empty($this->element->attributes()->maxheight)) ? ' max-height:'.$this->element->attributes()->maxheight.';' : '';

        // Adiciona largura maxima para imagem, caso definido
        $styleSize .= (!empty($this->element->attributes()->maxwidth)) ? ' max-width:'.$this->element->attributes()->maxwidth.';' : '';        

        // Adiciona altura para imagem, caso definido
        $styleSize .= (!empty($this->element->attributes()->height)) ? ' height:'.$this->element->attributes()->height.';' : '';

        // Adiciona largura para imagem, caso definido
        $styleSize .= (!empty($this->element->attributes()->width)) ? ' width:'.$this->element->attributes()->width.';' : '';    

        // Obtem o tema selecionado (quando definido) em requisicoes de modal
        $theme = $app->input->post->get('theme', '', 'STRING');

        // Percorre cada option para tratamento
		foreach ($this->element->xpath('option') as $option){
            // Setado limitacao de tema para o option e temos informacao do tema utilizado
            if((!empty($theme)) && (!empty($option['limitationtheme'])) && (!in_array($theme, explode(",", $option['limitationtheme'])))){
                continue;
            }

            // Obtem o value
            $value = (string) $option['value'];
			
            // Opcao manual
            if($value == 'manual'){
                $contentHtml = "<div class=\"nobossradiotheme__option-manual\" style='{$styleSize}'><i class='fa-solid fa-gears'></i> ".Text::_('NOBOSS_EXTENSIONS_GLOBAL_FIELD_OPT_MANUAL')."</div>";
            }
            // Imagem
            else{
                // Obtem a legenda da imagem, caso definida
                $text  = Text::_(trim((string) $option));

                // Obtem o endereco da imagem
                $srcImage = (string) $option['src'];

                // Url da imagem nao eh completa (nao possui o '://' do 'http://' ou 'https://): adiciona url base do site
                $srcImage = (!empty($srcImage) && (strpos($srcImage, '://') === false)) ? Uri::root().$srcImage : $srcImage;

                // Monta elemento html da imagem, caso src tenha sido definido
                $contentImage = (!empty($srcImage)) ? "<img src='{$srcImage}' style='{$styleSize}' />" : "";

                $attrs = "";

                // Ha mais atributos no option: organiza eles para ser colocado no html pois sao campos para preenchimento automatico
                if(!empty(current($option))){
                    foreach (current($option) as $keyAttr => $valueAttr) {
                        if(($keyAttr == 'value') || ($keyAttr == 'src')){
                            continue;
                        }

                        $attrs .= " data-{$keyAttr}=\"{$valueAttr}\"";
                    }
                }

                // Conteudo a ser exibido
                $contentHtml = "<div class=\"nobossradiotheme__option-image\" {$attrs}>{$contentImage}</div>
                                <div class=\"nobossradiotheme__option-legend\">{$text}</div>";
            }            

			$disabled = (string) $option['disabled'];
			$disabled = ($disabled == 'true' || $disabled == 'disabled' || $disabled == '1');
			$disabled = $disabled || ($this->readonly && $value != $this->value);

			$checked = (string) $option['checked'];
			$checked = ($checked == 'true' || $checked == 'checked' || $checked == '1');

			$tmp = array(
					'value'    => $value,
					'text'     => $contentHtml,
					'disable'  => $disabled,
					'class'    => (string) $option['class'],
					'checked'  => $checked,
			);

			if ((string) $option['showon']){
				$tmp['optionattr'] = " data-showon='" .
					json_encode(
						FormHelper::parseShowOnConditions((string) $option['showon'], $this->formControl, $this->group)
						)
					. "'";
			}
			
			$options[] = (object) $tmp;
		}

		reset($options);
        
        // echo '<pre>';
        // var_dump($options);
        // exit;

		return $options;

    }
}
