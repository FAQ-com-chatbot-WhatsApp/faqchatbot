<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\NumberField;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbMaxExecutiontimeField extends NumberField {

	protected function getInput() {
		// Valida e parâmetro "min" especificado para o campo.
		$this->min = ($this->element['min']) ? $this->element['min'] : 1;

		// Se o valor é vazio.
		if(empty($this->value)){
			if(!is_numeric($this->value)){
				$this->value = ini_get('max_execution_time');
			}
		}

   	// Pega valor da configuração de "max_execution_time" do PHP.ini.
   	$maxExecutionTime =  ini_get('max_execution_time');
 		// Remove espaços do valor.
    $maxExecutionTime = trim($maxExecutionTime);

 		// Defini valor máximo para o campo
		$this->max = $maxExecutionTime;

		// Chama função getInput da classe pai.
		return parent::getInput();
	}
}
