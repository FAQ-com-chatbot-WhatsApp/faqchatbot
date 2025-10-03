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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Language\Multilanguage;
use Noboss\Library\Component\NbViewToolbarComponent;
use Noboss\Library\Util\NbInstallScriptUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 *  Classe a ser estendida em componentes para view de listagem de registros
 *  @author  Johnny Salazar Reidel
 * 
 *  Observacao: o funcionamento desta classe tem como requisito que o componente seja desenvolvido no modelo No Boss
 */
class NbViewListComponent extends HtmlView {

	/**
     * Array de itens
     *
     * @var  array
     */
    public $items;

    /**
     * O objeto de paginacao
     *
     * @var  \Joomla\CMS\Pagination\Pagination
     */
    public $pagination;

    /**
     * O estado modelo
     *
     * @var   \Joomla\Registry\Registry
     */
    public $state;

    /**
     * Objeto de formulario para filtros de pesquisa
     *
     * @var    \Joomla\CMS\Form\Form
     */
    public $filterForm;

    /**
     * Os filtros de pesquisa ativos
     *
     * @var    array
     */
    public $activeFilters;

    /**
     * Esta visao eh um estado vazio
     *
     * @var  boolean
     */
    private $isEmptyState = false;

    /**
     * A lista de pedidos para os eventos
     *
     * @var    array
     */
    public $ordering = [];

    /**
     * Array de botoes que devem ser exibidos dentro do agrupador 'actions'
     *
     * @var  array
     */
    public $btnActions = ['publish', 'unpublish', 'archive', 'checkin', 'trash', 'batch'];

    /**
     * Metodo que exibe a tmpl
     *
     * @param   string  $tpl  O nome do arquivo tmpl a ser carregado
     *
     * @return  void
     */
    public function display($tpl = null) {
        // Pasta 'layouts/noboss' nao existe e nao foi possivel cria-la
        if(!NbInstallScriptUtil::checkLibraryLayoutFolder()){
            return;
        }
        
		$this->items		      = $this->get('Items');
		$this->pagination	      = $this->get('Pagination');
		$this->state		      = $this->get('State');
        $this->filterForm         = $this->get('FilterForm');
        $this->activeFilters      = $this->get('ActiveFilters');

        // Verifique se ha erros.
        if (!empty($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // Model da view
        if(!empty($this->_defaultModel)){
            $this->model = $this->getModel();
        }

        // Nome da view
        $this->viewName = $this->_name;

        // Alias do componente (ex: 'com_nobossfaq') que esta definido no arquivo principal do componente
        $this->componentAlias = Factory::getApplication()->input->get('option');
        
        // Executa funcao que organiza as colunas para exibicao na tmpl
        if(method_exists($this, 'columnsDisplay')){
            $this->columnsDisplay();
        }
        else{
            throw new GenericDataException('The columns display function that defines the columns to list is not defined', 500);
        }

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

        // Nao precisamos filtrar por idioma quando o multilingue estiver desabilitado
        if (!Multilanguage::isEnabled()) {
            unset($this->activeFilters['language']);
            $this->filterForm->removeField('language', 'filter');
        }

        // Soh exibe listagem se nao estiver setado emptystate e que tenha dados a exibir
        if(!$this->displayEmptyState()){
            parent::display($tpl);
        }
	}

    /**
     * Metodo para exibir barra de navegacao padrao
     * 
     * OBS: para carregar uma barra personalizada no template, basta declarar esse mesma funcao dentro da view do componente e colocar o codigo personalizado
     */
    public function addToolbar(){
        // Carrega barra de navegacao padrao
        NbViewToolbarComponent::addToolbarListView($this->pageTitle, $this->pageIcon, $this->componentAlias, $this->viewName, $this->createViewAlias, $this->get('State'), $this->isEmptyState, $this->btnActions);
    }

    /**
     * Metodo para exibir conteudo centralizadno quando nenhum registro ainda foi cadastrado (conhecido como 'empty state')
     */
    public function displayEmptyState($emptyState = array()){
        if(empty($emptyState)){
            return false;
        }

        // Nao ha item cadastrados e IsEmptyState esta definido
        if (!\count($this->items) && $this->get('IsEmptyState') && $emptyState['isEmptyState']){
            $emptyState['formURL'] = "index.php?option={$this->componentAlias}&view={$this->viewName}";               
            if (Factory::getApplication()->getIdentity()->authorise('core.create', $this->componentAlias)) {
                $emptyState['createURL'] = "index.php?option={$this->componentAlias}&task={$this->createViewAlias}.edit";
            }                
            echo LayoutHelper::render('joomla.content.emptystate', $emptyState);
            return true;
        }

        return false;
    }
}
