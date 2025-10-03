<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field\Nbbinaryfile;

use Noboss\Library\Form\Field\Nbbinaryfile\NbmimetypeHelper;
use Noboss\Library\Form\Field\Nbbinaryfile\NbimagereprocessHelper;

\defined('_JEXEC') or die;

class NbvalidatorHelper {
	/**
	 * Funcão para validar o se a extensão do arquivo é válida
	 *
	 * @param   Array 	$file 					Array com as informações do arquivo
	 * @param   String 	$allowedExtensions 		Extensões permitidas para o arquivo
	 * @return  mixed  	Retorna true se passou na validação, ou um array com o nome do elemento e a mensagem de erro
	 */
	public static function validateExtensionFile($file, $allowedExtensions){
		$hasError = false;
		//Funcao que explode o type do arquivo e pega o formato que fica na segunda posição
		$extArquivo = explode('.', $file["name"]);
		//Pega o ultimo item do array, gerado pelo explode
		$extArquivo = end($extArquivo);
		
		//Função que explode o texto e faz um array com os formatos supportados
		$extConfig = explode(',', str_replace('.', '', str_replace(' ', '', $allowedExtensions)));

		//Caso não tenha sido setado nenhuma limitação de extensão retorna true
		if (strlen($extConfig[0]) == 0 ) {
			return true;
		}

		// Verifica se a extensão e o tipo estão de acordo
		if($file['type'] !== NbmimetypeHelper::findMimeType($extArquivo)){
			$hasError = true;
		}

		//Verifica se está entre as extensoes permitidas
		if(!in_array($extArquivo, $extConfig)){
			$hasError = true;
		}

		//Verifica se é uma imagem
		if(explode('/', $file['type'])[0] == "image"){
			if (!self::recreatesImage($file)) {
				$hasError = true;
			}
		}

		// Validação da extensão encontrou erro
		if($hasError){
			return false;
		}

		return true;
	}

	/**
	 * Funcão para recriar a imagem, garantindo assim que códigos maliciosos sejam removidos
	 *
	 * @param   Array 	$file 					Array com as informações do arquivo
	 * @return  mixed  	Retorna imagem regerada
	 */
	public static function recreatesImage($file){
		$full = $file["tmp_name"];
		$aux = explode('/', $file["tmp_name"]);
		$filename = $aux[count($aux)-1];
		$path = str_replace($filename, "", $full);

		$reprocess = new NbimagereprocessHelper($full, $path, $filename."_new");

		return $reprocess->setAdapter('GD')->reprocess();
	}
}
