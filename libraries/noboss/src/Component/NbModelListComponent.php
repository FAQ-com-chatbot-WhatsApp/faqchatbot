<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Component;

// use Joomla\String\StringHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 *  Trait a ser utilizada como apoio em componentes para model de listagem de registros
 *  @author  Johnny Salazar Reidel
 * 
 *  Observacoes: 
 *      - Traits servem apenas para reuso de codigo, mas nao para heranca. Ou seja, nao eh possivel estender funcoes aqui definidas.
 *           * Se houver necessidade de estender alguma funcao aqui definida no model do componente, copie a funcao para o model e edite conforme necessario. 
 *      - O funcionamento desta classe tem como requisito que o componente seja desenvolvido no modelo No Boss
 */

trait NbModelListComponent {
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
}
