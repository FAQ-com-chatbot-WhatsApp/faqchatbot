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
class WafexceptionsModel extends ListModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] = $config['filter_fields'] ?? [];
		$config['filter_fields'] = $config['filter_fields'] ?: [
			'search',
			'id',
			'option',
			'view',
			'query',
		];

		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = 'id', $direction = 'desc')
	{
		$app = Factory::getApplication();

		// If we're under CLI there's nothing to populate
		if ($app->isClient('cli'))
		{
			return;
		}

		$search = $app->getUserStateFromRequest($this->context . 'filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $search);

		$option = $app->getUserStateFromRequest($this->context . 'filter.option', 'filter_option', '', 'string');
		$this->setState('filter.option', $option);

		$view = $app->getUserStateFromRequest($this->context . 'filter.view', 'filter_view', '', 'string');
		$this->setState('filter.view', $view);

		$query = $app->getUserStateFromRequest($this->context . 'filter.query', 'filter_query', '', 'string');
		$this->setState('filter.query', $query);

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.option');
		$id .= ':' . $this->getState('filter.view');
		$id .= ':' . $this->getState('filter.query');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->quoteName('w') . '.*',
				$db->quoteName('e.name')
			])
			->from($db->quoteName('#__admintools_wafexceptions', 'w'))
			->leftJoin($db->quoteName('#__extensions', 'e'),
				$db->quoteName('e.element') . ' = ' . $db->quoteName('w.option')
			);

		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (substr($search, 0, 3) === 'id:')
			{
				$id = (int) substr($search, 3);

				$query->where($db->quoteName('id') . ' = :id')
					->bind(':id', $id, ParameterType::INTEGER);
			}
			else
			{
				$search = '%' . $search . '%';

				$query->where($db->quoteName('option') . ' LIKE :search')
					->bind(':search', $search, ParameterType::STRING);
			}
		}

		$textFilters = ['option', 'view', 'query'];

		foreach ($textFilters as $filterName)
		{
			$filter = $this->getState('filter.' . $filterName);

			if (!empty($filter))
			{
				$query->where($db->quoteName($filterName) . ' = :' . $filterName)
					->bind(':' . $filterName, $filter, ParameterType::STRING);
			}
		}

		// List ordering clause
		$orderCol  = $this->state->get('list.ordering', 'id');
		$orderDirn = $this->state->get('list.direction', 'desc');
		$ordering  = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);

		$query->order($ordering);

		return $query;
	}

}