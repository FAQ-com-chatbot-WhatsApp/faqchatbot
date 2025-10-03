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
 *  Classe Model para edicao de registros que segue 'Metodo No Boss de desenvolvimento'
 *  @author  Johnny Salazar Reidel
 * 
 *  Orientacoes: 
 *    - Estamos utilizando NobossComponentsModelEdit da library da No Boss como Trait.
 *      * Traits servem apenas para reuso de codigo, mas nao para heranca. Ou seja, nao eh possivel estender funcoes definidas em NobossComponentsModelEdit.
 *      * Se houver necessidade de estender alguma funcao de NobossComponentsModelEdit, copie a funcao original para ca e edite conforme necessario. Neste caso, a funcao executada sera a desta classe e nao mais a definida no trait NobossComponentsModelEdit
 *      
 *    - Altere o nome desta classe
 *    - Defina os parametros sinalizados no metodo __construct()
 *    - Prepare os dados a serem salvos no banco usando os meotodos 'save()' e 'prepareTable()'
 *        * Voce pode usar os dois metodos para tratamento de dados antes de serem salvos no banco
 *        * Por padrao, apos a execucao destes dois metodos, o Joomla executa a funcao 'store()' definida na classe Table
 *        * Em resumo, a ordem de exeucacao das funcoes para salvar eh: 1 - save() | 2 - prepareTable() | 3 - store()
 *    - No metodo 'getItem()' sao obtidos os dados do banco
 *        * Esse metodo obtem os dados a serem carregados utilizando a classe Table
 *        * Voce pode aproveitar neste metodo para tratar algum dado vindo do banco antes de ser carregado no formulario
 *    - No metodo 'validate' voce pode validar dados do formulario antes de salvar (funciona apenas a partir do Joomla 3.9.25)
 *    - No metodo 'publish' voce podera executar alguma acao adicional quando usuario solicitar alteracao de status de um ou mais registros na view de listagem
 *    - Voce tambem pode criar outras funcoes conforme a complexidade do seu componente
 */

jimport('noboss.components.model.edit');

class NobossfaqModelGroup extends JModelAdmin {
    use NobossComponentsModelEdit;

    /**
	 * Construtor
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 */
	public function __construct($config = array()){
        $input = JFactory::getApplication()->input;

        // Nome dos fields de titulo e alias que sao exibidos no topo da pagina (qnd nao existir, deixar valor em branco)
        $this->fieldName = 'name_faqs_group';
        $this->fieldAlias = '';

        // Alias do campo de id do componente
        $this->recordIdAlias = 'id_faqs_group';

        // Alias do campo de ordenacao (deixar em branco se nao existir)
        $this->fieldOrdering = 'ordering';

        // Nome da tabela do banco
        $this->nameTable = '#__noboss_faq_group';

        // Sufixo do nome da classe da Table de edicao dos registros. Ex: se a chasse se chamar 'NobossfaqTableFaqgroup' o valor sera 'Faqgroup' que eh o final do nome da classe
        $this->tableClassNameSuffix = 'Faqgroup';

        // Seta no input alguns dos dados declarados para poder usar na view
        $input->set('fieldName', $this->fieldName);
        $input->set('fieldAlias', $this->fieldAlias);
        $input->set('recordIdAlias', $this->recordIdAlias);
        
        parent::__construct($config);
    }

