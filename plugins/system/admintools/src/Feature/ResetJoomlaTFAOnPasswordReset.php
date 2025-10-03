<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Exception;

class ResetJoomlaTFAOnPasswordReset extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		if (!$this->app->isClient('site'))
		{
			return false;
		}

		if ($this->wafParams->getValue('resetjoomlatfa', 0) != 1)
		{
			return false;
		}

		$option = $this->input->getCmd('option', 'com_foobar');
		$task   = $this->input->getCmd('task', 'default');

		if (!(($option == 'com_users') && ($task == 'complete')))
		{
			return false;
		}

		return true;
	}

	public function onUserAfterSave($user, $isnew, $success, $msg): void
	{
		$db = $this->db;

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->update($db->qn('#__users'))
			->set([
				$db->qn('otpKey') . ' = ' . $db->q(''),
				$db->qn('otep') . ' = ' . $db->q(''),
			])
			->where($db->qn('id') . ' = ' . $db->q($user['id']));

		$db->setQuery($query);

		try
		{
			$db->execute();
		}
		catch (Exception $e)
		{
			// Do nothing if the query fails
		}
	}
}
