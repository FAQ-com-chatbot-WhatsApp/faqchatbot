<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

#[\AllowDynamicProperties]
class AutobannedaddressesModel extends ListModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] = $config['filter_fields'] ?? [];
		$config['filter_fields'] = $config['filter_fields'] ?: [
			'ip',
			'reason',
			'until',
		];

		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = 'until', $direction = 'asc')
	{
		$app = Factory::getApplication();

		// If we're under CLI there's nothing to populate
		if ($app->isClient('cli'))
		{
			return;
		}

		$search = $app->getUserStateFromRequest($this->context . 'filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $search);

		$reason = $app->getUserStateFromRequest($this->context . 'filter.reason', 'filter_reason', '', 'string');
		$this->setState('filter.reason', $search);

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.reason');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select('*')
			->from($db->quoteName('#__admintools_ipautoban'));

		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$ip = '%' . $search . '%';

			$query->where($db->quoteName('ip') . ' LIKE :ip')
				->bind(':ip', $ip, ParameterType::STRING);
		}

		$reason = $this->getState('filter.reason');

		if (!empty($reason))
		{
			$query->where($db->quoteName('reason') . ' = :reason')
				->bind(':reason', $reason, ParameterType::STRING);
		}

		// List ordering clause
		$orderCol  = $this->state->get('list.ordering', 'until');
		$orderDirn = $this->state->get('list.direction', 'asc');
		$ordering  = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);

		$query->order($ordering);

		return $query;
	}
}