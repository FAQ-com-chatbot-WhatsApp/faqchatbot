<?php
/**
 * @package     Noboss.ChatbotMessages
 * @subpackage  com_chatbotmessages
 *
 * @copyright   Copyright (C) 2025 Noboss. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

/**
 * Controlador principal do componente ChatbotMessages
 */
class ChatbotMessagesController extends AdminController
{
	/**
	 * O prefixo da URL para redirecionamentos
	 */
	protected $view_list = 'messages';

	/**
	 * Método para obter o modelo
	 */
	public function getModel($name = 'Message', $prefix = 'ChatbotMessagesModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
}