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
 *  Classe Controller para listagem de registros que segue 'Metodo No Boss de desenvolvimento'
 *  @author  Johnny Salazar Reidel
 * 
 *  Orientacao: 
 *      - Edite o nome da classe e mude o valor da variavel $name na funcao getModel
 *      - Nesta classe voce tambem pode adicionar metodos especificos de controle, incluindo metodos para requisicoes ajax
 */

class NobossfaqControllerGroups extends JControllerAdmin{
	
    /**
	 * Metodo para obter um objeto model, carregando-o se necessário.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 */
	public function getModel($name = '', $prefix = '', $config = array('ignore_request' => true)){
        if(empty($name)){
            // Sufixo do nome da classe do model de edicao dos registros. Ex: se a chasse se chamar 'NobossfaqControllerGroup' o valor sera 'Group' que eh o final do nome da classe
            $name = 'Group';
        }
        
        if(empty($prefix)){
            $prefix = JFactory::getApplication()->input->get('componentClassPrefix').'Model';
        }

		return parent::getModel($name, $prefix, $config);
	}
}
