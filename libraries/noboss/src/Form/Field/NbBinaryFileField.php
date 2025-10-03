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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Form\Field\Nbbinaryfile\NbbinaryfileHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbBinaryFileField extends FormField {

	/**
     * Método que pega o HTML para inserção do campo.
     * @return string Retorna uma string com HTML do campo.
     */
  	protected function getInput(){

        // Flag que verifica se já o foi informado valor para o campo.
        $hasValue = !empty($this->value);

        // Verifica se tem valor.
        if($hasValue){

            // Pega valor do campo e realiza decode do JSON.
            $dataFile =  json_decode($this->value);

            // Trata caracteres especiais para inserção no atributo value do campo.
            $this->value = htmlspecialchars($this->value);

            // Configura mime type para o arquivo.
            $attributeMime = "mime='#mimeTypeFile#'";
            $mimeTypeFile = (empty($dataFile->mimeTypeFile)) ? "" : $dataFile->mimeTypeFile;
            $this->mimeTypeFile = str_replace("#mimeTypeFile#", $mimeTypeFile, $attributeMime);

            // Verifica se o mime type do arquivo possui image.
            if(strstr($mimeTypeFile, "image") != false){
                // Configura conteúdo src da tag img do campo.
                $attributeSrc = "data:#mimeTypeFile#;base64,#stringFile#";
                $attributeSrc = str_replace("#mimeTypeFile#", $mimeTypeFile, $attributeSrc );
                $stringFile = (empty($dataFile->stringFile)) ? "" : $dataFile->stringFile;
                $attributeSrc = str_replace("#stringFile#", $stringFile, $attributeSrc);
                $this->src = $attributeSrc;
            }
        }

        $params = '';

        // Verifica se não tem registro e configura classe hidden do campo.
        $classHidden = ($hasValue) ? "" : " hidden";
        // Verifica se o parâmetro extension foi especificado no xml do campo.
        if ($this->getAttribute('extension')) {
            // Monta parte da URL conforme parâmetros.
            $params = "&extension=" . $this->getAttribute('extension');
        } else {
            // Verifica se existem os atributos max_width e max_height no xml do campo.
            if ($this->getAttribute('max_width') && $this->getAttribute('max_height')) {
                // Monta parte da URL conforme os parâmetros.
                $params = "&restrict_dimensions=".$this->getAttribute('restrict_dimensions').
                "&max_width=".$this->getAttribute('max_width').
                "&max_height=".$this->getAttribute('max_height');
            }
        }

        // Pega o label do campo.
        $translatedLabel =  Text::_($this->getAttribute('label'));

        // Pega o label do botão de upload.
        $translatedLabelUploadButton = Text::_($this->getAttribute('label_upload_button'));

        // Pega o label do link visualização do arquivo.
        $translatedLabelViewFile = Text::_($this->getAttribute('label_view_file'));

        // Pega o label do link para excluir o arquivo.
        $translatedLabelDeleteImage = Text::_($this->getAttribute('label_delete_file'));

        // URL para requisição do campo.
        $url = "index.php?option=com_nobossajax&library=noboss.src.Form.Field.Nbbinaryfile.NbbinaryfileHelper&method=getBinaryFromFile&format=json".$params;

        // Cria um objeto com a classe que trata leitura binária.
        $nobossfilebinaryfile = new NbbinaryfileHelper();

        if ($this->getAttribute('extension')){
            // Pega parâmetros do contexto do campo.
            $params = $nobossfilebinaryfile->getParams($this->getAttribute('extension'));
            $paramFileExtensionsGranted = implode(",", $params->get("file_upload_extensions_granted", array()));
            $sizeLimitUploadFileInBytes = $params->get("size_limit_upload_file", '');
        }else{
            $paramFileExtensionsGranted = $this->getAttribute('file_upload_extensions_granted', '');
            $sizeLimitUploadFileInBytes = $this->getAttribute('size_limit_upload_file', '');
        }
        
        // Verifica se o valor do parâmetro de extensões permitidas é vazio e atribui um valor default.
        $extensionGranted = (empty($paramFileExtensionsGranted)) ? "*" : $paramFileExtensionsGranted;

        // Verifica se o valor do parâmetro de limite de tamanho de arquivo é vazio
        $attributeMaxFileSize = (empty($sizeLimitUploadFileInBytes)) ? "" : " data-max-size=" . $sizeLimitUploadFileInBytes;

        // Cria objeto para armazenar informações de contexto do campo.
        $dataParamsField = new \stdClass();
        $dataParamsField->msg_error_max_file_size = Text::_($this->getAttribute("msg_error_max_file_size"));
        $dataParamsField->msg_error_extension_file_granted = Text::_($this->getAttribute("msg_error_extension_file_granted"));

        // Transforma os dados para json.
        $jsonDataParamsField = json_encode($dataParamsField);

        // Trata caracteres especiais para inserção no atributo value do campo.
        $valueDataParamsField = htmlspecialchars($jsonDataParamsField);

        // Monta o HTML do campo.
        $html =
            <<<HTML
            <div class="file-upload-context" id="{$this->getAttribute('id')}">
                <div class="file-upload upload-button" style="margin-bottom: 10px;">
HTML;

        // Pega contexto da aplicação joomla.
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        // Acesso nao eh no administrator
        if(!$app->isClient('administrator')){
            // Carrega estilo CSS para o campo na área de site .
            $wa->registerAndUseStyle('nobossbinaryfile', Uri::root().'libraries/noboss/src/Form/Field/assets/stylesheets/css/nobossbinaryfile.min.css');

            // Cria elemento com botão de upload.
            $html .=
                <<<HTML
                    <span>{$translatedLabelUploadButton}</span>
HTML;
        }

        $html .=
            <<<HTML
                    <input name="upload_{$this->getAttribute('name')}" accept="{$extensionGranted}"{$attributeMaxFileSize} class="upload {$this->getAttribute('class')}" type="file" aria-label="{$translatedLabel}" data-id="upload-binary" data-params-field="{$valueDataParamsField}" data-url="{$url}">
                    <input type="hidden" name="{$this->getAttribute('name')}" value="{$this->value}" {$this->mimeTypeFile} data-id="upload-binary-hidden">
                </div>
                <span data-id="file-options" class="options-file{$classHidden}">
                    <span class="icon-arrow-right-3" style="margin: 0;"></span>
                    <a href="#" class="simple-link tooltip-trigger--active" data-id="seeFile">$translatedLabelViewFile</a>
                    &nbsp;&nbsp;
                    <span class="icon-arrow-right-3" style="margin: 0;"></span>
                    <a href="#" class="simple-link" data-id="deleteFile">$translatedLabelDeleteImage</a>
                </span>
                <div class="tooltip-image hidden" style="max-width: 50%;">
                    <img name="{$this->getAttribute('name')}" class="thumb" src="{$this->src}" data-id="upload-binary-img">
                </div>
            </div>
HTML;
            // Carrega javascript para funcionamento do campo
            $wa->registerAndUseScript('nobossbinaryfile', Uri::root().'libraries/noboss/src/Form/Field/assets/js/min/nobossbinaryfile.min.js');

            return $html;
        }
}
