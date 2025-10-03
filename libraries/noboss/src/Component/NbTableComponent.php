<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Component;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\ApplicationHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 *  Trait a ser utilizada como apoio em componentes para Table de edicao de registros
 *  @author  Johnny Salazar Reidel
 * 
 *  Observacoes: 
 *      - Traits servem apenas para reuso de codigo, mas nao para heranca. Ou seja, nao eh possivel estender funcoes aqui definidas.
 *           * Se houver necessidade de estender alguma funcao aqui definida no model do componente, copie a funcao para o model e edite conforme necessario. 
 */
trait NbTableComponent {
    /**
     * Metodo que permite verificar os dados antes de serem salvos
     *
     * @return  boolean  True on success.
     * 
     * @throws  \UnexpectedValueException
     */
    public function checkTrait(){
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // Existe field titulo e conteudo nao foi definido
        if ((!empty($this->fieldName)) && trim($this->{$this->fieldName}) == '') {
            throw new \UnexpectedValueException(Text::_('NOBOSS_COMPONENT_TITLE_EMPTY'));
        }

		// Existe field alias
		if(!empty($this->fieldAlias)){
			// Vvalor nao esta preenchido: pega valor do titulo
			if (empty($this->{$this->fieldAlias})) {
				$this->{$this->fieldAlias} = $this->{$this->fieldName};
			}

			// Nao existe field language: define em branco para executar stringURLSafe
			if(!isset($this->language)){
				$this->language = '';
			}

			// Converte valor do alias para formato correto
			$this->{$this->fieldAlias} = ApplicationHelper::stringURLSafe($this->{$this->fieldAlias}, $this->language);
	
			// Nao ha valor valido no alias: coloca data e hora atual como alias
			if (trim(str_replace('-', '', $this->{$this->fieldAlias})) == '') {
				$this->{$this->fieldAlias} = Factory::getDate()->format('Y-m-d-H-i-s');
			}
		}

		// Seta como null se existir campo 'checked_out_time' e ele estiver vazio
		if (isset($this->checked_out_time) && !(int) $this->checked_out_time) {
            $this->checked_out_time = null;
        }

		// Seta como null se existir campo 'publish_up' e ele estiver vazio
        if (isset($this->publish_up) && !(int) $this->publish_up) {
            $this->publish_up = null;
        }

		// Seta como null se existir campo 'publish_down' e ele estiver vazio
        if (isset($this->publish_down) && !(int) $this->publish_down) {
            $this->publish_down = null;
        }

		// Data final de publicacao eh maior que a data inicial
        if (!empty($this->publish_down) && !empty($this->publish_up) && $this->publish_down < $this->publish_up) {
            throw new \UnexpectedValueException('End publish date is before start publish date.');
        }

        return true;
    }

    /**
     * Metodo que estende a classe Table para poder manipular dados antes de serem salvos
     * 
     *
     * @param   boolean  $updateNulls  Verdadeiro para atualizar campos mesmo que sejam nulos.
     *
     * @return  boolean  True on success.
     */
    public function storeTrait($updateNulls = true){
        $date = Factory::getDate();
        $user = $this->getCurrentUser();

        // Edicao de registro
        if (!empty($this->id)) {
			// Existe field 'Alterado por' no formato de alias 'modified_user_id'
			if(isset($this->modified_user_id)){
				$this->modified_user_id = $user->id;
			}

			// Existe field 'Alterado por' no formato de alias 'modified_by'
			if(isset($this->modified_by)){
				$this->modified_by = $user->id;
			}

			// Existe field 'Alterado em' no formato de alias 'modified_time'
			if(isset($this->modified_time)){
				$this->modified_time = $date->toSql();
			}

			// Existe field 'Alterado em' no formato de alias 'modified'
			if(isset($this->modified)){
				$this->modified = $date->toSql();
			}
        }
        // Novo registro
        else {
			// Existe field 'Criado por' no formato de alias 'created_user_id' e nao esta definido
			if(isset($this->created_user_id) && empty($this->created_user_id)){
				$this->created_user_id = $user->id;
			}

			// Existe field 'Criado por' no formato de alias 'created_by' e nao esta definido
			if(isset($this->created_by) && empty($this->created_by)){
				$this->created_by = $user->id;
			}

			// Existe field 'Criado em' no formato de alias 'created_time' e nao esta definido
			if(isset($this->created_time) && (!(int) $this->created_time)){
				$this->created_time = $date->toSql();
			}

			// Existe field 'Criado em' no formato de alias 'created' e nao esta definido
			if(isset($this->created) && (!(int) $this->created)){
				$this->created = $date->toSql();
			}

			// Existe field 'Alterado por' no formato de alias 'modified_user_id' e nao esta definido
			if(isset($this->modified_user_id) && empty($this->modified_user_id)){
				$this->modified_user_id = $this->created_user_id;
			}

			// Existe field 'Alterado por' no formato de alias 'modified_by' e nao esta definido
			if(isset($this->modified_by) && empty($this->modified_by)){
				$this->modified_by = $this->created_by;
			}

			// Existe field 'Alterado em' no formato de alias 'modified_time' e nao esta definido
			if(isset($this->modified_time) && (!(int) $this->modified_time)){
				$this->modified_time = $this->created_time;
			}

			// Existe field 'Alterado em' no formato de alias 'modified' e nao esta definido
			if(isset($this->modified) && (!(int) $this->modified)){
				$this->modified = $this->created;
			}
        }

		// Existe field alias e esta setado para ser validado
		if(!empty($this->fieldAlias) && (!isset($this->fieldAliasValid) || $this->fieldAliasValid == true)){
			// Verifica se o alias eh unico
			$table = new static($this->getDbo());

			// Existe outro alias igual ao registro atual
			if ($table->load(['alias' => $this->{$this->fieldAlias}]) && ($table->{$this->recordIdAlias} != $this->{$this->recordIdAlias} || $this->{$this->recordIdAlias} == 0)) {
				$this->setError(Text::_('NOBOSS_COMPONENT_ERROR_UNIQUE_ALIAS'));
	
				// O alias que existe esta na lixeira
				if ((isset($table->published) && $table->published === -2) || (isset($table->state) && $table->state === -2)) {
					$this->setError(Text::_('NOBOSS_COMPONENT_ERROR_UNIQUE_ALIAS_TRASHED'));
				}
	
				return false;
			}
		}

        return parent::store($updateNulls);
    }
	
	/**
	 * Metodo para alterar status de um ou mais registros na view de listagem de registros
	 * 
	 * Nota: estamos estendendo a funcao do Joomla apenas para poder determinar outros nomes para coluna de publicacao (joomla espera por padrao que a coluna se chame sempre 'published')
	 *
	 * @param   mixed	    $pks        Ids dos registros a mudar status
	 * @param   integer     $state      Status a aplicar
	 * @param   integer     $userId     Id do usuario que modificou status
     * 
	 * @return  boolean  True se sucesso.
	 */
	public function publish($pks = null, $state = 1, $userId = 0) {
		// Componente usa coluna com nome 'state': forca que Joomla considere 'state' ao inves de 'published'
		if(isset($this->state)){
			$this->setColumnAlias('published', 'state');
		}

		return parent::publish($pks, $state, $userId);
	}
}
?>
