<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field\Nbbinaryfile;

use Noboss\Library\Form\Field\Nbbinaryfile\NbsizescaleHelper;
use Noboss\Library\Form\Field\Nbbinaryfile\NbimageregenerateHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * Classe para capturar binário de um arquivo.
 */
class NbbinaryfileHelper {
	/**
	 * Método que pega binário de um arquivo
	 * @return array Retorna lista com dados do arquivo.
	 */
	public static function getBinaryFromFile() {
		// Pega contexto da aplicação joomla.
		$app = Factory::getApplication();
		$post = $app->input->post;
		// Pega input da requisição.
		$input = $app->input;
		// Pega arquivos do POST.
		$postFiles = $input->files;
		$files = $postFiles->getArray();

		// Verifica se a função não recebeu o arquivo.
		if(empty($files['formFile'])){
			/* Para a execução do script pois o PHP negou o arquivo, quando isso ocorre o input não recebe
			nehuma variável. */
			// Informa  erro de stream de arquivo.
			$dataReturn["error"] = Text::_("JLIB_FILESYSTEM_ERROR_STREAMS_NOT_UPLOADED_FILE");
			exit(json_encode($dataReturn));
		}
        
		// Cria array com informações do arquivo.
		$options = new \stdClass();
		// Pega o input da requisição.
		$options->input = $input;
		// Pega arquivo da requisição.
		$options->file = $files['formFile'];
		// Pega nome temporário do arquivo
		$options->fileTempName = $options->file["tmp_name"];
		// Variável que armazena se a função get_files_contes está ativa.
		$options->isActiveFileGetContents = function_exists('file_get_contents');
		// Pega extensão do arquivo.
		$options->fileExtension = strrchr($options->file["name"], ".");
		// Cria um id aleatório para o arquivo.
		$options->fileId = mt_rand(0, 9999);
		// Pega json com parâmetros do XML do campo.
		$accept = $post->get("accept", '', 'RAW');

		// Pega json com parâmetros do XML do campo.
		$jsonDataParamsField = $post->get("dataParamsField", '', 'STRING');
		// Realiza decode dos parâmetros do XML do campo e adiciona as opções.
		$options->dataParamsField = json_decode($jsonDataParamsField);
		
		// Pega parâmetros para a requisição.
		$params = self::getParams($post->get("extension", '', 'STRING'), $options);
		        
		// Verifica se o arquivo não tem tamanho máximo válido.
		if(!self::validateMaxFileSizeGranted($options->file["size"], $params,)){
			$dataReturn["error"] = $options->dataParamsField->msg_error_max_file_size;
		}

		// Verifica se o arquivo não tem extensão válida.
		if(!self::validateFileExtensionGranted($options->fileExtension, $params, $accept)){
			$dataReturn["error"] = $options->dataParamsField->msg_error_extension_file_granted;
		}

		// Se não existem erros.
		if(!isset($dataReturn["error"]))
		{

			// Pega o tipo do arquivo.
			$fileType = explode("/", $options->file["type"]);
			$fileType = $fileType[0];

			// Verifica se o arquivo é do tipo image.
			if($fileType == "image") {
                
				// Realiza leitura de um arquivo do tipo imagem.
                $dataBinaryFile = self::getBinaryFromImageFile($options, $params);
                
				// Adiciona ao dados de retorno atributo src para tag <img>.
                $dataReturn["imageSrcDisplay"] = $dataBinaryFile["imageSrcDisplay"];
                
			}else{
				// Realiza leitura de um arquivo genérico.
				$dataBinaryFile = self::getBinaryFromGenericFile($options, $params);
			}

			// Verifica se já existe a informação de mime type do arquivo.
			if(array_key_exists("mimeType", $dataBinaryFile)){
				// Pega o mime type existente.
				$mimeType =  $dataBinaryFile["mimeType"];
			}else{
				// Pega o mime type do arquivo.
				$mimeType = self::getMimeTypeFile($options,$dataBinaryFile["pathNewTempFile"]);
			}

			// Verifica se deve excluir arquivo do diretório temporário.
			if($options->isActiveFileGetContents == false){
			 	self::removeImageTempDirectory(basename($dataBinaryFile["urlImageTemp"]));
			}

			// Conteúdo do arquivo codificado em base 64.
			$dataReturn["stringFile"] 	= base64_encode($dataBinaryFile["stringFile"]);

			// Mime do arquivo.
            $dataReturn["mimeTypeFile"] = $mimeType;
            
		}
        
		// Retorna array para requisição.
		exit(json_encode($dataReturn));
	}

