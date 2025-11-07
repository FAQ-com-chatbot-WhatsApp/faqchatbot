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
 * Modelo de lista para Messages
 */
class ChatbotMessagesModelMessages extends ListModel
{
	/**
	 * Método para construir query SQL
	 */
	protected function getListQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('m.id, m.conversation_id, m.direction, m.type, m.content, m.status, m.sent_at, m.created_at')
			  ->from($db->quoteName('#__messages', 'm'))
			  ->order($db->quoteName('m.created_at') . ' DESC');

		// Filtros
		$direction = $this->getState('filter.direction');
		if (!empty($direction)) {
			$query->where($db->quoteName('m.direction') . ' = ' . $db->quote($direction));
		}

		$status = $this->getState('filter.status');
		if (!empty($status)) {
			$query->where($db->quoteName('m.status') . ' = ' . $db->quote($status));
		}

		return $query;
	}

	/**
	 * Método para configurar estados
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = Factory::getApplication();

		// Filtros
		$this->setState('filter.direction', $app->getUserStateFromRequest('com_chatbotmessages.filter.direction', 'filter_direction'));
		$this->setState('filter.status', $app->getUserStateFromRequest('com_chatbotmessages.filter.status', 'filter_status'));

		parent::populateState('m.created_at', 'desc');
	}
}