    /**
     * Funcao que salva um registro instanciando a classe Table
     *
     * @param array     $data   Dados a serem salvos
     * 
     * @return boolean  true ou false
     */
    public function save($data){
        /* FIXME: qnd extensao for convertida p/ J5, eh preciso ajustar o caminho aqui.
	use Noboss\Library\Form\Field\Nbmodulesposition\NbmodulespositionHelper;
	Nome da classe muda para: NbmodulespositionHelper
*/
        jimport('noboss.forms.fields.nobossmodulesposition.helper');

        $input = JFactory::getApplication()->input;

        // Usuario solicitou clone de outro registro
        if ($input->get('task') == 'save2copy'){
            // Executa metodo para incrementar um numero no final do nome e alias
            $data = $this->incrementNameAndAlias($data, $this->fieldName, $this->fieldAlias);

            // Deixa registro despublicado
            $data['state'] = 0;

			// Zera o ID do modulo vinculado (nao pode referenciar o mesmo)
            $data["id_module_faqs_display"] = '';
        }

        // Variavel que armazena configuracaes do modulo de exibicao de FAQs.
        $data["config_module_faqs_display"] = array();

        // Percorre todos os campos do form para tratar os que sao do modulo
        foreach ($data as $keyField => $valueField) {
            // Campo inicia com name "faqs_display_": reorganiza o campo no array $data
            if(strpos($keyField, "faqs_display_") === 0){
                $data["config_module_faqs_display"]["$keyField"] = $valueField;
                unset($data[$keyField]);
            }
        }

        // Encoda em json o array com os campos do modulo
        $data["config_module_faqs_display"] = json_encode($data["config_module_faqs_display"]);

        // Agrupa como string as categorias de artigos
        $data["content_categories_articles"] = (is_array($data["content_categories_articles"])) ? implode(",", $data["content_categories_articles"]) : '';

        // Titulo do modulo
        $titleModule = '[No Boss FAQ] '.$data[$this->fieldName];

        // Define os campos que serao salvos no modulo para columa params
        $moduleConfig = json_decode($dataGroup['config_module_faqs_display']);
        $theme = json_decode($moduleConfig->faqs_display_theme)->theme;
        $exampleModals = array("faqs_display_external_area_{$theme}", "faqs_display_items_customization_{$theme}", "faqs_display_search_form_{$theme}", "faqs_display_categories_{$theme}");
        $exampleDataTabs = array($moduleConfig);
        $ignoreFields = array('faqs_display_theme');

        // Inicia transacao do banco de dados
        $this->_db->transactionStart();

        // Salva dados do modulo de exibicao de FAQs.
        $idModule = NobossModulePositionHelper::saveModule(
            "mod_nobossfaq",
            $data["id_module_faqs_display"],
            $titleModule,
            $data["position_module_faqs_display"],
            $data["assignment_module_faqs_display"],
            $data["assigned_module_faqs_display"],
            $data["state"],
            $data["language"],
            $exampleModals,
            $exampleDataTabs, 
            $ignoreFields
        );

        // Ocorreu erro ao salvar o modulo
        if(!$idModule){
            // Desfaz transacao do banco de dados
            $this->_db->transactionRollback();
            return false;
        }

        // Armazena o id do modulo para salvar no registro do grupo
        $data["id_module_faqs_display"] = $idModule;

        // Salva dados do registro
        $isSaved = parent::save($data);

        // Ocorreu erro ao salvar registro
        if(!$isSaved){
            // Desfaz transacao do banco de dados
            $this->_db->transactionRollback();
            return false;
        }

        // Deu tudo certo: commita transacao do banco e retorna true
        $this->_db->transactionCommit();
        return true;
    }

    /**
	 * Prepare e higieniza os dados antes de salvar usando a table
	 *
	 * @param   JTable  $table  Objeto com os dados que serao salvos
	 *
	 * @return  void
	 */
	protected function prepareTable($table) {
        // Limpa caracteres invalidos do titulo e gera alias no formato correto
        $table = $this->treatmentNameAndAlias($table, $this->fieldName, $this->fieldAlias);

        // Seta valor para o campo de ordenacao (qnd existir e ja nao tiver valor definido)
        $table = $this->setOrdering($table, $this->fieldOrdering, $this->nameTable);

        // Seta valores para os campos 'created', 'created_by', 'modified' e 'modified_by' (quando existirem)
        $table = $this->setCreatedModified($table, $this->recordIdAlias);
	}

    /**
     * Metodo para obter os dados de um registro
     *
     * @param   integer     Id do registro
     * @return  mixed  Objeto caso sucesso, ou false caso falhe.
     */
    public function getItem($pk = null) {
        /* FIXME: qnd extensao for convertida p/ J5, eh preciso ajustar o caminho aqui.
	use Noboss\Library\Form\Field\Nbmodulesposition\NbmodulespositionHelper;
	Nome da classe muda para: NbmodulespositionHelper
*/
        jimport('noboss.forms.fields.nobossmodulesposition.helper');

        $item = parent::getItem($pk);

        // Eh edicao
        if(!is_null($item->{$this->recordIdAlias})){
            // Carrega dados do modulo de exibicao de FAQs.
            $dataModuleFaqsDisplay = NobossModulePositionHelper::getDataModule($item->id_module_faqs_display);

            $item->client_id_module_faqs_display = $dataModuleFaqsDisplay['client_id'];
            $item->published_module_faqs_display = $dataModuleFaqsDisplay['published'];
            $item->position_module_faqs_display = $dataModuleFaqsDisplay['position'];
            $item->assignment_module_faqs_display = $dataModuleFaqsDisplay['assignment'];
            $item->assigned_module_faqs_display = $dataModuleFaqsDisplay['assigned'];

            // Converte as categorias de artigos para array
            $item->content_categories_articles = explode(",", $item->content_categories_articles);

            // Realiza decode dos dados de configuracao do modulo de exibicao de FAQs.
            $configModuleFaqDisplay = json_decode($item->config_module_faqs_display, true);

            // Percorre opcoes de visualizacoo.
            foreach ($configModuleFaqDisplay as $configName => $configValue) {
                // Insere opcao de visualizacao como propriedade no item.
                $item->$configName = $configValue;
            }
        }

        return $item;
    }

