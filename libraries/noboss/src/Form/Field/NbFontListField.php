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
// use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbFontListField extends ListField{

	protected function getLabel(){
		return "<label id='{$this->id}' for='{$this->id}_label' class='hasPopover' data-content='' data-original-title='".Text::_("LIB_NOBOSS_FIELD_NOBOSSFONTLIST_FONT_LABEL")."' aria-invalid='false'>".Text::_("LIB_NOBOSS_FIELD_NOBOSSFONTLIST_FONT_LABEL")."</label>";
	}

    protected function getInput(){
		$app = Factory::getApplication();
		$wa = $app->getDocument()->getWebAssetManager();

		$classSelect = "form-select";

		$value = json_decode($this->value, true);
		// Verifica se não tem valor e aplica os valores default
		if(empty($value)){
			$value = array(
				'fontfamily' => (!empty($this->element->attributes()->defaultfont[0])) ? $this->element->attributes()->defaultfont[0] : '',
				'externalLinked' => (!empty($this->element->attributes()->defautexternalurl[0])) ? $this->element->attributes()->defautexternalurl[0] : '',
				'fontStyle' => (!empty($this->element->attributes()->defaultfontstyle[0])) ? $this->element->attributes()->defaultfontstyle[0] : ''
			);
        }

        $attr = '';
        
        // To avoid user's confusion, readonly="true" should imply disabled="true".
		if ((string) $this->readonly == '1' || (string) $this->readonly == 'true' || (string) $this->disabled == '1'|| (string) $this->disabled == 'true')
		{
			$attr .= ' disabled="disabled"';
		}

		// Carrega o arquivo de tradução da library noboss   
		Factory::getLanguage()->load('lib_noboss', JPATH_SITE, null, false, true);
		$html = array();

		$html[] = '<select id="' . $this->id . '_select_font_list" '.trim($attr).' name="' . $this->fieldname . '_select_font_list" data-full-name="'.$this->fieldname.'" data-id="font_list_field" class="'.$classSelect.' '.$this->class.'">';
		$html[] = '<option '.(isset($value['fontfamily']) && $value['fontfamily'] == "inherit" ? "selected" : "").' value="inherit">'.Text::_('LIB_NOBOSS_FIELD_NOBOSSFONTLIST_INHERIT').'</option>';
		$html[] = '<option '.(isset($value['fontfamily']) && $value['fontfamily'] == "external_linked" ? "selected" : "").' value="external_linked">'.Text::_('LIB_NOBOSS_FIELD_NOBOSSFONTLIST_EXTERNAL_LINK').'</option>';
		
		$path = JPATH_LIBRARIES.'/noboss/src/Form/Field/assets/fonts/';
		//Percorre os valores inserindo uma option para cada um deles
		$files = scandir($path);

		$fontFormats = array(
			'ttf'  => 'truetype',
			'otf'  => 'opentype',
			'woff' => 'woff',
			'woff2' => 'woff2',
			'svg' => 'svg',
			'eot' => 'embedded-opentype'
		);

		// Remove as duas primeira posições do array que são . e ..
		array_shift($files);
		array_shift($files);
		// Percorre cada fonte na p
		$style = '';
		foreach ($files as $font) {
			//Monta o caminho até a fonte
			$fontPath = Uri::root().'libraries/noboss/src/Form/Field/assets/fonts/'.$font;
			// Remove a extensão do arquivo
			$filename = substr($font, 0 , (strrpos($font, ".")));
			// Pega a extensão do arquivo
			$fileExt = pathinfo($font, PATHINFO_EXTENSION);
			// Monta o Font Family a partir do nome do arquivo
			$fontName = str_replace('-', ' ', $filename);
			$rawFontNameArray = explode('_', $fontName);
			// Monta o nome da fonte sem o modificador que será exibido na option
			$rawFontName = $rawFontNameArray[0];
			// Remove o separador underline
			$fontName = str_replace('_', ' ', $filename);
			// Verifica se é o valor selecionado ou não
			$selected = $value['fontfamily'] == $font ? 'selected' : '';
			// Monta  o option e marca o valor já selecionado
			$html[] = "<option {$selected} value='{$font}' style='font-family: {$fontName}'>{$rawFontName}</option>";
			// Adiciona a tag style que importa a fonte
			$style .= "@font-face { font-family: '{$fontName}'; src: url('{$fontPath}') format('{$fontFormats[$fileExt]}')}\n";
		}
		// Fecha o select
		$html[] = "</select>";
		$html[] = "<input type='hidden' data-full-name='{$this->fieldname}' name='{$this->name}' value='{$this->value}'/>";
		$html[] = "<style>{$style}</style>";

		$html[] = '</div></div>';		
        
        $parentClass = ($this->element->attributes()->parentclass) ? $this->element->attributes()->parentclass : '';

		$html[] = "<div class='control-group ".$parentClass."' style='display: none;'>";
			$html[] = "<div class='control-label'>
						<label id='{$this->fieldname}_external_url' for='{$this->id}_font_external_url' class='hasPopover' data-content='".Text::_("LIB_NOBOSS_FIELD_NOBOSSFONTLIST_FONT_EXTERNAL_URL_DESC")."' data-original-title='".Text::_("LIB_NOBOSS_FIELD_NOBOSSFONTLIST_FONT_EXTERNAL_URL_LABEL")."' aria-invalid='false'>".Text::_("LIB_NOBOSS_FIELD_NOBOSSFONTLIST_FONT_EXTERNAL_URL_LABEL")."</label>
					   </div>";
			$html[] = "<div class='controls'>";
				$html[] = "<input type='url' data-full-name='{$this->fieldname}' name='{$this->fieldname}_font_external_url' id='{$this->id}_font_external_url' value='{$value['externalLinked']}' data-id='font_list_field_font_external_url' class='form-control'>";
			$html[] = "</div>";
		$html[] = "</div>";
			
		$html[] = "<div class='control-group ".$parentClass."' style='display: none;'>";
			$html[] = "<div class='control-label'>
						<label id='{$this->fieldname}_font_style' for='{$this->id}_font_style' class='hasPopover' data-content='".Text::_("LIB_NOBOSS_FIELD_NOBOSSFONTLIST_FONT_STYLE_DESC")."' data-original-title='".Text::_("LIB_NOBOSS_FIELD_NOBOSSFONTLIST_FONT_STYLE_LABEL")."' aria-invalid='false'>".Text::_("LIB_NOBOSS_FIELD_NOBOSSFONTLIST_FONT_STYLE_LABEL")."</label>
					  </div>";
			$html[] = "<div class='controls'>";
				$html[] = "<select id='{$this->id}_font_style' '.trim($attr).'  data-full-name='{$this->fieldname}' name='{$this->fieldname}_font_style' data-id='font_list_field_font_style' class='{$classSelect}'>
					<option value='{$value['fontStyle']}' selected >{$value['fontStyle']}</option>
				</select>";

		$wa->registerAndUseScript('nobossfontlist', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobossfontlist.min.js");

	  	return implode('', $html);
  	}
}
