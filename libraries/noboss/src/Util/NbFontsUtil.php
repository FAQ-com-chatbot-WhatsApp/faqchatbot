<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Util;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbFontsUtil {
	/**
	 * Recebe o nome de um arquivo de fonte, adiciona na página e retorna o nome próprio da fonte para a chamada no css
	 *
	 * @param   string   $fontFile  Nome do arquivo de fonte
	 * @param   string   $fontUrl   Url do google para importar a fonte
	 *
	 * @return  string  elemento css de definição da font-family
	 */
     public static function importFont($fontFile, $fontUrl = '', $textFontStyle = 'Regular', $onlyName = false){
        $app = Factory::getApplication();
        $doc = Factory::getDocument();
        $wa = $app->getDocument()->getWebAssetManager();

        // Herdar fonte da pagina
        if($fontFile == 'inherit'){
            $fontName = 'inherit';
        }
        // Utilizar fonte de url externa
        else if ($fontFile == 'external_linked'){
            if(!empty($fontUrl)){               
                // Obtem a url do arquivo sem a extensao
                $fontName = substr($fontUrl, 0, (strrpos($fontUrl, ".")));

                // Obtem somente o nome do arquivo
                //$fontName = substr($filename, strripos($filename, '/')+1);

                // Monta o Font Family a partir do nome do arquivo
                $fontName = 'Kalam-Bold';
    
                // Monta o código que deve ser posto na tag style
                $callCode = "@font-face { font-family: '{$fontName}'; src: url('{$fontUrl}')}\n";
        
                // Adiciona a codigo na tag style
                $wa->addInlineStyle($callCode);
            }else{
                $fontName = 'inherit';
            }
        }
        // Selecionada uma fonte da library
        else {
            $fontFormats = array(
                'ttf'   => 'truetype',
                'otf'   => 'opentype',
                'woff'  => 'woff',
                'woff2' => 'woff2',
                'svg'   => 'svg',
                'eot'   => 'embedded-opentype'
            );
            // Pega a extensão do arquivo
            $fileExt = pathinfo($fontFile, PATHINFO_EXTENSION);
            // Remove a extensão do arquivo
            $filename = substr($fontFile, 0 , (strrpos($fontFile, ".")));
            // Monta o Font Family a partir do nome do arquivo
            $fontName = str_replace('-', ' ', $filename);

            // Monta o caminho para o arquivo de fonte
            $fontPath = Uri::root().'libraries/noboss/src/Form/Field/assets/fonts/'.$fontFile;

            // Verifica se a fonte tem alguma estilização, ex. itálico ou negrito  
            if($textFontStyle != 'Regular'){               
                $styledFontFile = str_replace("Regular", $textFontStyle, $fontFile);
                $fontName = substr($styledFontFile, 0 , (strrpos($styledFontFile, ".")));

                // Monta o caminho até a fonte
                if(file_exists(JPATH_SITE.'/libraries/noboss/src/Form/Field/assets/fonts-stylized/'.$styledFontFile)){
                    // Monta o caminho até a fonte
                    $fontPath = Uri::root().'libraries/noboss/src/Form/Field/assets/fonts-stylized/'.$styledFontFile;
                }
            }

            $format = '';
            if ($fileExt != ''){
                $format = $fontFormats[$fileExt];
            }

            // Monta o código que deve ser posto na tag style
            $callCode = "@font-face { font-family: '{$fontName}'; src: url('{$fontPath}') format('{$format}')}\n";
    
            // Adiciona a codigo na tag style
            $wa->addInlineStyle($callCode);
        }
        // Verifica se deve retornar somente o nome da fonte ou a declaração css
        if (!$onlyName){
            if($fontName == "inherit"){
                // Retorna a declaracao css da fonte
                return 'font-family: ' . $fontName . ';';
            } else {
                // Retorna a declaracao css da fonte
                return 'font-family: "' . $fontName . '";';
            }
        } else {
            // Retorna o nome da fonte
            return $fontName;
        }
    }

    /**
     * Recebe o valor do campo nobossfontlist, prepara ele e chama a função importFont
     *
     * @param String $value string contendo o json do campo nobossfontlist
     * 
	 * @return  string  elemento css de definição da font-family
     */
    public static function importNobossfontlist($value, $onlyName = false){
        if($decoded = json_decode($value)){
            return self::importFont($decoded->fontfamily, $decoded->externalLinked, $decoded->fontStyle, $onlyName);
        } else {
            return self::importFont($value, '', 'Regular', $onlyName);
        }
    }

}
