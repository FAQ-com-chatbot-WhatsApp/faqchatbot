<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

/**
 * @deprecated 8.0  Use the Joomla Scheduled Tasks instead
 */
class DeleteInactiveUsers extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return ($this->params->get('deleteinactive', 0) == 1);
	}

	/**
	 * Deletes inactive users (not activated or not visited the site for too long).
	 */
	public function onAfterInitialise(): void
	{
		// If the days are not at least 1, bail out
		$filtertype = (int) $this->params->get('deleteinactive', 1);
		$days       = (int) $this->params->get('deleteinactive_days', 0);

		if ($days <= 0)
		{
			return;
		}

		// Get up to 5 ids of users to remove
		$db  = $this->db;
		$sql = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->qn('id'))
			->from($db->qn('#__users'))
			->where($db->qn('registerDate') . ' <= ' . "DATE_SUB(NOW(), INTERVAL $days DAY)")
			->extendWhere('AND', [
				$db->quoteName('lastvisitDate') . ' = :nullDate',
				$db->quoteName('lastvisitDate') . ' IS NULL',
			], 'OR')
			->bind(':nullDate', $nullDate);

		switch ($filtertype)
		{
			case 1:
				// Only users not yet activated
				$sql->extendWhere('AND NOT', [
					$db->quoteName('activation') . ' = ' . $db->quote(''),
					$db->quoteName('activation') . ' IS NULL',
				], 'OR');
				break;

			case 2:
				// Only users already activated
				$sql
					->extendWhere('AND', [
						$db->quoteName('activation') . ' = ' . $db->quote(''),
						$db->quoteName('activation') . ' IS NULL',
					], 'OR')
					->extendWhere('AND', [
						$db->quoteName('lastResetTime') . ' IS NULL',
						$db->quoteName('lastResetTime') . ' = :nullDate20',
					], 'OR')
					->bind(':nullDate20', $nullDate);
				break;

			case 3:
				// All users who haven't logged in
				break;
		}


		$db->setQuery($sql, 0, 5);

		$ids = $db->loadColumn();

		// Remove those inactive users
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				/** @noinspection PhpDeprecationInspection */
				$userToKill = self::getUserById($id);
				$userToKill->delete();
			}
		}
	}
}
