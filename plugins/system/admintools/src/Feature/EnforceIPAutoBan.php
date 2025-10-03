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
use Akeeba\Plugin\System\AdminTools\Utility\Cache;
use Akeeba\Plugin\System\AdminTools\Utility\Filter;
use Akeeba\Plugin\System\AdminTools\Utility\RescueUrl;

class EnforceIPAutoBan extends Base
{
	/**
	 * Blocks visitors coming from an automatically banned IP.
	 */
	public function onAfterInitialise(): void
	{
		$ip = Filter::getIp();

		if (!$this->isIPBlocked($ip))
		{
			return;
		}

		// Rescue URL check
		RescueUrl::processRescueURL($this->exceptionsHandler);

		@ob_end_clean();
		header("HTTP/1.0 403 Forbidden");

		$spammerMessage = $this->wafParams->getValue('spammermessage', '');

		if ($spammerMessage == 'We have detected suspicious activity from your IP address. Your access to this site is temporarily suspended.')
		{
			$spammerMessage .= ' [RESCUEINFO]';
		}

		$spammerMessage = str_replace('[IP]', $ip, $spammerMessage);
		$spammerMessage = str_replace('{IP}', $ip, $spammerMessage);
		$spammerMessage = RescueUrl::processRescueInfoInMessage($spammerMessage);

		echo $spammerMessage;

		$this->app->close();
	}

	/**
	 * Is the IP blocked by an auto-blocking rule?
	 *
	 * @param   string  $ip  The IP address to check. Skip or pass empty string / null to use the current visitor's IP.
	 *
	 * @return  bool
	 */
	public function isIPBlocked($ip = null)
	{
		if (empty($ip))
		{
			// Get the visitor's IP address
			$ip = Filter::getIp();
		}

		$records = Cache::getCache('ipautoban');

		if (!isset($records[$ip]))
		{
			return false;
		}

		$record = (object) $records[$ip];

		// Is this record expired?
		$jNow   = clone Factory::getDate();
		$jUntil = clone Factory::getDate($record->until);
		$now    = $jNow->toUnix();
		$until  = $jUntil->toUnix();
		$db     = $this->db;

		if ($now > $until)
		{
			// Ban expired. Move the entry and allow the request to proceed.
			$history     = clone $record;
			$history->id = null;

			try
			{
				$db->insertObject('#__admintools_ipautobanhistory', $history, 'id');
			}
			catch (Exception $e)
			{
				// Oops...
			}

			$sql = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->delete($db->qn('#__admintools_ipautoban'))
				->where($db->qn('ip') . ' = ' . $db->q($ip));
			$db->setQuery($sql);

			try
			{
				$db->execute();
			}
			catch (Exception $e)
			{
				// Oops...
			}

			return false;
		}

		// Move old entries - The fastest way is to create a INSERT with a SELECT statement
		$sql = 'INSERT INTO ' . $db->qn('#__admintools_ipautobanhistory') . ' (' . $db->qn('id') . ', ' . $db->qn('ip') . ', ' . $db->qn('reason') . ', ' . $db->qn('until') . ')' .
			' SELECT NULL, ' . $db->qn('ip') . ', ' . $db->qn('reason') . ', ' . $db->qn('until') .
			' FROM ' . $db->qn('#__admintools_ipautoban') .
			' WHERE ' . $db->qn('until') . ' < ' . $db->q($jNow->toSql());

		try
		{
			$r = $db->setQuery($sql)->execute();
		}
		catch (Exception $e)
		{
			// Oops...
		}

		$sql = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->delete($db->qn('#__admintools_ipautoban'))
			->where($db->qn('until') . ' < ' . $db->q($jNow->toSql()));
		$db->setQuery($sql);

		try
		{
			$db->execute();
		}
		catch (Exception $e)
		{
			// Oops...
		}

		return true;
	}
}
