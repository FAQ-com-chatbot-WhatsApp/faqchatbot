<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Exception;

/**
 * Remove old log entries
 *
 * @deprecated 8.0 Use Joomla Scheduled Tasks instead
 */
class RemoveOldLogEntries extends Base
{
	/**
	 * How many old entries to delete every time this feature executes.
	 *
	 * @since 7.0.0
	 */
	private const DELETE_BATCH_SIZE = 10000;

	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return ($this->params->get('maxlogentries', 0) > 0);
	}

	/**
	 * Deletes old log entries, keeping up to maxlogentries entries.
	 */
	public function onAfterInitialise(): void
	{
		// Delete up to 100 old entries
		$entriesToKeep   = $this->params->get('maxlogentries', 0);

		if ($entriesToKeep <= 0)
		{
			return;
		}

		// Run this with a 5% probability
		if (!mt_rand(1, 20) == 10)
		{
			return;
		}

		/**
		 * Delete up to 10,000 old entries
		 *
		 * IMPORTANT! We need a subquery-inside-a-subquery to avoid a MySQL limitation which does not allow IN arguments
		 * to be subqueries with LIMITs.
		 *
		 * The SQL to run is:
		 * DELETE FROM `#__admintools_log` WHERE `id` IN (SELECT * FROM (SELECT `id` FROM `#__admintools_log` ORDER BY
		 * `id` desc LIMIT $entriesToKeep, self::DELETE_BATCH_SIZE) `foo`)
		 */
		$db = $this->db;

		/** @noinspection PhpDeprecationInspection */
		$innerSubquery = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->quoteName('id'))
			->from($db->quoteName('#__admintools_log'))
			->order($db->quoteName('id') . ' DESC')
			->setLimit(self::DELETE_BATCH_SIZE, $entriesToKeep);

		$outerSubquery = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select('*')
			->from('(' . $innerSubquery . ') ' . $db->quoteName('foo'));

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->delete($db->quoteName('#__admintools_log'))
			->where($db->quoteName('id') . ' IN (' . $outerSubquery . ')');

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (Exception $exc)
		{
			// Do nothing on DB exception
		}
	}
}
