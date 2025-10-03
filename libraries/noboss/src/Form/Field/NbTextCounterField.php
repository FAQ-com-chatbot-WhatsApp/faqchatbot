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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbTextCounterField extends TextareaField {

    protected function getInput(){
        $html = parent::getInput();
        $showCharacters = $this->element->attributes()->showcharacters;
        $limit = $this->element->attributes()->limit;
        $autoResizeText = $this->element->attributes()->autoresizetext;

        //verifica se o parametro para mostrar o número de caracteres está setado
        if($showCharacters != null){
            //caso setado, adiciona o texto para cada caso
            if($showCharacters == "remaining"){
                $text = Text::_("LIB_NOBOSS_FIELD_NOBOSSTEXTCOUNTER_REMAINING_CHARACTERS");
            }else if ($showCharacters == "typed"){
                $text = Text::_("LIB_NOBOSS_FIELD_NOBOSSTEXTCOUNTER_TYPED_CHARACTERS");
            //caso seja um valor invalido
            }else{
                $showCharacters = "";
                $text = "";
            }
            //e o valor do parametro no elemento html
            $showCharacters = "data-showcharacters='{$showCharacters}'";
            //classe do contador
            $counterClass = "";
        //caso não, deixa as variaveis vazias
        }else{
            $showCharacters = "";
            $text = "";
            //classe do contador
            $counterClass = "hidden";
        }

        if($autoResizeText == true){
            $autoResizeText = "data-autoresizetext='true'";
        }else{
            $autoResizeText = '';
        }

        //monta o elemento html que mostra a contagem de caracteres
        $html .= "<div class='nobosstextcounter-wrapper {$this->class}' data-limit='{$limit}' {$showCharacters} {$autoResizeText}>";
        $html .= "<span class='{$counterClass}'>{$text}:</span>";
        $html .= "<span class='nobosstextcounter'></span>";
        $html .= "</div>";

        $app = Factory::getApplication();
        $doc = Factory::getDocument();
        $wa = $app->getDocument()->getWebAssetManager();
        
		$wa->registerAndUseScript('nobosstextcounter', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobosstextcounter.min.js");
		$wa->registerAndUseStyle('nobosstextcounter', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobosstextcounter.min.css");

        // Adiciona as constantes de traducao
        $wa->addInline("script", '
                if(!translationConstants){
                    var translationConstants = {};  
                }
                translationConstants.textcounter = {};
                
                translationConstants.textcounter.LIB_NOBOSS_FIELD_NOBOSSTEXTCOUNTER_CHARACTERS_LIMIT_REACHED = "'. Text::_("LIB_NOBOSS_FIELD_NOBOSSTEXTCOUNTER_CHARACTERS_LIMIT_REACHED").'";
                ', array('name' => ".textcounter"));

        return $html;
    }
}