        /**
	 * Metodo para validar os dados do formulário 
     * 
     * Funciona apenas para versoes do Joomla a partir de 3.9.25
	 *
	 * @param   JForm   $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 */
	public function validate($form, $data, $group = null) {
		// Exemplo que impede alteracao do 'created_by' (usuario que criou o registro) qnd usuario nao tiver permissoes de acessar o componente com_users
		// if (!JFactory::getUser()->authorise('core.manage', 'com_users')){
		// 	if (isset($data['created_by'])) {
		// 		unset($data['created_by']);
		// 	}
		// }

		return parent::validate($form, $data, $group);
	}

    /**
     * Metodo que altera status do registro
     * 
     * OBS: apesar de estar no model de edicao de registros, essa funcao eh executada na alteracao de status da view de listagem
     *
     * @param   array   &$pks     Ids dos registros a mudar o status
     * @param   int     $value    Id do status
     * 
     * @return  boolean  true ou false
     */
    public function publish(&$pks, $value = 1) {
        /* FIXME: qnd extensao for convertida p/ J5, eh preciso ajustar o caminho aqui.
	use Noboss\Library\Form\Field\Nbmodulesposition\NbmodulespositionHelper;
	Nome da classe muda para: NbmodulespositionHelper
*/
        jimport('noboss.forms.fields.nobossmodulesposition.helper');

        $table = $this->getTable();

        // Percorre os itens que tiveram alteracao de status para modificar tb o status do modulo vinculado
        foreach ($pks as $pk) {
            $table->load($pk, true);
            // Obtem o id do modulo vinculado ao registro
            $moduleId = parent::getItem($pk)->id_module_faqs_display;
            // Modifica status do modulo
            NobossModulePositionHelper::publishModule($moduleId, $value);
        }

        return parent::publish($pks, $value);
    }

    /**
     * Metodo para remover um ou mais grupos, removendo junto os modulos gerados
     *
     * @param   int     &$pks   Ids dos registros a remover
     * 
     * @return boolean  true ou false
     */
    public function delete(&$pks) {
        /* FIXME: qnd extensao for convertida p/ J5, eh preciso ajustar o caminho aqui.
	use Noboss\Library\Form\Field\Nbmodulesposition\NbmodulespositionHelper;
	Nome da classe muda para: NbmodulespositionHelper
*/
        jimport('noboss.forms.fields.nobossmodulesposition.helper');

        $table = $this->getTable();

        // Percorre cada registro solicitado a ser removido
        foreach ((array) $pks as $pk) {
            // Carrega dados do registro para pegar id do modulo
            if ($table->load($pk)){
                // Deleta modulos associados ao grupo.
                NobossModulePositionHelper::deleteModuleGroup($table->id_module_faqs_display);

                // Remove o ID do grupo de todos os registros que possuam ele.
                self::clearRegistersIdGroup($table->{$this->recordIdAlias});
            }
        }

        // Remove registro chamando funcao parent
        return parent::delete($pks);
    }


    /**
     * Metodo que limpa todos os registros nas FAQs que possuam o id do grupo.
     *
     * @param int $idGroup Id do grupo.
     * @return boolean Retorna true se sucesso ou false caso falha.
     */
    public function clearRegistersIdGroup($idGroup) {
        $db = $this->_db;
        // Pega uma nova query.
        $query = $db->getQuery(true);

        // Apaga todos os id do grupos nas FAQs.
        $query
              ->update("#__noboss_faq")
                 ->set( "id_faqs_group = NULL")
               ->where("id_faq = " . (int) $idGroup);
           $db->setQuery($query);

        return $db->execute();
    }
}
