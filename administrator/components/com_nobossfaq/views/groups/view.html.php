<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	com_nobossfaq
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

/**
 *  Classe View para listagem de registros que segue 'Metodo No Boss de desenvolvimento'
 *  @author  Johnny Salazar Reidel
 * 
 *  Orientacao: preencha as informacoes corretamente nas funcoes abaixo:
 *      - display(): defina os parametros sinalizados dentro da funcao
 *      - columnsDisplay(): defina as colunas que serao listadas
 *      - displayEmptyState(): defina o conteudo a ser exibido centralizado quando nenhum registro ainda foi cadastrado (Joomla 4)
 *      - specificTreatments(): local para adicionar codigos especificos desta view
 */

jimport('noboss.components.view.list');

class NobossfaqViewGroups extends NobossComponentsViewList {

    /**
     * Metodo principal
     */
	public function display($tpl = null) {
        // Nome da view de edicao dos registros
        $this->createViewAlias = 'group';
        
        // Alias do campo de id do componente
        $this->recordIdAlias = 'id_faqs_group';
        
        // Titulo da pagina
        $this->pageTitle = 'No Boss FAQ: '.JText::_('COM_NOBOSSFAQ_TITLE_GROUPS');
        
        // Icone exibico junto ao titulo da pagina (sem informar prefixo 'icon-')
        $this->pageIcon = 'list-view';
        
        // Texto a exibir como introducao em uma caixa de notice no topo da pagina
        $this->noticeIntro = JText::_('COM_NOBOSSFAQ_GROUPS_INFO_INTRO');
        
        parent::display($tpl);
	}

    /**
     * Funcao que define as colunas a serem exibidas na listagem
     */
    public function columnsDisplay(){
        // Inicio dos parametros que definem exibicao de colunas globais (deixe em branco os que nao desejar exibir ou nao existir)

        // Prefixo utilizado para as colunas na consulta sql que traz os items (no exemplo a seguir o valor seria 'faqs_group': SELECT * FROM noboss_faq as faqs_group)
        $this->prefixColumns = 'faqs_group';
        
        // Alias da coluna de status (deixe em branco se nao existir)
        $this->statusColumn = 'state';

        // Alias das colunas de inicio e fim de publicacao (deixe em branco se nao existir)
        $this->publishUpColumn = '';
        $this->publishDownColumn = '';

        // Alias da coluna de ordenacao (normalmente eh 'ordering'. Deixe em branco de nao existir)
        $this->orderingColumn = 'ordering';

        // Alias da coluna principal (exibe titulo, nome ou algo similar)
        $this->mainColumn = 'name_faqs_group';
        // Titulo da coluna princpal (informar apenas a constante)
        $this->mainColumnName = 'JFIELD_NAME_LABEL';
        // Texto adicional pequeno (small) ao lado do conteudo da coluna principal
        $this->mainColumnSmall = '';
        // Largura da coluna principal
        $this->mainColumnWidth = '';

        // Alias da coluna 'Autor' (normalmente eh 'created' / deixe em branco se nao existir ou nao quiser exibir)
        $this->nameCreation = "";

        // Alias da coluna 'Data de criacao' (normalmente eh 'created' / deixe em branco se nao existir ou nao quiser exibir)
        $this->creationDate = "";

        // Alias da coluna 'Revisado por' (normalmente eh 'created' / deixe em branco se nao existir ou nao quiser exibir)
        $this->nameModification = "";

        // Alias da coluna 'Data de modificacao' (normalmente eh 'modified' / deixe em branco se nao existir ou nao quiser exibir)
        $this->modificationDate = "";

        // Alias da coluna 'Idioma' (deixe em branco se nao existir ou nao quiser exibir)
        $this->languageColumn = 'language';


        // Inicio dos parametros para definir as colunas customizadas a exibir (cada coluna deve ser um objeto '$column' sendo adicionado no array $this->customColumns)

        // Array para armazenar como objeto cada coluna personalizada a ser exibida
        $this->customColumns = array();

        // Coluna para coluna de 'Acessar pagina'
        $column = new stdClass();
        // Alias da coluna
        $column->alias = 'link';
        // Titulo da coluna (informar apenas a constante)
        $column->title = 'COM_NOBOSSFAQ_PREVIEW_COLUMN';
        // Flag p/ determinar se coluna permitira ordenacao
        $column->allowOrdering = 0;
        // Html de retorno (pode colocar html setando '%VALUE%' para onde deve ser inserido o valor do item)
        $column->returnHtml = '<div style="width:100%; text-align: center;"><a href="%VALUE%" target="_blank" class="btn btn-secondary btn-sm"></span>'.JText::_('COM_NOBOSSFAQ_PREVIEW_BTN').'</a></div>';
        // Html de retorno quando valor nulo p/ o item corrente
        $column->returnHtmlWhenNull = '';
        // Largura da coluna (ex: '20%')
        $column->width = "10%";
        // Armazena valor no array
        $this->customColumns[] = $column;

        // Coluna para coluna de 'Posicao de modulo'
        $column = new stdClass();
        // Alias da coluna
        $column->alias = 'position_module_faq_display';
        // Titulo da coluna (informar apenas a constante)
        $column->title = 'COM_NOBOSSFAQ_POSITION_MODULE_LABEL';
        // Flag p/ determinar se coluna permitira ordenacao
        $column->allowOrdering = 0;
        // Html de retorno (pode colocar html setando '%VALUE%' para onde deve ser inserido o valor do item)
        $column->returnHtml = "<span class='badge-info badge bg-info'>%VALUE%</span>";
        // Html de retorno quando valor nulo p/ o item corrente
        $column->returnHtmlWhenNull = "<span class='badge bg-secondary'>".JText::_('JNONE')."</span>";
        // Largura da coluna (ex: '20%')
        $column->width = "";
        // Armazena valor no array
        $this->customColumns[] = $column;
    }

