<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Table\DisallowlistTable;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\MVC\Model\State as PointlessContraption;
use Joomla\CMS\Object\CMSObject;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;

/**
 * @method $this groupbydate() groupbydate(int $value)
 * @method $this groupbytype() groupbytype(int $value)
 * @method $this datefrom() datefrom(string $value)
 * @method $this dateto() dateto(string $value)
 * @method $this ip() ip(string $value)
 * @method $this url() url(string $value)
 * @method $this reason() reason(string $value)
 */
#[\AllowDynamicProperties]
class BlockedrequestslogsModel extends ListModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] = $config['filter_fields'] ?? [];
		$config['filter_fields'] = $config['filter_fields'] ?: [
			'search', 'ip', 'logdate', 'id', 'extradata',
			'groupbydate', 'groupbytype',
			'block', 'datefrom', 'dateto',
			'url', 'reason',
		];

		parent::__construct($config, $factory);

		$this->filterFormName = 'filter_blockedrequestslog';
	}

	protected function populateState($ordering = 'logdate', $direction = 'desc')
	{
		$app = Factory::getApplication();

		// If we're under CLI there's nothing to populate
		if ($app->isClient('cli'))
		{
			return;
		}

		$ip = $app->getUserStateFromRequest($this->context . 'filter.ip', 'ip', '', 'string');
		$search = $app->getUserStateFromRequest($this->context . 'filter.search', 'filter_search', $ip, 'string');
		$this->setState('filter.search', $search);
		$this->setState('ip', null);

		$groupbydate = $app->getUserStateFromRequest($this->context . 'filter.groupbydate', 'groupbydate', '', 'string');
		$this->setState('groupbydate', ($groupbydate === '') ? $groupbydate : (int) $groupbydate);

		$groupbytype = $app->getUserStateFromRequest($this->context . 'filter.groupbytype', 'groupbytype', '', 'string');
		$this->setState('groupbytype', ($groupbytype === '') ? $groupbytype : (int) $groupbytype);

		$datefrom = $app->getUserStateFromRequest($this->context . 'filter.datefrom', 'datefrom', '', 'string');
		$this->setState('datefrom', $datefrom);

		$dateto = $app->getUserStateFromRequest($this->context . 'filter.dateto', 'dateto', '', 'string');
		$this->setState('dateto', $dateto);

		$url = $app->getUserStateFromRequest($this->context . 'filter.url', 'url', '', 'string');
		$this->setState('url', $url);

		$reason = $app->getUserStateFromRequest($this->context . 'filter.reason', 'reason', '', 'string');
		$this->setState('reason', $reason);

		parent::populateState($ordering, $direction);
	}

	public function __call($name, $arguments)
	{
		$this->setState($name, ...$arguments);

		return $this;
	}

	public function resetState()
	{
		// Joomla 4: CMSObject
		if (str_starts_with(JVERSION, '4.'))
		{
			/** @noinspection PhpDeprecationInspection */
			$this->state = new CMSObject;

			return $this;
		}

		// Joomla 5: Using the transitional State object
		if (str_starts_with(JVERSION, '5.'))
		{
			/** @noinspection PhpDeprecationInspection */
			$this->state = new PointlessContraption();

			return $this;
		}

		// Joomla 6 and beyond: Using the new Registry object
		$this->state = new Registry();

		return $this;
	}

	public function ban(int $id): bool
	{
		$item = $this->getTable();

		if (empty($id) || !$item->load($id))
		{
			throw new Exception(Text::_('COM_ADMINTOOLS_LOG_ERR_NOID'), 500);
		}

		/** @var DisallowlistTable $block */
		$block = $this->getTable('Disallowlist');
		$block->reset();

		try
		{
			$ret          = $block->save(
				[
					'ip'          => $item->ip,
					'description' => Text::_('COM_ADMINTOOLS_LOG_LBL_REASON_' . $item->reason),
				]
			);
			/** @noinspection PhpDeprecationInspection qualified access will work when getError is removed */
			$errorMessage = method_exists($block, 'getError') ? $block->getError() : '';
		}
		catch (Exception $e)
		{
			$ret          = false;
			$errorMessage = $e->getMessage();
		}

		if (!$ret)
		{
			if (!method_exists($this, 'setError'))
			{
				throw new \RuntimeException($errorMessage);
			}

			/** @noinspection PhpDeprecationInspection only called when deprecated code is not removed */
			$this->setError($errorMessage);
		}

		return $ret;
	}

	public function unban(int $id): void
	{
		$item = $this->getTable();

		if (empty($id) || !$item->load($id))
		{
			throw new Exception(Text::_('COM_ADMINTOOLS_LOG_ERR_NOID'), 500);
		}

		$db = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->delete($db->quoteName('#__admintools_ipblock'))
			->where($db->quoteName('ip') . ' = :ip')
			->bind(':ip', $item->ip);
		$db->setQuery($query)->execute();
	}

	public function getTable($name = 'Blockedrequestslog', $prefix = '', $options = array())
	{
		return parent::getTable($name, $prefix, $options);
	}

	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('groupbydate');
		$id .= ':' . $this->getState('groupbytype');
		$id .= ':' . $this->getState('datefrom');
		$id .= ':' . $this->getState('dateto');
		$id .= ':' . $this->getState('ip');
		$id .= ':' . $this->getState('url');
		$id .= ':' . $this->getState('reason');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->from($db->quoteName('#__admintools_log', 'l'));

		if ($this->getState('groupbydate', 0) == 1)
		{
			$query->select([
				'DATE(' . $db->qn('l.logdate') . ') AS ' . $db->qn('date'),
				'COUNT(' . $db->qn('l.id') . ') AS ' . $db->qn('exceptions'),
			]);
		}
		elseif ($this->getState('groupbytype', 0) == 1)
		{
			$query->select([
				$db->quoteName('l.reason'),
				'COUNT(' . $db->qn('l.id') . ') AS ' . $db->qn('exceptions'),
			]);
		}
		else
		{
			$query
				->select([
					$db->quoteName('l') . '.*',
					'CASE COALESCE(' . $db->quoteName('b.ip') . ', ' . $db->quote(0) . ') WHEN ' .
					$db->quote(0) . ' THEN ' . $db->quote('0') . ' ELSE ' . $db->quote('1') .
					' END AS ' . $db->quoteName('block'),
				])
				->join('LEFT OUTER',
					$db->quoteName('#__admintools_ipblock', 'b'),
					$db->quoteName('b.ip') . ' = ' . $db->quoteName('l.ip')
				);
		}

		$app         = Factory::getApplication();
		$timezone    = $app->get('offset', 'UTC');
		$fltDateFrom = $this->getState('datefrom', null);


		// Grab the timezone only if we're not under CLI
		if (!$app->isClient('cli'))
		{
			$user        = $app->getIdentity();
			$timezone    = $user->getParam('timezone', $app->get('offset', 'UTC'));
		}

		if ($fltDateFrom)
		{
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

			if (!preg_match($regex, $fltDateFrom) || (substr($fltDateFrom, 0, 1) == '-') || (substr($fltDateFrom, 0, 2) == '00'))
			{
				$fltDateFrom = '2000-01-01 00:00:00';
				$this->setState('datefrom', '');
			}

			$date = clone Factory::getDate($fltDateFrom, $timezone);
			$date->setTime(0, 0, 0);
			$sqlDateFrom = $date->toSql();
			$query->where($db->quoteName('logdate') . ' >= :sqlDateFrom')
				->bind(':sqlDateFrom', $sqlDateFrom);
		}

		$fltDateTo = $this->getState('dateto', null);

		if ($fltDateTo)
		{
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

			if (!preg_match($regex, $fltDateTo) || (substr($fltDateTo, 0, 1) == '-') || (substr($fltDateTo, 0, 2) == '00'))
			{
				$fltDateTo = '2037-01-01 00:00:00';
				$this->setState('dateto', '');
			}

			$date = clone Factory::getDate($fltDateTo, $timezone);
			$date->setTime(23, 59, 59);
			$sqlDateTo = $date->toSql();

			$query->where($db->quoteName('logdate') . ' <= :sqlDateTo')
				->bind(':sqlDateTo', $sqlDateTo);
		}

		$fltIP = $this->getState('filter.search', null);
		$fltIP = $this->getState('ip', $fltIP);

		if ($fltIP)
		{
			$fltIP = '%' . $fltIP . '%';
			$query->where($db->quoteName('l.ip') . ' LIKE :ftpIp')
				->bind(':ftpIp', $fltIP);
		}

		$fltURL = $this->getState('url', null);

		if ($fltURL)
		{
			$fltURL = '%' . $fltURL . '%';
			$query->where($db->quoteName('url') . ' LIKE :url')
				->bind(':url', $fltURL);
		}

		$fltReason = $this->getState('filter.reason', null);

		if ($fltReason)
		{
			$query->where($db->quoteName('reason') . ' = :reason')
				->bind(':reason', $fltReason);
		}

		$this->_buildQueryGroup($query);

		if ($this->getState('groupbydate', 0) == 1)
		{
			$query->order('DATE(' . $db->quoteName('l.logdate') . ') ASC');
		}
		elseif ($this->getState('groupbytype', 0) == 1)
		{
			$query->order($db->quoteName('l.reason') . ' ASC');
		}
		else
		{
			$orderCol  = $this->state->get('list.ordering', 'logdate');
			$orderDirn = $this->state->get('list.direction', 'DESC');
			$ordering  = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);

			$query->order($ordering);
		}

		return $query;
	}

	protected function _buildQueryGroup(QueryInterface $query): void
	{
		$db = $this->getDatabase();

		if ($this->getState('groupbydate', 0) == 1)
		{
			$query->group([
				'DATE(' . $db->quoteName('l.logdate') . ')',
			]);
		}
		elseif ($this->getState('groupbytype', 0) == 1)
		{
			$query->group([
				$db->quoteName('l.reason'),
			]);
		}
	}


}