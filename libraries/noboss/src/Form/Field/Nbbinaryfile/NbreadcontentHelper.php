<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field\Nbbinaryfile;

use Joomla\CMS\Uri\Uri;

\defined('_JEXEC') or die;

/**
 * Classe que auxília na leitura de conteúdo de arquivos.
 */
class NbreadcontentHelper {
	/**
	 * Função que lê o conteúdo de um arquivo carregado através de um campo upload(input type="file").
	 * @param Array $fileUploaded Deve ser um array como os mesmos dados de um arquivo capturado através
	 * da variável FILES do PHP.
	 * @return string Retorna o conteúdo do arquivo em string ou false caso algum erro ocorra.
	 */
	public static function readUploadFileAsString(Array $fileUploaded) {

		// Flag que armazena se a função get_files_contes está ativa no ambiente de execução.
		$isActiveFileGetContents = function_exists('file_get_contents');

		// Pega extensão do arquivo.
		$fileExtension = strrchr($fileUploaded["name"], ".");

		// Pega mime type do arquivo de upload.
		$fileMimeType = $fileUploaded["type"];

		// Variável que armazenará o conteúdo string do arquivo.
		$fileString = false;

	 	// Verifica se a função file_get_contents está ativa.
	 	if($isActiveFileGetContents){
	 		// Pega conteúdo da imagem em string.
	 		$fileString = file_get_contents($fileUploaded["tmp_name"]);
	 	}else{
	 		// file_get_contents não está ativo.

	 		// Hirerquia de pastas para caminho de arquivos temporários.
	 		$folders = 'images/noboss_files/readcontent';

	 		// Configura caminho de diretório para arquivos temporários.
	 		$temporaryDirectory = JPATH_ROOT . DIRECTORY_SEPARATOR . $folders;

	 		// Verfica se não existe o diretório para os salvar os arquivos temporários.
			if(!file_exists($temporaryDirectory)){
				// Cria o diretório com permissão 775 e no modo recursivo(criando subdiretórios se necessário).
				mkdir($temporaryDirectory, 0775, true);
			}else{
				// Se já existe o diretório altera permissão para garantir que os arquivos serão salvos.
				chmod($temporaryDirectory, 0775);
			}

			// Caminho completo onde o arquivo será gerado.
			$dest = $temporaryDirectory . DIRECTORY_SEPARATOR . $fileUploaded["name"];

			/* Tenta mover o arquivo para uma área acessível via requisição HTTP e captura resultado. */
			$isSuccessFileMove = move_uploaded_file($fileUploaded["tmp_name"], $dest);

			// Verifica se o arquivo foi movido com sucesso.
			if($isSuccessFileMove != false){
				// Monta URL temporária de acesso ao arquivo.
				$urlFileTemp =  URI::root() . $folders . DIRECTORY_SEPARATOR . $fileUploaded["name"];

				// Pega o conteúdo do arquivo em string.
				$fileString = self::cURLReadyFile($urlFileTemp);

				// Remove arquivo do diretório temporário.
				unlink($dest);
			} else {
				// Arquivo não pode ser movido.
				return false;
			}

		}

		// Retorna caminho string do arquivo.
		return $fileString;
	}

	/**
	 * Função que lê arquivo como string a partir de uma URL.
	 * @param string $fileUrl URL onde encontra-se o arquivo.
	 * @return string Devolve o conteúdo da arquivo em uma string.
	 */
	public static function cURLReadyFile($fileUrl)
	{
		// Inicia curl.
		$cUrl = curl_init();
		// Informa a url de execução.
		curl_setopt ($cUrl, CURLOPT_URL, $fileUrl);
		// Informa que o curl deve esperar pelo carregamento do arquivo e ignorar o timeout.
		curl_setopt ($cUrl, CURLOPT_CONNECTTIMEOUT, 0);
		// Informa que queremos capturar o retorno de execução.
		curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, 1);
		// Informa que deve ser utilizada "transferência binária" segura.
		curl_setopt($cUrl, CURLOPT_BINARYTRANSFER, 1);
		// Lê o conteúdo do arquivo em string.
		$stringImage = curl_exec($cUrl);
		// Fecha recurso de curl.
		curl_close($cUrl);
		// Retorna string do arquivo.
		return $stringImage;
	}

}
