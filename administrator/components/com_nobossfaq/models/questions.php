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
 *  Modelo default de model de listagem compativel com J3 e J4
 *  @author  Johnny Salazar Reidel
 * 
 * - Estamos utilizando NobossComponentsModelList da library da No Boss como Trait.
 *      * Traits servem apenas para reuso de codigo, mas nao para heranca. Ou seja, nao eh possivel estender funcoes definidas em NobossComponentsModelList.
 *      * Se houver necessidade de estender alguma funcao de NobossComponentsModelList, copie a funcao original para ca e edite conforme necessario. Neste caso, a funcao executada sera a desta classe e nao mais a definida no trait NobossComponentsModelList
 * 
 *  Orientacao para edicoes minimas necessarias: 
 *      - Edite o nome da classe
 *      - Edite as colunas para ordenacao no metodo '__construct' e informe a ordenacao default da listagem
 *      - Edite a consulta SQL que lista os registros no metodo 'getListQuery'
 */

jimport('noboss.components.model.list');

class NobossfaqModelQuestions extends JModelList {
    use NobossComponentsModelList;

	/**
	 * Construtor
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
     * 
	 */
	public function __construct($config = array()) {
		if (empty($config['filter_fields'])) {
            /**
             * Declare cada coluna do banco que permitira ordenacao na listagem
             * Para cada coluna voce ira declarar:
             *     - Na primeira posicao do array apenas o alias da coluna
             *     - Na segunda posicao do array o prefixo da tabela declarado na consulta SQL + '.' + o alias da coluna
             *     - OBS: se for uma coluna renomeada com alias, informe o alias declarado em apenas uma posicao do array
             */
			$config['filter_fields'] = array(
				'id_faq', 'a.id_faq',
				'question', 'a.question',
				'state', 'a.state',
				'ordering', 'a.ordering',
				'category_name',
				'group_name'
            );
		}

        // Defina a coluna padrao para ordenacao dos registros (informe o prefixo da tabela declarado na consulta SQL + '.' + o alias da coluna)
        $this->orderingDefault = 'a.id_faq';
    
        // Defina a direcao da ordenacao informando 'asc' ou 'desc'
        $this->directionDefault = 'desc'; 

		parent::__construct($config);
	}

    /**
	 * Metodo para obter um id com base no state da configuracao da model.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 */
	protected function getStoreId($id = '') {
		$id	.= ':'.$this->getState('filter.search');
		$id	.= ':'.$this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Metodo para montar a consulta SQL que busca os dados que devem ser listados
	 *
	 * @return  JDatabaseQuery
	 */
	protected function getListQuery() {
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Define os campos de retorno da tabela.
		$query->select(
			$this->getState(
				'list.select',
				'a.id_faq,' .
				'a.id_category,' .
				'a.question,' .
				'a.answer,' .
				'a.state,' .
				'a.created,' .
				'a.modified,' .
				'a.ordering,' .
				'a.created_by,' .
				'a.modified_by,' .
				'a.checked_out,' .
				'a.checked_out_time, ' .
				'faq_group.name_faqs_group AS group_name'
			)
		);
		$query->from($db->quoteName('#__noboss_faq') . ' AS a');

		// Join com categorias.
		$query
			->select('c.title AS category_name')
			->join('LEFT', '#__categories AS c ON c.id = a.id_category');

		// Join com tabela de grupos de faq
		$query
			->join('LEFT', '#__noboss_faq_group AS faq_group ON faq_group.id_faqs_group = a.id_faqs_group');

		// Obtem opcao selecionada no filtro de status (caso definido)
		$state = $this->getState('filter.state');

        // Usuario filtrou por um status na listagem
		if (is_numeric($state)) {
			$query->where('a.state = '.(int) $state);
		}
        // Nao tem filtro de status definido: traz todos status, menos status de lixeira
        else {
			$query->where('(a.state IN (-1,0,1,2))');
		}

        // Obtem opcao selecionada no filtro de idioma (caso definido)
		$language = $this->getState('filter.language');
		if (!empty($language)) {
			$query->where('language = ' . $db->quote($language));
		}

		// Obtem categoria filtrada (caso definido)
		$categoryId = $this->getState('filter.id_category');
		if (is_numeric($categoryId)){
			$query->where('a.id_category = ' . (int) $categoryId);
		}

        // Obtem grupo de faq filtrada (caso definido)
		$groupId = $this->getState('filter.id_faqs_group');
		if (is_numeric($groupId)){
			$query->where('a.id_faqs_group = ' . (int) $groupId);
		}

        // Obtem texto pesquisado (caso definido)
		$search = $this->getState('filter.search');
		if (!empty($search)) {
            // Realiza a pesquisa do texto em colunas especificas do banco
            $search = $db->Quote('%'.$db->escape($search, true).'%');
            $query->where("UPPER(a.question) LIKE " . $search);
            $query->orWhere("UPPER(a.answer) LIKE " . $search);
		}

        // Seta ordenacao conforme solicitado pelo usuario na listagem
        $orderCol  = $this->state->get('list.ordering', 'ordering');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol.' '.$orderDirn));

		return $query;
	}

    /**
	 * Metodo para saber se existe ao menos um grupo de modulo publicado
	 *
	 * @return  boolean     true se existir algum publicado ou false
	 */
    public function isGroupPublished(){
        $db = $this->getDbo();
		$query = $db->getQuery(true);

        $query
			->select('*')
			->from($db->quoteName('#__noboss_faq_group'))
			->where($db->quoteName('state') . ' = 1');
		$db->setQuery($query);
		$db->execute();
        
        // Nao existem grupos de modulos de faq cadastrados
		if(!$db->getNumRows()){
            return false;
        }

        return true;
    }
}
