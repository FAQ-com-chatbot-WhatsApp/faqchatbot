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
 *  Classe Controller para edicao de registros que segue 'Metodo No Boss de desenvolvimento'
 *  @author  Johnny Salazar Reidel
 * 
 *  Orientacao: 
 *      - Edite o nome da classe
 *      - Nesta classe voce pode adicionar metodos especificos de controle, incluindo metodos para requisicoes ajax
 *      - Tambem eh possivel sobreescrever metodos da classe estendida
 */

 // Carrega arquivo helper principal
JLoader::register($input->get('componentClassPrefix').'Helper', JPATH_ADMINISTRATOR . '/components/' . $input->get('option') . '/helpers/'.strtolower($input->get('componentClassPrefix')).'.php');

class NobossfaqControllerGroup extends JControllerForm{
    
}
