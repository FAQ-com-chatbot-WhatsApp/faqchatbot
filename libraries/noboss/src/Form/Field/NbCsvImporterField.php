<?php
/**
 * @package         No Boss Extensions
 * @subpackage      No Boss Library
 * @author          No Boss Technology <contact@nobosstechnology.com>
 * @copyright       Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license         GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Util\NbJsConstantsUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbCsvImporterField extends FormField {
   
    protected function getInput(){
        // Adiciona constantes padroes do JS
        NbJsConstantsUtil::addConstantsDefault();

        $html = "";

        // Valida se o usuário colocou um valor default no campo pai, algo que não pode e remove o valor
        if($this->value == $this->default){
            $this->value = '';
        }

        $cols = array();

        // Percorre todas as colunas especificadas no xml
        for($i = 0; $i < count($this->element->col); $i++){
            $objCol = new \stdClass;
            // Seta o nome da coluna
            $objCol->alias = (String)$this->element->col[$i]->attributes()->alias;
            // Seta label da coluna no array
            $objCol->label = Text::_($this->element->col[$i]->attributes()->label);
            // Seta label da coluna no array
            $objCol->label = Text::_((String)$this->element->col[$i]->attributes()->label);
            // Seta nome de funcao JS a executar da coluna no array (caso definido)
            $jsfunction = (String)$this->element->col[$i]->attributes()->jsfunction;
            if (isset($jsfunction) && !empty($jsfunction)){
                $objCol->jsfunction = $jsfunction;
            }
            // Seta opcoes validas para a coluna (caso definido)
            $validvalues = (String)$this->element->col[$i]->attributes()->validvalues;
            if (isset($validvalues) && !empty($validvalues)){
                $objCol->validvalues = $validvalues;
            }
            // Armazena objeto no array
            $cols[] = $objCol;
        }

        $html .= "<button class='csvimportercols-btn-upload'>".Text::_('LIB_NOBOSS_FIELD_NOBOSSCSVIMPORTER_SELECT_FILE_BUTTON')."</button>";

        // Subform para qual os dados podem ser migrados
        $subform = (!empty($this->element->attributes()->subform)) ? 'data-subformname="'.$this->element->attributes()->subform.'"' : '';

        // Input para upload do arquivo (coloca dados da coluna vindo do xml) e nome do xml que podemos copiar os dados
        $html .= "<span class='csvimportercols-input-file ".$this->class."' style='display: none;'><input type='file' data-csvimporter='file' accept='.csv,.txt' data-csvimportercols='".json_encode($cols)."' {$subform} /></span>";

        $this->value = str_replace("'", '\"', $this->value);

        // Input hidden onde sao salvos os valores importados pelo arquivo
        $html .= "<input type='hidden' data-csvimporter='jsonvalue' name='".$this->name."' value='".$this->value."' />";

        // Setado para exibir link de download
        if((!isset($this->element->attributes()->displaylinkdownload)) || ($this->element->attributes()->displaylinkdownload == 1)){
            // Titulo do link esta definido no xml
            if(isset($this->element->attributes()->titlelinkdownload)){
                $titleLink = Text::_($this->element->attributes()->titlelinkdownload);
            }
            // Obtem titulo do link de constante da library
            else{
                $titleLink = Text::_('LIB_NOBOSS_FIELD_NOBOSSCSVIMPORTER_LINK_DOWNLOAD_TITLE');
            }
            
            $html .= '<a href="#" data-id="download-csv" style="margin-left: 25px;"><span class="icon-download"></span>'.$titleLink.'</a>';
        }

        // Setado para exibir tabela
        if((!isset($this->element->attributes()->displaytable)) || ($this->element->attributes()->displaytable == 1)){
            // Ha valores salvos
            if(!empty($this->value)){
                $displayResult = 'block';
            }
            else{
                $displayResult = 'none';
            }

            // Exibe titulo de 'dados importados'
            $html .= "<span style='font-weight: bold; font-size: 14px; padding-bottom: 15px;  margin-top: 30px; display: ".$displayResult.";'>".Text::_         ('LIB_NOBOSS_FIELD_NOBOSSCSVIMPORTER_IMPORTED_DATA_TITLE')."</span>";

            // Abre table para apresentacao dos dados importados
            $table = "<div style='position: relative; max-height: 450px; overflow: scroll;  display: ".$displayResult.";'>
                            <table id='csvimporter-result' class='table table-striped'>";


            // Ha valores salvos
            if (!empty($this->value)){
                // Abre <tr> do cabecalho
                $table .= '<thead><tr>';

                // Percorre todas as colunas especificadas no xml
                foreach($cols as $colHead){
                    // Exibe <th> com label da coluna
                    $table .= "<th>{$colHead->label}</th>";
                }

                // Fecha <tr> do cabecalho
                $table .= '</tr></thead>';

                // Decodifica json em array de objetos
                $lines = json_decode($this->value);

                // Percorre cada linha
                foreach($lines as $line){
                    // Abre <tr> da linha
                    $table .= '<tr>';

                    // Percorre todas as colunas especificadas no xml
                    foreach($cols as $colHead){
                        // Coluna possui valor para linha atual
                        if (isset($line->{$colHead->alias})){
                            // Exibe <td> com label da coluna
                            $table .= "<td>".(urldecode($line->{$colHead->alias}))."</td>";
                        }
                        // Coluna nao possui valor
                        else{
                            // Exibe td vazio para nao quebrar tabela
                            $table .= "<td></td>";
                        }
                    }

                    // Fecha </tr> da linha
                    $table .= '<tr>';
                }
            }

            // Fecha table para apresentacao dos dados importados
            $table .= "</table></div>";

            $html .= $table;
        }
        
        $app = Factory::getApplication();
        $doc = Factory::getDocument();
        $wa = $app->getDocument()->getWebAssetManager();

        $wa->addInline("script", "var baseNameUrl = '".Uri::root()."';", array('name' => 'baseNameUrl'));

        // Define constantes para o JS
        Text::script('LIB_NOBOSS_FIELD_NOBOSSCSVIMPORTER_ERROR_INVALID_EXTENSION_FILE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSCSVIMPORTER_BADLY_FORMATTED_FUNCTION');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSCSVIMPORTER_UNABLE_UPLOAD_DATA_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSCSVIMPORTER_ERROR_READING_FILE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSCSVIMPORTER_INCORRECTLY_FORMATTED_DATA');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSCSVIMPORTER_DATA_BEING_MIGRATED');

        $wa->registerAndUseScript('nobosscsvimporter', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobosscsvimporter.min.js");
        $wa->registerAndUseStyle('nobosscsvimporter', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobosscsvimporter.min.css");

        // Retorna o html do field gerado
        return $html;
    }
}
