<?php
/**
 * @package     Noboss.ChatbotMessages
 * @subpackage  com_chatbotmessages
 *
 * @copyright   Copyright (C) 2025 Noboss. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Input\Input;

// Carrega a linguagem
$lang = Factory::getLanguage();
$lang->load('com_chatbotmessages', JPATH_ADMINISTRATOR);

$input = Factory::getApplication()->input;

// Executa o controlador
$controller = BaseController::getInstance('ChatbotMessages');
$controller->execute($input->getCmd('task'));
$controller->redirect();