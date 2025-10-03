<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Component;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Noboss\Library\Util\NbLoadextensionAssetsUtil;
use Noboss\Library\Component\NbViewToolbarComponent;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 *  Classe a ser estendida em componentes para view de edicao de registros
 *  @author  Johnny Salazar Reidel
 * 
 *  Observacao: o funcionamento desta classe tem como requisito que o componente seja desenvolvido no modelo No Boss
 */
class NbViewEditComponent extends HtmlView {
    /**
     * O objeto Form
     *
     * @var  \Joomla\CMS\Form\Form
     */
    public $form;

    /**
     * O item ativo
     *
     * @var  object
     */
    public $item;

    /**
     * O estado modelo
     *
     * @var  \Joomla\Registry\Registry
     */
    public $state;

    /**
     * Sinalizar se uma associacao existe
     *
     * @var  boolean
     */
    public $assoc;

    /**
     * As ações que o usuario esta autorizado a executar
     *
     * @var    \Joomla\Registry\Registry
     */
    public $canDo;

    /**
     * Matriz de fieldsets a nao exibir
     *
     * @var    string[]
     */
    public $ignore_fieldsets = [];

    /**
     * Metodo que exibe a tmpl
     *
     * @param   string  $tpl  O nome do arquivo tmpl a ser carregado
     *
     * @return  void
     */
	public function display($tpl = null) {
		$app = Factory::getApplication();
		$input = $app->input;

        $this->form		= $this->get('Form');
		$this->item		= $this->get('Item');
		$this->state	= $this->get('State');

		// Verifica erros
        if (!empty($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // Model da view
        $this->model = $this->getModel();

        // Nome da view
        $this->viewName = $this->_name;

        // Alias do componente (ex: 'com_nobossfaq') que esta definido no arquivo principal do componente
        $this->componentAlias = $input->get('option');

        // Nome dos fields de titulo e alias que sao exibidos no topo da pagina (qnd existir)
        $this->fieldName = $input->get('fieldName');
        $this->fieldAlias = $input->get('fieldAlias');

        // Alias do campo de id do componente
        $this->recordIdAlias = $input->get('recordIdAlias');

        $id = isset($this->item->{$this->recordIdAlias}) ? (int)$this->item->{$this->recordIdAlias} : 0;

        // Url do post do formulario
        $this->actionForm = (empty($this->actionForm)) ? "index.php?option={$this->componentAlias}&view={$this->viewName}&layout=edit&{$this->recordIdAlias}=".$id : $this->actionForm;

        // Existe valor em fieldsetsIgnore: converte para novo field utilizado para indicar fieldsets a nao ser exibidos
        if(!empty($this->fieldsetsIgnore)){
            // Variavel fieldsetsIgnore havia sido criado pela noboss, mas depois o Joomla lancou ignore_fieldsets com mesmo proposito
            $this->ignore_fieldsets = $this->fieldsetsIgnore;
            $this->fieldsetsIgnore = '';
        }

        // Carrega arquivo traducao da library
		$assetsObject = new NbLoadextensionAssetsUtil('lib_noboss');
		$extensionPath = $assetsObject->getDirectoryExtension(false);
		Factory::getLanguage()->load("lib_noboss", $extensionPath);

        // Carrega barra de navegacao padrao
        $this->addToolbar();        

        // Executa funcao para tratamentos especificos desta view
        if(method_exists($this, 'specificTreatments')){
            $this->specificTreatments();
        }

        // Executa funcao para carregar CSS e JS
        if(method_exists($this, 'loadExtensionAssets')){
            $this->loadExtensionAssets();
        }

        $this->useCoreUI = true;

		parent::display($tpl);
	}

    /**
     * Metodo para exibir barra de navegacao padrao
     * 
     * OBS: para carregar uma barra personalizada no template, basta declarar esse mesma funcao dentro da view do componente e colocar o codigo personalizado
     */
    public function addToolbar(){
        $id = isset($this->item->{$this->recordIdAlias}) ? (int)$this->item->{$this->recordIdAlias} : 0;

        // Novo registro
        if($id == 0){
            // Constante para parte do titulo 'novo registro' nao definido: pega default da library
            if(empty($this->pageSubtitleNew)){
                $this->pageSubtitle = 'NOBOSS_COMPONENT_VIEW_EDIT_SUBTITLE_NEW';
            }
            else{
                $this->pageSubtitle = $this->pageSubtitleNew;
            }
        }
        // Edicao de registro
        else{
            // Constante para parte do titulo 'edicao de registro' nao definido: pega default da library
            if(empty($this->pageSubtitleEdit)){
                $this->pageSubtitle = 'NOBOSS_COMPONENT_VIEW_EDIT_SUBTITLE_EDIT';
            }
            else{
                $this->pageSubtitle = $this->pageSubtitleEdit;
            }
        }

        // Carrega barra de navegacao
        NbViewToolbarComponent::addToolbarEditView($this->pageTitle, $this->pageSubtitle, $this->pageIcon, $this->recordIdAlias, $this->componentAlias, $this->viewName, $this->item);
    }
    /**
     * Funcao para carregamento de variaveis e arquivos JS e CSS no padrao No Boss
     * 
     * Nota: depreciated para formato do Joomla 5. No Joomla 5 passamos a ter essas chamadas direto no componente, podendo dai especificar o nome do arquivo que vamos carregar.
     * 
     * OBS: essa funcao soh eh executada se for chamada pela funcao display do componente
     */
    public function loadExtensionAssets(){       
        // Instancia objeto para carregamento de CSS e JS da extensao
        $assetsObject = new NbLoadextensionAssetsUtil($this->componentAlias);

        // Adiciona jquery, variaveis JS (baseNameUrl, majorVersionJoomla, completeVersionJoomla) e carrega arquivo JS da extensao (caso esteja definido um no caminho default. Ex: '/administrator/components/com_nobossfaq/assets/admin/js/com_nobossfaq.min.js')
        $assetsObject->loadJs(true, '', true, true, true);

        // Carrega arquivo CSS da extensao (caso esteja definido um no caminho default. Ex: '/administrator/components/com_nobossfaq/assets/admin/css/com_nobossfaq.min.css')
        $assetsObject->loadCss(true, '', true);
    }
}
