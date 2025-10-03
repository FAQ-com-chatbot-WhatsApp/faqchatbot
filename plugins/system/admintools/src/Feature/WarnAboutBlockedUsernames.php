<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;

class WarnAboutBlockedUsernames extends Base
{
	public function isEnabled()
	{
		if (!$this->app->isClient('site'))
		{
			return false;
		}

		if (!file_exists(__DIR__.'/../../assets/forbidden_usernames.php'))
		{
			return false;
		}

		return ($this->wafParams->getValue('blockusernames', 0) == 1);
	}

	/**
	 * Hooks into the Joomla! models before a user is saved.
	 *
	 * @param   User|array  $oldUser  The existing user record
	 * @param   bool        $isNew    Is this a new user?
	 * @param   array       $data     The data to be saved
	 *
	 * @throws  Exception  When we catch a security exception
	 */
	public function onUserBeforeSave($oldUser, $isNew, $data): bool
	{
		if (!isset($data['username']) || !$data['username'])
		{
			return true;
		}

		$blocked_usernames = include __DIR__ . '/../../assets/forbidden_usernames.php';

		// Missing or invalid data, stop here
		if (!$blocked_usernames)
		{
			return true;
		}

		// Adjust the default list by adding or removing usernames
		$extra_block = $this->wafParams->getValue('blockusernames_forbid', []);

		if (is_string($extra_block))
		{
			$extra_block = array_map('trim', explode(',', $extra_block));
		}

		$extra_block = array_map(
			function ($x) {
				return is_array($x) ? $x[0] : $x;
			}, is_array($extra_block) ? $extra_block : []
		);

		$extra_allow = $this->wafParams->getValue('blockusernames_allow', []);

		if (is_string($extra_allow))
		{
			$extra_allow = array_map('trim', explode(',', $extra_allow));
		}

		$extra_allow = array_map(
			function ($x) {
				return is_array($x) ? $x[0] : $x;
			}, is_array($extra_allow) ? $extra_allow : []
		);

		$blocked_usernames = array_merge_recursive($blocked_usernames, $extra_block);
		$blocked_usernames = array_diff($blocked_usernames, $extra_allow);

		if (in_array($data['username'], $blocked_usernames))
		{
			$jlang = $this->app->getLanguage();
			$jlang->load('com_admintools', JPATH_ADMINISTRATOR, 'en-GB', true);
			$jlang->load('com_admintools', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
			$jlang->load('com_admintools', JPATH_ADMINISTRATOR, null, true);

			throw new Exception(Text::sprintf('PLG_ADMINTOOLS_ERR_BLOCKEDUSERNAME', $data['username']));
		}

		return true;
	}
}
