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
 *  Modelo default de arquivo principal compativel com J3 e J4
 *  @author  Johnny Salazar Reidel
 * 
 *  Orientacao: edite somente os valores para as variaveis 'componentClassPrefix' e 'defaultView'
 */

$input = JFactory::getApplication()->input;

// Prefixo utilizado no inicio dos nomes das classes PHP
$input->set('componentClassPrefix', 'Nobossfaq');
// View de listagem default do componente
$input->set('defaultView', 'questions');

// Usuario nao tem permissao de acesso ao componente
if (!JFactory::getUser()->authorise('core.manage', $input->get('option'))) {
	throw new JAccessExceptionNotallowed(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}

// Carrega controler correspondente a task acessada
$controller = JControllerLegacy::getInstance($input->get('componentClassPrefix'));
$controller->execute($input->get('task'));
$controller->redirect();
