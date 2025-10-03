<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\Utilities\ArrayHelper;

class WarnAboutLeakedPasswords extends Base
{
	public function isEnabled()
	{
		// Protect vs broken host
		if (!function_exists('sha1'))
		{
			return false;
		}

		return ($this->wafParams->getValue('leakedpwd', 0) == 1);
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
		if (!isset($data['password_clear']) || !$data['password_clear'])
		{
			return true;
		}

		// This group is allowed to have insecure passwords? If so let's stop here
		if (!$this->checkByGroup($oldUser, $data))
		{
			return true;
		}

		// HIBP database searches for the first 5 chars, if the rest of the hash is in the response body, the password
		// is included in a leaked database
		$hashed = strtoupper(hash('sha1', $data['password_clear']));
		$search = substr($hashed, 0, 5);
		$body   = substr($hashed, 5);

		$http = HttpFactory::getHttp();
		$http->setOption('user-agent', 'admin-tools-pwd-checker');

		try
		{
			$response = $http->get('https://api.pwnedpasswords.com/range/' . $search);
		}
		catch (Exception $e)
		{
			// Do not die if anything wrong happens
			return true;
		}

		// This should never happen, but better be safe than sorry
		if ($response->code !== 200)
		{
			return true;
		}

		// There's no need to further process the response: if the rest of the hash is inside the body,
		// it means that is an insecure password
		if (strpos($response->body, $body) !== false)
		{
			// Load the component's administrator translation files
			$jlang = $this->app->getLanguage();
			$jlang->load('com_admintools', JPATH_ADMINISTRATOR, 'en-GB', true);
			$jlang->load('com_admintools', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
			$jlang->load('com_admintools', JPATH_ADMINISTRATOR, null, true);

			throw new Exception(Text::sprintf('PLG_ADMINTOOLS_ERR_LEAKEDPWD', $data['password_clear']), '403');
		}

		return true;
	}

	/**
	 * Given a user group, should we allow insecure passwords?
	 *
	 * @param   User|array  $oldUser
	 * @param   array       $data
	 *
	 * @return bool        Should we continue with the check or no?
	 */
	private function checkByGroup($oldUser, $data)
	{
		$groups = $this->wafParams->getValue('leakedpwd_groups');

		if (!$groups)
		{
			return false;
		}

		if (!is_array($groups))
		{
			$groups = array_filter(ArrayHelper::toInteger(explode(',', $groups)));

			if (empty($groups))
			{
				return false;
			}
		}

		// Ok, here's the situation. If you DON'T change user group, $data['groups'] is empty and we have to check the $oldUser.
		// If you change the groups or create a new user, data['groups'] is populated and we have to check for it
		if (isset($data['groups']) && $data['groups'])
		{
			$user_groups = $data['groups'];
		}
		else
		{
			$user_groups = [];

			if (is_array($oldUser))
			{
				$user_groups = $oldUser['groups'];
			}
			elseif ($oldUser instanceof User)
			{
				$user_groups = $oldUser->groups;
			}
		}

		// We have the groups, now let's check them
		foreach ($user_groups as $user_group)
		{
			if (in_array($user_group, $groups))
			{
				return true;
			}
		}

		return false;
	}
}
