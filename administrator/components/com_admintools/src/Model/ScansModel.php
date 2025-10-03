<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Scanner\Crawler;
use Akeeba\Component\AdminTools\Administrator\Scanner\Email;
use Akeeba\Component\AdminTools\Administrator\Scanner\Logger\Logger;
use Akeeba\Component\AdminTools\Administrator\Scanner\Part;
use Akeeba\Component\AdminTools\Administrator\Scanner\Util\Configuration;
use Akeeba\Component\AdminTools\Administrator\Scanner\Util\Session;
use Akeeba\Component\AdminTools\Administrator\Scanner\Util\Timer;
use Akeeba\Component\AdminTools\Administrator\Table\ScanTable;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

#[\AllowDynamicProperties]
class ScansModel extends ListModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] = $config['filter_fields'] ?? [];
		$config['filter_fields'] = $config['filter_fields']
			?: [
				'search',
				'since',
				'until',
				'id',
				'comment',
				'scanstart',
				'scanend',
				'status',
				'origin',
				'totalfiles',
				'files_new',
				'files_modified',
				'files_suspicious',
			];

		parent::__construct($config, $factory);
	}

	public function removeIncompleteScans()
	{
		$failedStatuses = ['fail', 'run'];

		$db         = $this->getDatabase();
		$innerQuery = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([$db->quoteName('id')])
			->from($db->quoteName('#__admintools_scanalerts'))
			->whereIn($db->quoteName('status'), $failedStatuses, ParameterType::STRING);

		$deleteAlerts = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->delete($db->quoteName('#__admintools_scanalerts'))
			->where($db->quoteName('scan_id') . ' IN(' . $innerQuery . ')');

		$deleteScans = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->delete($db->quoteName('#__admintools_scans'))
			->whereIn($db->quoteName('status'), $failedStatuses, ParameterType::STRING);

		$db->transactionStart();
		try
		{
			$db->setQuery($deleteAlerts)->execute();
			$db->setQuery($deleteScans)->execute();
			$db->transactionCommit();
		}
		catch (\Exception $e)
		{
			$db->transactionRollback();
		}
	}

	/**
	 * Clears the table with files information
	 *
	 * @return  bool
	 */
	public function purgeFilesCache()
	{
		try
		{
			$this->getDatabase()->truncateTable('#__admintools_filescache');

			return true;
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	/**
	 * Starts a new file scan
	 *
	 * @return  array
	 */
	public function startScan($origin = 'backend')
	{
		if (function_exists('set_time_limit'))
		{
			@set_time_limit(0);
		}

		// Get the scanner engine's base objects (configuration, session storage and logger)
		$configuration = Configuration::getInstance();
		$session       = Session::getInstance();
		$logger        = new Logger($configuration);

		// Log the start of a new scan
		$logger->reset();
		$logger->info(sprintf("Admin Tools Professional %s (%s)", ADMINTOOLS_VERSION, ADMINTOOLS_DATE));
		$logger->info('PHP File Change Scanner');
		$logger->info('Starting a new scan from the “' . $origin . '” origin.');

		// Get a timer according to the engine's configuration
		$maxExec     = $configuration->get('maxExec');
		$runtimeBias = $configuration->get('runtimeBias');
		$logger->debug(
			sprintf("Getting a new operations timer, max. exec. time %0.2fs, runtime bias %u%%", $maxExec, $runtimeBias)
		);
		$timer = new Timer($maxExec, $runtimeBias);

		// Reset the session. This marks a brand new scan.
		$logger->debug('Resetting the session storage');
		$session->reset();

		// Create a new scan record and save its ID in the session
		$logger->debug('Creating a new scan record');
		$currentTime = clone Factory::getDate();
		/** @var ScanTable $newScanRecord */
		$newScanRecord = $this->getTable('Scan', 'Administrator');
		$newScanRecord->save(
			[
				'id'         => null,
				'scanstart'  => $currentTime->toSql(),
				'status'     => 'run',
				'origin'     => $origin,
				'totalfiles' => 0,
			]
		);
		$logger->debug(sprintf('Scan ID: %u', $newScanRecord->getId()));
		$session->set('scanID', $newScanRecord->getId());

		// Run the scanner engine
		$statusArray = $this->tickScannerEngine($configuration, $session, $logger, $timer, true);

		return $this->postProcessStatusArray($statusArray, $logger);
	}

	/**
	 * Steps the file scan
	 *
	 * @return  array
	 */
	public function stepScan()
	{
		// Get the scanner engine's base objects (configuration, session storage and logger)
		$configuration = Configuration::getInstance();
		$session       = Session::getInstance();
		$logger        = new Logger($configuration);

		// Get a timer according to the engine's configuration
		$maxExec     = $configuration->get('maxExec');
		$runtimeBias = $configuration->get('runtimeBias');
		$logger->debug(
			sprintf("Getting a new operations timer, max. exec. time %0.2fs, runtime bias %u%%", $maxExec, $runtimeBias)
		);
		$timer = new Timer($maxExec, $runtimeBias);

		// Run the scanner engine
		$statusArray = $this->tickScannerEngine($configuration, $session, $logger, $timer, true);

		return $this->postProcessStatusArray($statusArray, $logger);
	}

	protected function populateState($ordering = 'id', $direction = 'desc')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . 'filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $search);

		$since = $app->getUserStateFromRequest($this->context . 'filter.since', 'filter_since', '', 'string');
		$this->setState('filter.since', $since);

		$until = $app->getUserStateFromRequest($this->context . 'filter.until', 'filter_until', '', 'string');
		$this->setState('filter.until', $until);

		$status = $app->getUserStateFromRequest($this->context . 'filter.status', 'filter_status', '', 'string');
		$this->setState('filter.status', $status);

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.since');
		$id .= ':' . $this->getState('filter.until');
		$id .= ':' . $this->getState('filter.status');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db        = $this->getDatabase();
		$metaQuery = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true));
		$metaQuery
			->select(
				[
					$db->quoteName('scan_id'),
					'SUM(IF(' . $db->quoteName('diff') . ' = ' . $db->quote('') . ', 1, 0)) AS ' . $db->quoteName(
						'new'
					),
					'SUM(IF(' . $db->quoteName('diff') . ' = ' . $db->quote('') . ' OR '.$db->quoteName('diff').' = '.$db->quote("###SUSPICIOUS FILE###\n").', 0, 1)) AS ' . $db->quoteName(
						'modified'
					),
					'SUM(IF(' . $db->quoteName('threat_score') . ' > 0 AND ' . $db->quoteName('acknowledged')
					. ' = 0, 1, 0)) AS ' . $db->quoteName('suspicious'),
				]
			)
			->from($db->quoteName('#__admintools_scanalerts'))
			->group($db->quoteName('scan_id'));

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select(
				[
					$db->quoteName('s') . '.*',
					$db->quoteName('meta.new', 'files_new'),
					$db->quoteName('meta.modified', 'files_modified'),
					$db->quoteName('meta.suspicious', 'files_suspicious'),
				]
			)
			->from($db->quoteName('#__admintools_scans', 's'))
			->join(
				'left', "($metaQuery) AS " . $db->quoteName('meta'),
				$db->quoteName('meta.scan_id') . ' = ' . $db->quoteName('s.id')
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

				$query->where($db->quoteName('comment') . ' LIKE :search')
					->bind(':search', $search, ParameterType::STRING);
			}
		}

		$since = $this->getState('filter.since');
		$until = $this->getState('filter.until');

		try
		{
			$since = empty($since) ? null : (clone Factory::getDate($since))->toSql();
		}
		catch (\Exception $e)
		{
			$since = null;
		}

		try
		{
			$until = empty($until) ? null : (clone Factory::getDate($until))->toSql();
		}
		catch (\Exception $e)
		{
			$until = null;
		}

		if (!empty($since) && !empty($until))
		{
			$query->where($db->quoteName('scanstart') . ' BETWEEN :since AND :until')
				->bind(':since', $since)
				->bind(':until', $until);
		}
		elseif (!empty($since))
		{
			$query->where($db->quoteName('scanstart') . ' >= :since')
				->bind(':since', $since);
		}
		elseif (!empty($until))
		{
			$query->where($db->quoteName('scanstart') . ' <= :until')
				->bind(':until', $until);
		}

		$status = $this->getState('filter.status');

		if (!empty($status))
		{
			$query->where($db->quoteName('status') . ' = :status')
				->bind(':status', $status);
		}

		// List ordering clause
		$orderCol  = $this->state->get('list.ordering', 'id');
		$orderDirn = $this->state->get('list.direction', 'desc');
		$ordering  = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);

		$query->order($ordering);

		return $query;
	}

	private function postProcessStatusArray(array $statusArray, Logger $logger)
	{
		// Get the current scan record
		$session       = Session::getInstance();
		$configuration = Configuration::getInstance();
		$scanID        = $session->get('scanID');
		/** @var ScanTable $scanRecord */
		$scanRecord = $this->getTable('Scan', 'Administrator');
		if (!$scanRecord->load($scanID))
		{
			throw new \RuntimeException(sprintf('Scan record %d not found', $scanID));
		}
		$currentTime = clone Factory::getDate();
		$warnings    = $logger->getAndResetWarnings();

		// Apply common updates to the backup record
		$scanRecord->bind(
			[
				'totalfiles' => $session->get('scannedFiles'),
				'scanend'    => $currentTime->toSql(),
			]
		);

		// More work to do
		if ($statusArray['HasRun'] && (empty($statusArray['Error'])))
		{
			$logger->debug('** More work necessary. Will resume in the next step.');

			$scanRecord->save(
				[
					'status' => 'run',
				]
			);

			// Still have work to do
			return [
				'status'   => true,
				'done'     => false,
				'error'    => '',
				'warnings' => $warnings,
			];
		}

		// An error occurred
		if (!empty($statusArray['Error']))
		{
			$logger->debug('** An error occurred. The scan has died.');

			$scanRecord->save(
				[
					'status' => 'fail',
				]
			);
			$session->reset();

			return [
				'status'   => false,
				'done'     => true,
				'error'    => $statusArray['Error'],
				'warnings' => $warnings,
			];
		}

		// Just finished
		// -- Send emails, if necessary
		if ($scanRecord->origin != 'backend')
		{
			$logger->debug('Finished scanning. Evaluating whether to send email with scan results.');
			$email = new Email($configuration, $session, $logger);
			$email->sendEmail();
		}

		$logger->debug('** This scan is now finished.');
		$scanRecord->save(
			[
				'status' => 'complete',
			]
		);
		$session->reset();

		return [
			'status'   => true,
			'done'     => true,
			'error'    => '',
			'warnings' => $warnings,
		];
	}

	/**
	 * @param   Configuration  $configuration
	 * @param   Session        $session
	 * @param   Logger         $logger
	 * @param   Timer          $timer
	 * @param   bool           $enforceMinimumExecutionTime
	 *
	 * @return  array
	 *
	 * @since   5.4.0
	 */
	private function tickScannerEngine(
		Configuration $configuration, Session $session, Logger $logger, Timer $timer,
		$enforceMinimumExecutionTime = true
	)
	{
		// Get the crawler and step it while we have enough time left
		$crawler   = new Crawler($configuration, $session, $logger, $timer);
		$step      = $session->get('step', 0);
		$operation = 0;
		$logger->debug(sprintf('===== Starting Step #%u =====', ++$step));

		while (true)
		{
			$logger->debug(sprintf('----- Starting operation #%u -----', ++$operation));
			$statusArray = $crawler->tick();
			$logger->debug(sprintf('----- Finished operation #%u -----', $operation));

			// Did we run into an error?
			if ($crawler->getState() == Part::STATE_ERROR)
			{
				$logger->debug('The scanner engine has experienced an error.');

				break;
			}

			// Are we done?
			if ($crawler->getState() == Part::STATE_FINISHED)
			{
				$logger->debug('The scanner engine finished scanning your site.');

				break;
			}

			// Did we run out of time?
			if ($timer->getTimeLeft() <= 0)
			{
				$logger->debug('We are running out of time.');

				break;
			}

			// Is the Break Flag set?
			if ($session->get('breakFlag', false))
			{
				$logger->debug('The Break Flag is set.');

				break;
			}
		}

		$logger->debug(sprintf('===== Finished Step #%u =====', $step));

		// Reset the break flag
		$session->set('breakFlag', false);

		// Do I need to enforce the minimum execution time?
		if (!$enforceMinimumExecutionTime)
		{
			return $statusArray;
		}

		$minExec    = $configuration->get('minExec');
		$alreadyRun = $timer->getRunningTime();
		$waitTime   = $alreadyRun - $minExec;

		// Negative wait times mean that we shouldn't wait. Also, waiting for less than 10 msec is daft.
		if ($waitTime <= 0.01)
		{
			return $statusArray;
		}

		if (!function_exists('time_nanosleep'))
		{
			usleep(1000000 * $waitTime);

			return $statusArray;
		}

		$seconds    = floor($waitTime);
		$fractional = $waitTime - $seconds;
		time_nanosleep($seconds, intval(floor($fractional * 1000000000)));

		return $statusArray;
	}
}