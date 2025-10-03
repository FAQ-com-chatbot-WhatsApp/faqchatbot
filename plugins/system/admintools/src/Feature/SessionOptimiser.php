<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Factory;
use Joomla\Database\Exception\ConnectionFailureException;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\Pdo\PdoDriver;
use mysqli;
use function mysqli_fetch_object;

/**
 * @deprecated 8.0  Use the Joomla Scheduled Tasks instead
 */
class SessionOptimiser extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return ($this->params->get('sesoptimizer', 0) == 1);
	}

	public function onAfterInitialise(): void
	{
		$minutes = (int) $this->params->get('sesopt_freq', 0);

		if ($minutes <= 0)
		{
			return;
		}

		$lastJob = $this->getTimestamp('session_optimize');
		$nextJob = $lastJob + $minutes * 60;

		$now = clone Factory::getDate();

		if ($now->toUnix() >= $nextJob)
		{
			$this->setTimestamp('session_optimize');
			$this->sessionOptimize();
		}
	}

	/**
	 * Optimizes the session table. The idea is that as users log in and out,
	 * vast amounts of records are created and deleted, slowly fragmenting the
	 * underlying database file and slowing down user session operations. At
	 * some point, your site might even crash. By doing a periodic optimization
	 * of the sessions table this is prevented. An optimization per hour should
	 * be adequate, even for huge sites.
	 *
	 * Note: this is not necessary if you're not using the database to save
	 * session data. Using disk files, memcache, APC or other alternative caches
	 * has no impact on your database performance. In this case you should not
	 * enable this option, as you have nothing to gain.
	 */
	private function sessionOptimize()
	{
		$db     = $this->db;
		$dbName = $db->getName();

		// Make sure this is MySQL
		if (!in_array(strtolower($dbName), ['mysql', 'mysqli', 'pdomysql']))
		{
			return;
		}

		try
		{
			$result = $this->executeUnpreparedGetObjectList('CHECK TABLE ' . $db->quoteName('#__session'));
		}
		catch (Exception $e)
		{
			return;
		}

		$isOK = false;

		if (!empty($result))
		{
			foreach ($result as $row)
			{
				if (($row->Msg_type == 'status') && (
						($row->Msg_text == 'OK') ||
						($row->Msg_text == 'Table is already up to date')
					)
				)
				{
					$isOK = true;
				}
			}
		}

		// Run a repair only if it is required
		if (!$isOK)
		{
			try
			{
				$this->executeUnpreparedQuery('REPAIR TABLE ' . $db->quoteName('#__session'));
			}
			catch (Exception $e)
			{
				return;
			}
		}

		// Finally, optimize
		try
		{
			$this->executeUnpreparedQuery('OPTIMIZE TABLE ' . $db->quoteName('#__session'));
		}
		catch (Exception $e)
		{
			return;
		}
	}

	/**
	 * Executes an unprepared SQL statement.
	 *
	 * The PDO driver doesn't distinguish between prepared and unprepared statements. Therefore we can just run anything
	 * we please. The MySQLi driver, however, has a distinction between prepared and unprepared statements. We cannot
	 * run certain SQL comments (such as OPTIMIZE and REPAIR) over a prepared statement. The MySQLi driver has a handy
	 * method called executeUnpreparedStatement which is protected and which runs this kind of statements.
	 *
	 * This here method tries to figure out if the database driver object has that method and use it instead of the
	 * prepared statement.
	 *
	 * @param $sql
	 *
	 * @return bool|mixed
	 */
	private function executeUnpreparedGetObjectList($sql)
	{
		$sql        = $this->db->replacePrefix($sql);
		$connection = $this->db->getConnection();

		if (($this->db instanceof PdoDriver) || !($connection instanceof mysqli))
		{
			return $this->db->setQuery($sql)->loadObjectList();
		}

		$cursor = $connection->query($sql);

		// If an error occurred handle it.
		if (!$cursor)
		{
			$errorNum = (int) $connection->errno;
			$errorMsg = (string) $connection->error;

			// Check if the server was disconnected.
			if (!$this->db->connected())
			{
				try
				{
					// Attempt to reconnect.
					$connection = null;
					$this->db->connect();
				}
				catch (ConnectionFailureException $e)
				{
					throw new ExecutionFailureException($sql, $errorMsg, $errorNum);
				}

				// Since we were able to reconnect, run the query again.
				return $this->executeUnpreparedGetObjectList($sql);
			}

			// The server was not disconnected.
			throw new ExecutionFailureException($sql, $errorMsg, $errorNum);
		}

		$result = [];

		while ($row = mysqli_fetch_object($cursor))
		{
			$result[] = $row;
		}

		$cursor->free_result();

		return $result;
	}

	/**
	 * Executes an unprepared SQL statement.
	 *
	 * The PDO driver doesn't distinguish between prepared and unprepared statements. Therefore we can just run anything
	 * we please. The MySQLi driver, however, has a distinction between prepared and unprepared statements. We cannot
	 * run certain SQL comments (such as OPTIMIZE and REPAIR) over a prepared statement. The MySQLi driver has a handy
	 * method called executeUnpreparedStatement which is protected and which runs this kind of statements.
	 *
	 * This here method tries to figure out if the database driver object has that method and use it instead of the
	 * prepared statement.
	 *
	 * @param $sql
	 *
	 * @return bool|mixed
	 */
	private function executeUnpreparedQuery($sql)
	{
		$sql    = $this->db->replacePrefix($sql);
		$db     = $this->db;
		$refObj = new \ReflectionObject($db);

		try
		{
			$method = $refObj->getMethod('executeUnpreparedQuery');
			$method->setAccessible(true);

			return $method->invoke($db, $sql);
		}
		catch (\ReflectionException $e)
		{
			return $db->setQuery($sql)->execute();
		}
	}

}
