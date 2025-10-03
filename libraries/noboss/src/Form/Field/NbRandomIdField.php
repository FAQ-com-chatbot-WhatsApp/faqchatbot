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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbRandomIdField extends FormField {

    protected function getInput() {
		$valueGet = $this->__get('value');

		// Caso nao tenha valor definido, gera um valor randomico
        $value = empty($valueGet) ? uniqid() : $valueGet;
        
		// Seta o valor
        $this->__set('value', $value);

        $html = '';

       
        // Definido para exibir em campo text permitindo edicao 
        if(!empty($this->element['input']) && $this->element['input'] == 'text'){

            if(!empty($this->pattern)){
                $this->pattern = "pattern='{$this->pattern}'";
            }
            else{
                $this->pattern = '';
            }

            $html .=  "</div><div><input type='text' 
                            name='{$this->name}'
                            id='{$this->id}'
                            value='{$value}'
                            required='required'
                            aria-required='true'
                            aria-invalid='false'
                            class='required form-control'
                            maxlength='20'
                            {$this->pattern}
                        />";
        }
        // Exibir em input hidden
        else{
            $html .=  "<input type='hidden' 
                            name='{$this->name}'
                            id='{$this->id}'
                            value='{$value}'
                        />";
        }

		return $html;
    }

    protected function getLabel(){
        // Definido para exibir em campo text permitindo edicao 
        if(!empty($this->element['input']) && $this->element['input'] == 'text'){
            return parent::getLabel();
        }
        // Exibir em input hidden
        else{
            return;
        }
    }
}
