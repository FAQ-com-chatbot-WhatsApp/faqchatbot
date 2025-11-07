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
 * Controlador principal do componente ChatbotHooks
 */
class ChatbotHooksController extends AdminController
{
	/**
	 * O prefixo da URL para redirecionamentos
	 */
	protected $view_list = 'hooks';

	/**
	 * Método para obter o modelo
	 */
	public function getModel($name = 'Hook', $prefix = 'ChatbotHooksModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
}