	/**
	 * Método que valida tamanho máximo permitido para os arquivos verificando se as configurações 
	 * não execedem as configurações do PHP.
	 * @param configSizeLimitUploadFileInBytes double Valor da configuração de limite máximo para upload 
	 * de arquivos.
	 * @return string Retorna o valor do tamanho máximo permitido para os arquivos.
	 */
	public static function getMaxFileSizeGranted($configSizeLimitUploadFileInBytes)
	{
  		// Pega valor da configuração de "limite de upload de arquivos" do PHP.ini.
   		$phpUploadMaxSize =  ini_get('upload_max_filesize');
   		// Remove espaços do valor.
	    $phpUploadMaxSize = trim($phpUploadMaxSize);
	    // Pega o valor númerico removendo o último caracter da configuração.
	    $valueUploadMaxSize = substr($phpUploadMaxSize, 0, -1);
	    // Pega último caractere da configuração que corresponde a grandeza de entrada.
	    $scaleInput = strtolower($phpUploadMaxSize[strlen($phpUploadMaxSize)-1]);

   		// Limite de upload do php.ini em bytes.
		$phpPostMaxSizeInBytes = NbsizescaleHelper::convertScale($valueUploadMaxSize, $scaleInput, 'b');

		// Verifica se a configuração de upload do php.ini é menor que a configuração parametrizada.
		if($phpPostMaxSizeInBytes < $configSizeLimitUploadFileInBytes){
			// Utiliza a configuração do php para validar.
			$maxSizeFileInBytes = $phpPostMaxSizeInBytes;
		}else{
			// Utiliza a configuração do parâmetro para validar.
			$maxSizeFileInBytes = $configSizeLimitUploadFileInBytes;
		}

		// Retorna o valor máximo permitido para o arquivo.
		return $maxSizeFileInBytes;
	}