    /**
     * Funcao para exibir conteudo centralizadno quando nenhum registro ainda foi cadastrado (conhecido como 'empty state')
     *      - Essa funcao eh valida somente para o Joomla 4
     */
    public function displayEmptyState($emptyState = array()){
        // Defina a seguir os parametros para exibicao da mensagem

        // Habilitar / desabilitar exibicao da mensagem de empty state (true / false)
        $emptyState['isEmptyState'] = true;
        // Titulo
        $emptyState['title'] = JText::_('COM_NOBOSSFAQ_GROUP_EMPTY_STATE_TITLE');
        // Texto de apoio
        $emptyState['content'] = JText::_('COM_NOBOSSFAQ_GROUP_EMPTY_STATE_CONTENT');
        // Texto do botao de adicionar novo item
        $emptyState['btnadd'] = JText::_('COM_NOBOSSFAQ_GROUP_EMPTY_STATE_BTN_ADD');
        // Icone
        $emptyState['icon'] = 'icon-list-view';
        // Link do botao para pagina de documentacao 'leia mais' (deixe em branco para nao exibir o botao)
        $emptyState['helpURL'] = '';

        return parent::displayEmptyState($emptyState);
    }
    
    /**
     * Funcao para tratamentos especificos desta view
     */
    public function specificTreatments(){
        /* FIXME: qnd extensao for convertida p/ J5, eh preciso ajustar o caminho aqui.
	use Noboss\Library\Form\Field\Nbmodulesposition\NbmodulespositionHelper;
	Nome da classe muda para: NbmodulespositionHelper
*/
        jimport('noboss.forms.fields.nobossmodulesposition.helper');

        // Percorre todos itens a exibir
        foreach ($this->items as $i => $item){
            // Obtem dados do modulo vinculado ao item
            $dataModule = NobossModulePositionHelper::getDataModule($item->id_module_faqs_display);
            // Cria novo atributo ao objeto item para poder exibir na listagem
            $item->position_module_faq_display = (!empty($dataModule['position'])) ? $dataModule['position'] : '';

            // Gera o link de acesso para visualizacao do modulo
            $item->link = JURI::root()."index.php?option=com_nobossajax&module-id={$item->id_module_faqs_display}&load-head=1";
        }
    }

}
