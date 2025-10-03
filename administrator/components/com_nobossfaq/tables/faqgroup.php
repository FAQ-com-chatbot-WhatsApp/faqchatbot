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
 *  Classe Table para edicao de registros que segue 'Metodo No Boss de desenvolvimento'
 *  @author  Johnny Salazar Reidel
 * 
 *  Orientacoes:
 *    - Estamos utilizando NobossComponentsTable da library da No Boss como Trait.
 *      * Traits servem apenas para reuso de codigo, mas nao para heranca. Ou seja, nao eh possivel estender funcoes definidas em NobossComponentsTable.
 *      * Se houver necessidade de estender alguma funcao de NobossComponentsTable, copie a funcao original para ca e edite conforme necessario. Neste caso, a funcao executada sera a desta classe e nao mais a definida no trait NobossComponentsTable
 *      
 *    - Altere o nome desta classe
 *    - Defina os parametros sinalizados no metodo __construct()
 *    - No metodo 'check()' eh possivel definir validacoes antes de salvar
 *    - No metodo 'store()' eh possivel adicionar tratamento de dados antes de salvar
 */

jimport('noboss.components.table.table');

class NobossfaqTableFaqgroup extends JTable {
    use NobossComponentsTable;

	/**
	 * Construtor
	 *
	 * @param   JDatabaseDriver  &$db  A database connector object
	 */
	public function __construct(&$db) {
        // Alias do campo de id do componente
        $recordIdAlias = 'id_faqs_group';

        // Nome da tabela do banco
        $nameTable = '#__noboss_faq_group';

		parent::__construct($nameTable, $recordIdAlias, $db);

        // Utilizado qnd queremos dar uma alternativa de alias para uma coluna (verificar melhor se fica funcionando os dois alias ou apenas o novo setado)
        // $this->setColumnAlias('title', 'name');

        // Utilizado qnd temos historico de versoes
        // JTableObserverContenthistory::createObserver($this, array('typeAlias' => 'com_newsfeeds.newsfeed'));
	}

    /**
	 * Metodo que permite verificar os dados antes de serem salvos
	 *
	 * @return  boolean  True on success.
	 */
	public function check()	{
        // Quando tiver um valor invalido, pode setar uma mensagem de erro e retonar false na funcao. Ex:
        // if (trim($this->name) == ''){
        //     $this->setError(JText::_('COM_NEWSFEEDS_WARNING_PROVIDE_VALID_NAME'));
        //     return false;
        // }

        return true;
    }

    /**
	 * Metodo que estende a classe JTable para poder manipular dados antes de serem salvos
     * 
     * OBS: se estiver usando essa table a partir do model, recomenda-se deixar a alteracao dos dados nas funcoes 'save()' ou prepareTable()' do proprio model
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 */
	public function store($updateNulls = false) {
        return parent::store($updateNulls);
    }
}
