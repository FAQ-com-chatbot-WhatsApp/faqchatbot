<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;

class NoFrontendSuperUserLogin extends Base
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

		if ($this->wafParams->getValue('nofesalogin', 0) != 1)
		{
			return false;
		}

		return true;
	}

	public function onUserLogin($user, $options = []): bool
	{
		$instance = $this->getUserObject($user, $options);

		$isSuperAdmin = $instance->authorise('core.admin');

		if (!$isSuperAdmin)
		{
			return true;
		}

		// Is this a Joomla! 3.9+ installation with a user who's not yet provided consent?
		if ($this->isJoomlaPrivacyEnabled())
		{
			$userID     = UserHelper::getUserId($user['username']);
			$userObject = self::getUserById($userID);

			if (!$this->hasUserConsented($userObject))
			{
				return true;
			}
		}

		$newopts = [];
		$this->app->logout($instance->id, $newopts);

		// Since Joomla! 2.5.5 you have to close the session before throwing an error, otherwise the user isn't
		// logged out.
		$this->app->getSession()->close();

		// Throw error
		throw new Exception(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
	}

	private function getUserObject($user, $options = [])
	{
		$instance = new User();

		if ($id = intval(UserHelper::getUserId($user['username'])))
		{
			$instance->load($id);

			return $instance;
		}

		$config           = ComponentHelper::getParams('com_users');
		$defaultUserGroup = $config->get('new_usertype', 2);

		$instance->id       = 0;
		$instance->name     = $user['fullname'];
		$instance->username = $user['username'];
		$instance->email    = $user['email']; // Result should contain an email (check)
		$instance->usertype = 'deprecated';
		$instance->groups   = [$defaultUserGroup];

		return $instance;
	}
}
