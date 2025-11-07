<?php
/**
 * @package     Noboss.ChatbotMessages
 * @subpackage  com_chatbotmessages
 *
 * @copyright   Copyright (C) 2025 Noboss. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

/**
 * Modelo de lista para Hooks
 */
class ChatbotHooksModelHooks extends ListModel
{
	/**
	 * Método para construir query SQL
	 */
	protected function getListQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('h.id, h.description, h.created_at, h.updated_at')
			  ->from($db->quoteName('#__hooks', 'h'))
			  ->order($db->quoteName('h.created_at') . ' DESC');

		return $query;
	}
}