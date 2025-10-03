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

class NbimageregenerateHelper {
	/**
	 * Função que regera imagem de acordo com limites estipulados para largura e altura
	 *
	 * @param   String 		$filePath 			Caminho completo da imagem original.
	 * @param   int 		$newWidth 			Nova largura para a imagem em px.
	 * @param   int 		$newHeight 			Nova altura para a imagem em px.
	 * @param   boolean		$onlyGreater 		Determina se imagem deve ser regerada apenas se possuir dimensão acima do estipulado.
	 * @param   string		$pathGenerateImage 	Caminho completo com nome do arquivo e extensão onde o arquivo seerá gerado.
	 * @param 	string 		$imageExtension 	Exntesão do arquivo.
	 * @return String 		Retorna o caminho da imagem, se não foi regerada retorna imagem original.
	 */
	public static function regenerateDimensions($filePath, $newWidth, $newHeight, $onlyGreater = true, $pathGenerateImage = false, $imageExtension = false) {
        
		// Refinar.
		$sharpen = true;
		// Qualidade JPG.
		$jpg_quality = 100;

		// Obtem as dimensões da imagem
		$dimensions   = GetImageSize($filePath);
		$width        = $dimensions[0];
		$height       = $dimensions[1];
        
		// Setado para regerar imagem se limites da imagem forem maior do que o estipulado e limites estão ok
		if (($onlyGreater == true) && (($width <= $newWidth) && ($height <= $newHeight))){
			// Retorna caminho original da imagem, sem regerá-la.
            return $filePath;
            
		}
        
		// Redimensiona a imagem, mantendo proporções e respeitando o limite máximo.
		if($width > $height) {
			$ratio = $height/$width;
			$newHeight = ceil($newWidth * $ratio);
		} else {
			$ratio = $width/$height;
			$newWidth = ceil($newHeight * $ratio);
		}
        
		// Inicia nova imagem com as novas dimensões
		$dst = ImageCreateTrueColor($newWidth, $newHeight);

		if($imageExtension){
			$extArquivo = $imageExtension;
		}else{
			// Funcao que explode o type do arquivo e pega o formato que fica na segunda posição
			$extArquivo = explode('.', $filePath);
			// Pega o ultimo item do array, gerado pelo explode.
			$extArquivo = end($extArquivo);
		}
       
		switch ($extArquivo) {
            case '.png':
				$src = @ImageCreateFromPng($filePath);
				imagealphablending($dst, false);
				imagesavealpha($dst,true);
				$transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
				imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
				break;
            case '.gif':
				$src = @ImageCreateFromGif($filePath);
				break;
            default:
				$src = @ImageCreateFromJpeg($filePath);
				ImageInterlace($dst, true); // Ativar o entrelaçamento (JPEG progressivo, arquivo de tamanho menor)
				break;
		}
        
		// Realiza redimensionamento na memória
		ImageCopyResampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		ImageDestroy($src);

		// sharpen the image?
		// NOTE: requires PHP compiled with the bundled version of GD (see http://php.net/manual/en/function.imageconvolution.php)
		if($sharpen == TRUE && function_exists('imageconvolution')) {
			$intSharpness = self::sharpen($width, $newWidth);
			$arrMatrix = array(
			array(-1, -2, -1),
			array(-2, $intSharpness + 12, -2),
			array(-1, -2, -1)
			);
			imageconvolution($dst, $arrMatrix, $intSharpness, 0);
		}

		// save the new file in the appropriate path, and send a version to the browser.
		if($pathGenerateImage){
			// Verifica se não foi adicionada a barra no começo do caminho fornecido.
			if(substr($pathGenerateImage, 0, 1) != DIRECTORY_SEPARATOR){
				$newFile = JPATH_ROOT . DIRECTORY_SEPARATOR . $pathGenerateImage;
			}else{
				$newFile = JPATH_ROOT . $pathGenerateImage;
			}
		}else{
			$newFile = $filePath;
		}

		switch ($extArquivo) {
			case '.png':
				$gotSaved = ImagePng($dst, $newFile);
				break;
			case '.gif':
				$gotSaved = ImageGif($dst, $newFile);
				break;
			default:
				$gotSaved = ImageJpeg($dst, $newFile, $jpg_quality);
				break;
		}

		ImageDestroy($dst);

		return $newFile;
	}

	public static function sharpen($intOrig, $intFinal) {
		$intFinal = $intFinal * (750.0 / $intOrig);
		$intA     = 52;
		$intB     = -0.27810650887573124;
		$intC     = .00047337278106508946;
		$intRes   = $intA + $intB * $intFinal + $intC * $intFinal * $intFinal;
		return max(round($intRes), 0);
	}
}
