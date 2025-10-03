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
class ScanalertsModel extends ListModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] = $config['filter_fields'] ?? [];
		$config['filter_fields'] = $config['filter_fields'] ?: [
			'search',
			'id',
			'path',
			'scan_id',
			'diff',
			'threat_score',
			'acknowledged',
			'status',
			'newfile',
			'suspicious',
			'filestatus',
		];

		parent::__construct($config, $factory);
	}

	/**
	 * Mark all entries of the specified scan as safe.
	 *
	 * @param   int  $scan_id  The ID of the scan
	 *
	 * @since   5.2.1
	 */
	public function markAllSafe($scan_id)
	{
		$scan_id = max(0, (int) $scan_id);

		if ($scan_id == 0)
		{
			return;
		}

		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->update($db->qn('#__admintools_scanalerts'))
			->set([
				$db->qn('acknowledged') . ' = ' . $db->q(1),
			])
			->where($db->qn('scan_id') . ' = ' . $db->q($scan_id))
			->where($db->qn('threat_score') . ' > ' . $db->q(0));
		$db->setQuery($query)->execute();
	}

	protected function populateState($ordering = 'path', $direction = 'asc')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . 'filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $search);

		$status = $app->getUserStateFromRequest($this->context . 'filter.status', 'filter_status', '', 'string');
		$this->setState('filter.status', $status);

		$scan_id = $app->getUserStateFromRequest($this->context . 'filter.scan_id', 'filter_scan_id', '', 'string');
		$this->setState('filter.scan_id', ($scan_id === '') ? $scan_id : (int) $scan_id);

		$acknowledged = $app->getUserStateFromRequest($this->context . 'filter.acknowledged', 'filter_acknowledged', '', 'string');
		$this->setState('filter.acknowledged', ($acknowledged === '') ? $acknowledged : (int) $acknowledged);

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.scan_id');
		$id .= ':' . $this->getState('filter.status');
		$id .= ':' . $this->getState('filter.acknowledged');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db = $this->getDatabase();

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->quoteName('a.id'),
				'IF(' . $db->quoteName('diff') . ' = "" AND '.$db->quoteName('threat_score').' = 0,1,0) AS ' . $db->quoteName('newfile'),
				'IF(' . $db->quoteName('diff') . ' LIKE ' . $db->q('###SUSPICIOUS FILE###%') . ' OR (' . $db->quoteName('diff') . ' = ' . $db->quote("") . ' AND ' . $db->quoteName('threat_score') . ' > 0), 1, 0) AS ' . $db->quoteName('suspicious'),
				'IF('.$db->quoteName('diff').' = '.$db->quote("").' AND '.$db->quoteName('threat_score').' = 0, '.$db->quote('1-new').', IF('.$db->quoteName('diff').' LIKE '.$db->quote('###SUSPICIOUS FILE###%').' OR ('.$db->quoteName('diff').' = '.$db->quote('').' AND '.$db->quoteName('threat_score').' > 0), '.$db->quote('0-suspicious').', '.$db->quote('2-modified').')) AS ' . $db->quoteName('filestatus'),

				'CASE WHEN ' . $db->quoteName('threat_score') . ' = 0 THEN ' . $db->quote('none') .
				'WHEN ' . $db->quoteName('threat_score') . ' < 10 THEN ' . $db->quote('low') .
				'WHEN ' . $db->quoteName('threat_score') . ' < 100 THEN ' . $db->quote('medium') .
				'ELSE ' . $db->quote('high') . ' END AS ' . $db->quoteName('threatindex'),
				$db->qn('path'),
				$db->qn('threat_score'),
				$db->qn('acknowledged'),
				$db->qn('scan_id'),
				$db->qn('s.scanstart', 'scandate'),
			])
			->from($db->quoteName('#__admintools_scanalerts', 'a'))
			->join('inner', $db->quoteName('#__admintools_scans', 's'),
				$db->quoteName('s.id') . ' = ' . $db->quoteName('a.scan_id')
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

				$query->where($db->quoteName('path') . ' LIKE :search')
					->bind(':search', $search, ParameterType::STRING);
			}
		}

		$scan_id = $this->getState('filter.scan_id');

		if (is_numeric($scan_id))
		{
			$scan_id = (int) $scan_id;

			$query->where($db->quoteName('scan_id') . ' = :scan_id')
				->bind(':scan_id', $scan_id);
		}

		$status = $this->getState('filter.status');

		switch ($status)
		{
			case 'new':
				$query->where('IF(' . $db->qn('diff') . ' != "",0,1) = ' . $db->q(1));
				break;

			case 'suspicious':
				$query->where('IF(' . $db->qn('diff') . ' LIKE "###SUSPICIOUS FILE###%",1,0)  = ' . $db->q(1));
				break;

			case 'modified':
				$query->where('IF(' . $db->qn('diff') . ' != "",0,1) = ' . $db->q(0));
				$query->where('IF(' . $db->qn('diff') . ' LIKE "###SUSPICIOUS FILE###%",1,0)  = ' . $db->q(0));
				break;
		}

		$acknowledged = $this->getState('filter.acknowledged');

		if (is_numeric($acknowledged))
		{
			$acknowledged = (int) $acknowledged;

			$query->where($db->quoteName('acknowledged') . ' = :acknowledged')
				->bind(':acknowledged', $acknowledged, ParameterType::INTEGER);
		}

		// List ordering clause
		$orderCol  = $this->state->get('list.ordering', 'path');
		$orderDirn = $this->state->get('list.direction', 'asc');
		$ordering  = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);

		$query->order($ordering);

		return $query;
	}
}