	/**
	 * Método que válida se o campo possui extensão válida. Se a configuração de file_extensions_granted_file
	 * não existe o método considera que a extensão do arquivo é válida.
	 * @param string $fileExtension Extensão do arquivo. Exemplo: ".jpg".
	 * @param array $params Lista de parâmetros para o tratamento de arquivos.
	 * @return boolean Retorna true se a extensão for válida ou false caso não seja permitida.
	 */
	public static function validateFileExtensionGranted($fileExtension, $params, $accept)
	{
		if(!empty($params->get('file_upload_extensions_granted'))){
			$listFileExtensionGranted = $params->get('file_upload_extensions_granted');
		}
		else if(!empty($accept)){
			$listFileExtensionGranted = explode(",", $accept);
		}

		// Se a configuração de extensões permitidas for vazia.
		if(empty($listFileExtensionGranted)){
			// Retorna verdadeiro pois não foi informado um limitação de extensões.
			return true;
		}

		if(!empty(in_array(strtolower($fileExtension), array_map("strtolower", $listFileExtensionGranted)))){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Método que verifica se o tamanho máximo do arquivo é permitido.
	 * @param string $fileSizeInBytes Tamanho do arquivo em bytes.
	 * @param array $params Lista de parâmetros para o tratamento de arquivos.
	 * @return boolean Retorna true se a extensão for válida ou false caso não seja permitida.
	 */
	public static function validateMaxFileSizeGranted($fileSizeInBytes, $params)
	{

		/* Pega configuração de limite máximo de arquivos se não encotrar
		pegar valor default (maximo permitido pelo PHP). */
		$configSizeLimitUploadFileInBytes = $params->get('size_limit_upload_file', self::getMaxFileSizeGranted($fileSizeInBytes));

		// Pega valor máximo para upload de arquivos.
		$maxFileSizeGranted = self::getMaxFileSizeGranted($configSizeLimitUploadFileInBytes);

		// Verifica se o arquivo tem tamanho maior que o permitido.
		if($maxFileSizeGranted < $fileSizeInBytes){
			// Arquivo não é válido.
			return false;
		}else{
			// Arquivo é válido.
			return true;
		}
	}

	/**
	 * Método que pega o mimetype do arquivo.
	 * @param array $options Lista informações da requisição.
	 * @param string $pathNewTempFile Caminho do arquivo.
	 * @return string Retorna mimetype do arquivo.
	 */
	public static function getMimeTypeFile($options, $pathNewTempFile)
	{
		// Verifica se existe a função "mime_content_type".
		if(function_exists("mime_content_type")){
			// Pega mime type do arquivo.
		 	$mime = mime_content_type($pathNewTempFile);
		}else{
		 	// Retorna o mime type do arquivo original do upload.
		 	$mime = $options->file["type"];
		}

		return $mime;
	}

	/**
	 * Função que lê imagem como string apartir de uma URL.
	 * @param string $imageUrl URL onde encontra-se a imagem.
	 * @return string Devolve o conteúdo da imagem em uma string.
	 */
	public static function cURLReadyFile($imageUrl){
		// Inicia curl.
		$cUrl = curl_init();
		// Informa a url de execução.
		curl_setopt ($cUrl, CURLOPT_URL, $imageUrl);
		// Informa que o curl deve esperar pelo carregamento da imagem e ignorar o timeout.
		curl_setopt ($cUrl, CURLOPT_CONNECTTIMEOUT, 0);
		// Informa que queremos capturar o retorna de execução.
		curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, 1);
		// Informa que deve ser utiliza "transferência binária" segura.
		curl_setopt($cUrl, CURLOPT_BINARYTRANSFER, 1);
		// Pega imagem em arquivo.
		$stringFile = curl_exec($cUrl);
		// Fecha recurso de curl.
		curl_close($cUrl);

		return $stringFile;
	}

	/**
	 * Função que remove uma imagem do diretório temporário.
	 * @param string $imageName Nome da imagem(com exntesão) a ser removida.
	 * @return boolean Retorna true se o arquivo foi deletado ou false caso um erro tenha ocorrido.
	 */
	public static function removeImageTempDirectory($imageName){
		// Caminho do diretório para imagens temporárias.
		$directoryImage = JPATH_ROOT . '/tmp/noboss_to_binary/';

		// Remover arquivo do diretório.
		unlink($directoryImage . $imageName);
	}

	/**
	 * Método que pega os parâmetros para o tratamento dos arquivos.
	 * @param array $options Lista informações da requisição.
	 * @param string $extensionName Nome da extensão a carregar.
	 * @return mixed Retorna os parâmetros conforme o valor da variável "extension"(componente ou
	 * módulo), se essa variável não existir será considerado que os parâmetros estão na própria
	 * requisição(POST e GET).
	 */
	public static function getParams($extensionName, $options = false ){
		// Cria parametro com valor nulo.
		$params = null;

		// Verifica foi informado o parâmetro de extensão na requisição.
		if ($extensionName) {
			// Separa o valor da extensão pelo caractere "_" (underline).
			$explodedExtension = explode("_", $extensionName);
			// Pega o prefixo da extensão.
			$prefixExtension = $explodedExtension[0];

			// Verifica o prefixo da extensão.
			switch ($prefixExtension) {

				// É um prefixo de componente.
				case "com":
					// Pega parâmetros do componente.
					$params = ComponentHelper::getParams($extensionName);
					break;

				// É um prefixo de módulo.
				case "mod":
					// Pega o módulo.
					$module = ModuleHelper::getModule($extensionName);
					// Pega os parâmetros do módulo.
					$params = new Registry($module->params);
					break;

				// Não é uma extensão válida.
				default:
					// Não retorna nenhum parâmetro.
					return $params;
					break;
			}

		} else {
			$params = $options->input;
		}

		return $params;
	}

	/**
	 * Método que lê o binário de um arquivo do tipo imagem.
	 * @param array $options Lista informações da requisição.
	 * @param array $params Lista de parâmetros para o tratamento do arquivo de imagem.
	 * @return array Retorna dados do arquivo.
	 */
	private static function getBinaryFromImageFile($options, $params){
		// Variáveis que armazenam os dados de retorno..
		$dataImageFile = array();
		$stringFile;
		$pathNewTempFile;
		$urlImageTemp = '';
        
		// Pega configuração "restringir dimensões" se não existe pega 0 por padrão.
		$resizeDimensions = $params->get("restrict_dimensions", "0");

		// Verificar se a imagem deve ser redimensionada.
		if($resizeDimensions){
            
			// Pega configurações de dimensões.
			$maxWidth = $params->get("max_width");
			$maxHeight = $params->get("max_height");

			// Verifica se existe a função file_get_contents.
			if($options->isActiveFileGetContents){
                
				// Regera a imagem.
				$pathNewTempFile = NbimageregenerateHelper::regenerateDimensions($options->fileTempName, $maxWidth, $maxHeight, true, false, $options->fileExtension);
                
                // Pega conteúdo da imagem em string.
                $stringFile = file_get_contents($pathNewTempFile);
                
			} else {
				// Caminho do diretório para imagens temporárias.
				$directoryImage = JPATH_ROOT . '/tmp/noboss_to_binary';

				// Verifica se não existe o diretório para criar a imagem.
				if(!file_exists($directoryImage)){
					// Cria o diretório.
					mkdir($directoryImage, 0775);
				} else {
					// Altera permissão do diretório da imagem.
					chmod($directoryImage, 0775);
				}
                
				// Cria um id aleatório para o arquivo.
				$options->fileId = mt_rand(0, 9999);

				// Caminho completo onde o arquivo será gerado.
				$dest = "tmp" . DIRECTORY_SEPARATOR . "noboss_to_binary"
					. DIRECTORY_SEPARATOR . "binaryFile_" . $options->fileId . $options->fileExtension;

				// Cria imagem temporária em "tmp/noboss_to_binary".
				$pathNewTempFile = NbimageregenerateHelper::regenerateDimensions($options->file["tmp_name"], $maxWidth, $maxHeight, true, $dest, $options->fileExtension);

				// Se foto não precisou ser redimensionada.
				if($pathNewTempFile == $options->file["tmp_name"]){
					$pathNewTempFile = JPATH_ROOT . DIRECTORY_SEPARATOR . $dest;
					// Move arquivo para a pasta temporária.
					move_uploaded_file($options->file["tmp_name"], $pathNewTempFile);
				}

				// Monta URL da imagem temporária.
				$urlImageTemp =  URI::root() . $dest;

				// Pega o conteúdo da imagem em string.
				$stringFile = self::cURLReadyFile($urlImageTemp);
			}
		} else {
			// Não redimensiona a imagem apenas pega o binário da imagem.
			$dataImageFile = self::getBinaryFromGenericFile($options);
			$pathNewTempFile = $dataImageFile["pathNewTempFile"];
			$stringFile = $dataImageFile["stringFile"];
		}

		// Pega o mime type do arquivo.
		$mimeType = self::getMimeTypeFile($options, $pathNewTempFile);

		// Monta atributo src para tag <img>.
		$imageSrcDisplay = 'data:' . $mimeType . ';base64,' . base64_encode($stringFile);

		// Pega novo caminho do arquivo.
		$dataImageFile["pathNewTempFile"] = $pathNewTempFile;
		// Pega string do arquivo de imagem.
		$dataImageFile["stringFile"]  = $stringFile;
		// Pega URL temporária do arquivo.
		$dataImageFile["urlImageTemp"]  = $urlImageTemp;
		// Atributo "src" para tag "img".
		$dataImageFile["imageSrcDisplay"] = $imageSrcDisplay;
		// Pega mime type da imagem.
		$dataImageFile["mimeType"] = $mimeType;

		return $dataImageFile;
	}

	/**
	 * Método que lê o binário de um arquivo genérico.
	 * @param array $options Lista de opções da requisição.
	 * @return array Retorna dados do arquivo.
	 */
	private static function getBinaryFromGenericFile($options){

		// Variáveis que armazenam os dados de retyorno.
		$dataImageFile = array();
		$stringFile;
		$pathNewTempFile;
		$urlImageTemp = "";

		// Verifica se existe a função file_get_contents.
		if($options->isActiveFileGetContents){
			// Pega conteúdo da imagem em string.
			$stringGenericFile = file_get_contents($options->fileTempName);
			$pathNewTempFile = $options->file['tmp_name'];
		}else{
			// Caminho do diretório para imagens temporárias.
			$directoryImage = JPATH_ROOT . '/tmp/noboss_to_binary';

			// Verfica se não existe o diretório para criar a imagem.
			if(!file_exists($directoryImage)){
				// Cria o diretório.
				mkdir($directoryImage, 0775);
			}
			else{
				// Altera permissão do diretório da imagem.
				chmod($directoryImage, 0775);
			}

			// Caminho completo onde o arquivo será gerado.
			$dest = "tmp" . DIRECTORY_SEPARATOR . "noboss_to_binary"
			. DIRECTORY_SEPARATOR . "binaryFile_" . $options->fileId . $extension;

			// Se foto não precisou ser redimensionada.
			$pathNewTempFile = JPATH_ROOT . DIRECTORY_SEPARATOR . $dest;
			// Move arquivo para a pasta temporária.
			move_uploaded_file($options->file["tmp_name"], $pathNewTempFile);

			// Monta URL da imagem temporária.
			$urlImageTemp =  URI::root() . $dest;

			// Pega o conteúdo da imagem em string.
			$stringGenericFile = self::cURLReadyFile($urlImageTemp);
		}

		// Pega novo caminho do arquivo.
		$dataGenericFile["pathNewTempFile"] = $pathNewTempFile;
		// Pega string do arquivo de imagem.
		$dataGenericFile["stringFile"]  = $stringGenericFile;
		// Pega URL temporária do arquivo.
		$dataGenericFile["urlImageTemp"]  = $urlImageTemp;

		return $dataGenericFile;
	}

	/**
	 * Método que cria um arquivo temporário na área do PHP.
	 * @param string $name Nome do arquivo.
	 * @param string $content Conteúdo do arquivo.
	 * @return string Retorna o caminho do arquivo na área temporária do PHP.
	 */
	public static function temporaryFile($name, $content){
	    $file = DIRECTORY_SEPARATOR .
	            trim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) .
	            DIRECTORY_SEPARATOR .
	            ltrim($name, DIRECTORY_SEPARATOR);

	    file_put_contents($file, $content);

	    register_shutdown_function(function() use($file) {
	        unlink($file);
	    });

	    return $file;
	}

