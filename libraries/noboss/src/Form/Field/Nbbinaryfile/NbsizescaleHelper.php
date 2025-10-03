<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field\Nbbinaryfile;

\defined('_JEXEC') or die;
// FIXME: necessario descobrir ainda se algum arquivo aponta para essa classe direto de extensoes. Se nao apontar, remover ele. Se apontar, aqui devemos estender do forms fields
/**
 * Classe que auxília na leitura de conteúdo de arquivos.
 */
class NbsizescaleHelper {
	/**
	 * Função que recebe entrada um valor de uma grandeza e a coverte para outra grandeza especificada.
	 * As grandezas permitidas são "B" para bytes, "K" para kilobytes, "M" para megabytes e "G" para gigabytes.
	 * @param double $value Valor da grandeza.
	 * @param string $scaleInput
	 * @param string $scaleOutput Grandeza de saída.
	 * @return mixed Retorna valor convertido ou false caso a grandeza de entrada ou de saída não sejam válidas.
	 */
	public static function convertScale($value, $scaleInput, $scaleOutput) {

		// Array com opções de entra e saída permitidas.
	    $scaleOptionsGranted = array("b", "k", "m", "g");

	     // Verifica se as grandezas de entrada e saída não estão entre as grandezas permitidas.
	    if(!in_array($scaleInput, $scaleOptionsGranted) || !in_array($scaleOutput, $scaleOptionsGranted)){
    		// Valor de entrada ou de saída não é válido.
    		return false;
		}

		// Transforma para minuscula grandeza informada pelo usuário.
	    $scaleInput = strtolower($scaleInput);

	    // Verifica qual a grandeza especificada na configuração.
	    switch($scaleInput) {
	        /*As grandezas são organizadas de forma descrescente, desta forma o valores são multiplicados
	        conforme necessário.*/
	        case 'g':
	            $value *= 1024;
	        case 'm':
	            $value *= 1024;
	        case 'k':
	            $value *= 1024;
	    }

	    // Valor em bytes.
	    $valueInBytes = $value;

	   	// Valor de saída.
	    $valueOutput = $valueInBytes;

	    // Transforma para minuscula grandeza informada pelo usuário.
	    $scaleOutput = strtolower($scaleOutput);

	    // Verifica a escala de saída selecionada pelo usuário.
	    switch ($scaleOutput) {
	    	case 'g':
	            $valueOutput /= 1024;
	        case 'm':
	            $valueOutput /= 1024;
	        case 'k':
	            $valueOutput /= 1024;
	    }

	    // Retorna valor de saída.
	    return $valueOutput;
	}
}
