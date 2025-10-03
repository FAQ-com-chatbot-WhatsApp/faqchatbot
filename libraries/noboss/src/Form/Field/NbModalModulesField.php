<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Noboss\Library\Util\NbLoadextensionAssetsUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

// Carrega select com posicoes de modulo permitindo abrir em modal para edicao
// Código inspirado no plugin 'FormFieldEBModules' (plugins\engagebox\module\form\fields\ebmodules.php)

// TODO: a partir do Joomla 5.0.0, o Joomla passou a usar o campo 'ModalSelectField' para abrir modais de edicao de artigos, categorias, etc. No futuro, podemos migrar este campo para estender o 'ModalSelectField' igual field administrator\components\com_content\src\Field\Modal\ArticleField.php faz (https://magazine.joomla.org/all-issues/january-2024/creating-a-custom-form-field-type-in-joomla-5-using-the-modal-select-example)
class NbModalModulesField extends ListField {

    protected $options = array();
    protected $limit;

    protected function getOptions() {
        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $this->options);
        
        return $options;

    }

    protected function getModules($limit = null){
        $db	= Factory::getDbo();
        $query =  $db->getQuery(true);
        $query->select("id, CONCAT(title, ' (id: ', id, ')') AS title, module, position, published");
        $query->from('#__modules AS m');
        $query->where("m.client_id = 0");
        // $query->where("published IN ('0', '1')");

        // Definido parametro 'extension': limita exibicao somente do modulo mencionado
        if(isset($this->element['extension'])){
            $query->where("m.module = " . $db->quote($this->element['extension']));
        }

        // Limita numero de exibicoes
        if(!empty($limit)){
            $query->setLimit("{$limit}");
            // Ordena priorizando a exibicao dos modulos da no boss
            $query->order("module like 'mod_noboss%' DESC, module like 'mod_nb%' DESC, published DESC, module, ordering");
        }else{
            // Ordena priorizando os ids mais recentes
            $query->order("id desc, published DESC, module, ordering");
        }

        // echo str_replace('#__', 'ext_', $query); exit;

        // Set the query
        $db->setQuery($query);

        // Dados nao encontrados
        if (!($modules = $db->loadObjectList())) {
            return false;
        }

        return $modules;
    }

    protected function getInput() {
        // Obtem os modulos do site
        $modules = $this->getModules();

        // Limite maximo a exibir definido no xml
        if(!empty($this->element['limit'])){
            $this->limit = (int)$this->element['limit'];
        }
        // Limite nao definido: forca um limite
        else{
            $this->limit = 2000;
        }

        // Existe mais modulos que o limite: repete pesquisa trazendo apenas os modulos mais recentes dentro do limite
        if(!empty($modules) && (count($modules) > $this->limit)){
            // Obtem os modulos do site
            $modules = $this->getModules($this->limit);
        }

        // Percorre resultados para montar opcoes do select
        foreach($modules as $module){
            $title = $module->title;

            // Nao definido parametro 'extension': acrescenta junto ao nome do modulo o nome da extensao
            if(!isset($this->element['extension'])){
                $title .=  ' (' . $module->module . ')';
            }

            if($module->published == '-2'){
                $title .= Text::_('LIB_NOBOSS_FIELD_NOBOSSMODULES_NOT_TRASHED');
            }

            if($module->published == '0'){
                $title .= Text::_('LIB_NOBOSS_FIELD_NOBOSSMODULES_NOT_PUBLISHED');
            }

            $this->options[] = HTMLHelper::_('select.option', $module->id, $title);
        }

        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        $wa->registerAndUseStyle('nobossmodalmodules', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobossmodalmodules.min.css");
        $wa->registerAndUseScript('nobossmodalmodules', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobossmodalmodules.min.js");

        // Definido parametro 'extension'
        if(isset($this->element['extension'])){
            // Obtem dados da extensao na tabela do joomla
            $extensionData = $this->getExtensionsData($this->element['extension']);

            if(!empty($extensionData)){
                // Carrega arquivo .sys de traducao da extensao
                $assetsObject = new NbLoadextensionAssetsUtil($extensionData->element);
                $extensionPath = $assetsObject->getDirectoryExtension();
                $lang = Factory::getLanguage();
                $lang->load($extensionData->element. '.sys', $extensionPath);

                // Obtem nome da extensao a partir do arquivo de traducao
                $extensionName = Text::_($extensionData->name);
            }
        }

        $modalNameEdit = 'modaledit_' . $this->id;
       
        // Options da modal de edicao (nao passamos a url da modal pq isso manipulamos via JS por conta da variacao do ID de modulo selecionado)
        $optionsEdit = array(
            'title'       => (!empty($extensionName) ? $extensionName . ': ' : '') . Text::_('LIB_NOBOSS_FIELD_NOBOSSMODULES_TITLE_EDIT_MODULE'),
            'url'         => '#',
            'backdrop'    => 'static',
            'keyboard'    => false,
            'closeButton' => true,
            // 'height'      => '700px', // 400px
            // 'width'       => '800px', // 800px
            'bodyHeight'  => 90, // 70
            'modalWidth'  => 90, // 80
            'footer'      => '<button type="button" class="btn btn-danger" data-id="' . $modalNameEdit . '" data-id-select="' . $this->id . '" data-type="cancel">' . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
                        . '<button type="button" class="btn btn-success" data-id="' . $modalNameEdit . '" data-id-select="' . $this->id . '" data-type="save">' . Text::_('JSAVE') . '</button>'
                        . '<button type="button" class="btn btn-success" data-id="' . $modalNameEdit . '" data-id-select="' . $this->id . '" data-type="apply">' . Text::_('JAPPLY') . '</button>',
        );

        // Renderiza a modal de edicao
        echo '<div class="nobossmodalmodules__modal nobossmodalmodules__modal-edit">'.HTMLHelper::_('bootstrap.renderModal', $modalNameEdit, $optionsEdit).'</div>';

        $linkPreview = '';
        $displayBtnsEdit = '';
        $displayBtnNew = '';

        // Nao possui valor vindo do banco
        if(empty($this->value)){
            $displayBtnsEdit = 'none';
            $displayBtnNew = 'inline-block';
        }
        else{
            $displayBtnsEdit = 'inline-block';
            $displayBtnNew = 'none';
            $linkPreview = Uri::root()."index.php?option=com_nobossajax&load-head=1&module-id={$this->value}";
        }   

        // Dtml do botao de edicao
        $htmlButtons = '<button class="btn btn-primary nb-module-btn-edit" id="'.$this->id.'_edit" name="'.$this->name.'_edit" data-bs-toggle="modal" type="button" data-bs-target="#'.$modalNameEdit.'" style="display:'.$displayBtnsEdit.';"><span class="icon-edit" aria-hidden="true"></span> ' . Text::_('LIB_NOBOSS_FIELD_NOBOSSMODULES_BUTTON_EDIT_MODULE') . '</button>';

        // Html do botao de preview
        $htmlButtons .= '<a class="btn btn-secondary nb-module-btn-preview" href="'.$linkPreview.'" target="_blank" style="display:'.$displayBtnsEdit.';"> ' . Text::_('LIB_NOBOSS_FIELD_NOBOSSMODULES_BUTTON_PREVIEW_MODULE') . '</a>';

        // Definido parametro 'extension': exibe opcao de criar modulo que seja da extensao especificada
        if(isset($this->element['extension']) && !empty($extensionData)){
            $modalNameNew = 'modalnew_' . $this->id;

            // Options da modal de new
            $optionsNew = array(
                'title'       => $extensionName . ': ' . Text::_('LIB_NOBOSS_FIELD_NOBOSSMODULES_TITLE_NEW_MODULE'),
                'url'         => '#',
                'backdrop'    => 'static',
                'keyboard'    => false,
                'closeButton' => true,               
                // 'height'      => '700px', // 400px
                // 'width'       => '800px', // 800px
                'bodyHeight'  => 90, // 70
                'modalWidth'  => 90, // 80
                'footer'      => '<button type="button" class="btn btn-danger" data-id="' . $modalNameNew . '" data-id-select="' . $this->id . '" data-type="cancel">' . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
                        . '<button type="button" class="btn btn-success" data-id="' . $modalNameNew . '" data-id-select="' . $this->id . '" data-type="save">' . Text::_('JSAVE') . '</button>'
                        . '<button type="button" class="btn btn-success" data-id="' . $modalNameNew . '" data-id-select="' . $this->id . '" data-type="apply">' . Text::_('JAPPLY') . '</button>',
            );


            // Renderiza a modal de novo modulo
            echo '<div class="nobossmodalmodules__modal nobossmodalmodules__modal-new">'.HTMLHelper::_('bootstrap.renderModal', $modalNameNew, $optionsNew).'</div>';

            // Html do botao de novo modulo
            $htmlButtons .= '<button class="btn btn-success nb-module-btn-new" id="'.$this->id.'_new" name="'.$this->name.'_new" data-bs-toggle="modal" type="button" data-bs-target="#'.$modalNameNew.'" style="display:'.$displayBtnNew.';" data-extension-id="'.$extensionData->extension_id.'" ><span class="icon-new" aria-hidden="true"></span> ' . Text::_('LIB_NOBOSS_FIELD_NOBOSSMODULES_BUTTON_NEW_MODULE') . '</button>';
        }

        // Usando preg_match para capturar a ultima  ocorrencia de colchetes
        preg_match_all('/\[(.*?)\]/', $this->formControl, $matches);
        
        // Existe uma ultima ocorrencia de [] no name do field (campo esta sendo chamado talvez em subform)
        if (!empty($matches[1])) {
            // Alias para replace via JS de name / id quando utilizado em subform
            $aliasReplace = end($matches[1]);
        }
        else{
            $aliasReplace = '';
        }

        // Retorna input parent (field list) e mais os botoes
        return "<div class='nobossmodalmodules__field' data-alias-replace='{$aliasReplace}'>".parent::getInput().$htmlButtons."</div>";
    }

    protected function getExtensionsData($element){
        $db	= Factory::getDbo();

        $query =  $db->getQuery(true);
        $query->select('*');
        $query->from('#__extensions');
        $query->where("element = " . $db->quote($element));

        $db->setQuery($query);

        // echo str_replace('#__', 'ext_', $query); exit;

        return $db->loadObject();
    }
}