	/**
	 * Método que disponibiliza visualização genérica do arquivo, a exibição é delegada ao navegador
	 * que fica responsável pela exibição do conteúdo do arquivo.
	 * @return void
	 */
	public static function viewFile(){
		// Pega contexto da aplicação Joomla.
		$app = Factory::getApplication();
		// Pega input da requisição.
		$input = $app->input;
		$post = $app->input->post;

		// Pega dados do arquivo.
        $fileUploadedData = $post->get("fileUploadedData", '', 'STRING');
        
        // Recebido dados em um unico input fileUploadedData
        if(!empty($fileUploadedData)){
            // Realiza decode JSON dos dados.
            $fileUploadedData = json_decode($fileUploadedData);
                    
            // Pega string do arquivo.
            $stringFile = base64_decode($fileUploadedData->stringFile);

            // Pega mime type do arquivo.
            $mimeTypeFile = $fileUploadedData->mimeTypeFile;
        }
        // Tenta obter os dados em campos separados
        else{
            // Pega string do arquivo.
            $stringFile = base64_decode($post->get("stringFile", '', 'STRING'));

            // Pega mime type do arquivo.
            $mimeTypeFile = $post->get("mimeTypeFile", '', 'STRING');
        }
		
		// Pega extensão do arquivo.
		$extensionFile = explode("/", $mimeTypeFile);
		$extensionFile = array_pop($extensionFile);

		// Cria um nome temporário único para o arquivo.
		$tempName = uniqid("fileUploadedData", true);

		$fileTempPath = self::temporaryFile($tempName, $stringFile);

		// Configurar header para o navegador.
		header('Cache-Control: public');
		header('Content-Type: ' . $mimeTypeFile);
		header('Content-Length: ' . filesize($fileTempPath));
		header('Content-Disposition: inline; filename="view_file.'. $extensionFile .'"');
		readfile($fileTempPath);

		// Realiza exit do script.
		exit();
	}
}
