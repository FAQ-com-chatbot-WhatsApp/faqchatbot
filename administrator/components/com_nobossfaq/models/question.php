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

class NobossfaqModelQuestion extends JModelAdmin {
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
        $this->fieldName = '';
        $this->fieldAlias = '';

        // Alias do campo de id do componente
        $this->recordIdAlias = 'id_faq';

        // Alias do campo de ordenacao (deixar em branco se nao existir)
        $this->fieldOrdering = 'ordering';

        // Nome da tabela do banco
        $this->nameTable = '#__noboss_faq';

        // Sufixo do nome da classe da Table de edicao dos registros. Ex: se a chasse se chamar 'NobossfaqTableFaqgroup' o valor sera 'Faqgroup' que eh o final do nome da classe
        $this->tableClassNameSuffix = 'Faq';

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
        $input = JFactory::getApplication()->input;

        // Usuario solicitou clone de outro registro
        if ($input->get('task') == 'save2copy'){
            // Executa metodo para incrementar um numero no final do nome e alias
            $data = $this->incrementNameAndAlias($data, $this->fieldName, $this->fieldAlias);

            // Deixa registro despublicado
            $data['state'] = 0;
        }

        return parent::save($data);
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
        return parent::getItem($pk);
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
        // Remove registro chamando funcao parent
        return parent::delete($pks);
    }
}
