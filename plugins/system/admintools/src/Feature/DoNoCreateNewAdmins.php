<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Akeeba\Component\AdminTools\Administrator\Helper\ServerTechnology;
use Exception;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;

class DoNoCreateNewAdmins extends Base
{
    /** @var array Groups that are forbidden to have their details modified */
    protected array $groups_backend = [];

    /** @var array Groups that are forbidden to be edited from frontend */
    protected array $groups_frontend = [];

	/**
	 * Sets the temporary disable flag, used when saving temporary super users
	 *
	 * @param   bool  $tempDisableFlag
	 */
	public static function setTempDisableFlag($tempDisableFlag)
	{
		// Create a secure temp token for the flag
		$class = static::class;
		$sig   = ApplicationHelper::getHash($class . '_tempDisableFlag_');

		// Make sure we are being called by an explicitly allowed method
		ServerTechnology::checkCaller([
			'Akeeba\Component\AdminTools\Administrator\Table\Mixin\NoSuperUsersCheckFlags::setNoCheckFlags',
			'Akeeba\Component\AdminTools\Administrator\Table\TempsuperuserTable::setNoCheckFlags',
			'Akeeba\Component\AdminTools\Administrator\Model\TempsuperusersModel::setNoCheckFlags',
			'Akeeba\Component\AdminTools\Administrator\Model\TempsuperuserModel::setNoCheckFlags',
		]);

		// Set the flag in the session
		/** @var CMSApplication $app */
		$app = Factory::getApplication();
		$app->getSession()->set('com_admintools.' . $sig, (bool) $tempDisableFlag);
	}

	/**
	 * Gets the temporary disable flag, used when saving temporary super users
	 *
	 * @return  bool
	 */
	protected static function getTempDisableFlag()
	{
		// Get a secure temp token for the flag and retrieve the current value
		$class = static::class;
		$sig   = ApplicationHelper::getHash($class . '_tempDisableFlag_');

		/** @var CMSApplication $app */
		$app = Factory::getApplication();
		$ret = (bool) $app->getSession()->get('com_admintools.' . $sig, false);

		// Reset the flag on retrieval
		$app->getSession()->set('com_admintools.' . $sig, null);

		// Return the retrieved value
		return $ret;
	}

	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		$fromBackend  = $this->wafParams->getValue('nonewadmins', 0) == 1;
		$fromFrontend = $this->wafParams->getValue('nonewfrontendadmins', 1) == 1;

		$enabled = $fromBackend && $this->app->isClient('administrator');
		$enabled |= $fromFrontend && $this->app->isClient('site');

        $this->groups_backend  = $this->wafParams->getValue('nonewadmins_groups', []);
        $this->groups_frontend = $this->wafParams->getValue('nonewfrontendadmins_groups', []);

		return $enabled;
	}

	/**
	 * Disables creating new admins or updating new ones
	 */
	public function onAfterInitialise(): void
	{
		$input  = $this->input;
		$option = $input->getCmd('option', '');
		$task   = $input->getCmd('task', '');

		if ($option != 'com_users' && $option != 'com_admin')
		{
			return;
		}

		$jform = $this->input->get('jform', [], 'array');

		$filteredTasks = [
			'save',
			'apply',
			'user.apply',
			'user.save',
			'user.save2new',
			'profile.apply',
			'profile.save',
		];

		if (!in_array($task, $filteredTasks))
		{
			return;
		}

		// Not editing, just core devs using the same task throughout the component, dammit
		if (empty($jform))
		{
			return;
		}

		$groups = [];

		if (isset($jform['groups']))
		{
			$groups = $jform['groups'];
		}

		$user = self::getUserById((int) $jform['id']);

		// Sometimes $user->groups is null... let's be 100% sure that we loaded all the groups of the user
		if (empty($user->groups))
		{
			$user->groups = UserHelper::getUserGroups($user->id);
		}

        $isFrontend      = $this->app->isClient('site');
        $groups_to_check = $isFrontend ? $this->groups_frontend : $this->groups_backend;

        // If we have a user-supply list of blocked groups, use it, otherwise fall back to checking if they have login access
        $makingNewAdmin = $groups_to_check ? $this->isBlockedGroup($groups, $groups_to_check) : $this->hasAdminGroup($groups);
		$isAdmin        = $groups_to_check ? $this->isBlockedGroup($user->groups, $groups_to_check) : $this->hasAdminGroup($user->groups);

        // Not an admin and is not trying to become one, that's good to go
		if (!$isAdmin && !$makingNewAdmin)
		{
			return;
		}

		// Allow editing my own user record if I am already an admin
		if ($isAdmin && ($this->app->getIdentity()->id == $jform['id']))
		{
			return;
		}

		/**
		 * In the frontend we only stop requests which are trying to make a new admin. This lets execution fall through
		 * to onUserBeforeSave where we can check for Joomla! 3.9+ user consent changes.
		 */
		$newUser = array_merge($jform);

		if (array_key_exists('params', $newUser))
		{
			$newUser['params'] = json_encode($newUser['params']);
		}

		if ($isFrontend && !$makingNewAdmin && $this->allowEdit($user))
		{
			return;
		}

		// Get the correct reason (was the user being created in front- or back-end)?
		$reason = $this->app->isClient('administrator') ? 'nonewadmins' : 'nonewfrontendadmins';

		// Log and autoban security exception
		$extraInfo = "Submitted JForm Variables :\n";
		$extraInfo .= print_r($jform, true);
		$extraInfo .= "\n";

		// Display the error only if the user should be really blocked (ie we're not in the Whitelist)
		if ($this->exceptionsHandler->logRequest($reason, $extraInfo))
		{
			// Throw an exception to prevent Joomla! processing this form
			$jlang = $this->app->getLanguage();
			$jlang->load('joomla', JPATH_ROOT, 'en-GB', true);
			$jlang->load('joomla', JPATH_ROOT, $jlang->getDefault(), true);
			$jlang->load('joomla', JPATH_ROOT, null, true);

			throw new Exception(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), '403');
		}
	}

	/**
	 * Hooks into the Joomla! models before a user is saved. This catches the case where a 3PD extension tries to create
	 * a new user instead of going through com_users.
	 *
	 * @param   User|array  $oldUser  The existing user record
	 * @param   bool        $isNew    Is this a new user?
	 * @param   array       $data     The data to be saved
	 *
	 * @throws  Exception  When we catch a security exception
	 */
	public function onUserBeforeSave($oldUser, $isNew, $data): bool
	{
		// Are we temporarily disabled?
		if (self::getTempDisableFlag())
		{
			return true;
		}

        $option = $this->input->getCmd('option', '');
        $task   = $this->input->getCmd('task', '');

        // Specific exception when user is requesting a password reset
        if ($option == 'com_users' && $task == 'request')
        {
	        return true;
        }

		// Only applies to admin users.
        $isFrontend      = $this->app->isClient('site');
        $groups_to_check = $isFrontend ? $this->groups_frontend : $this->groups_backend;

        // If we have a user-supply list of blocked groups, use it, otherwise fall back to checking if they have login access
        $isAdmin = $groups_to_check ? $this->isBlockedGroup($data['groups'], $groups_to_check) : $this->hasAdminGroup($data['groups']);

		if (!$isAdmin)
		{
			return true;
		}

		$user = self::getUserById($data['id']);

		// We are allowed to edit the profile of a user without active consent
		if (!$isNew && $this->allowEdit($user))
		{
			return true;
		}

		// We can always edit out own profile
		if ($this->app->getIdentity()->id == $data['id'])
		{
			return true;
		}

		// Get the correct reason (was the user being created in front- or back-end)?
		$reason = $this->app->isClient('administrator') ? 'nonewadmins' : 'nonewfrontendadmins';

		// Log and autoban security exception
		$extraInfo = "User Data Variables :\n";
		$extraInfo .= print_r($data, true);
		$extraInfo .= "\n";

		// Display the error only if the user should be really blocked (ie we're not in the Whitelist)
		if ($this->exceptionsHandler->logRequest($reason, $extraInfo))
		{
			// Throw an exception to prevent Joomla! processing this form
			$jlang = $this->app->getLanguage();
			$jlang->load('joomla', JPATH_ROOT, 'en-GB', true);
			$jlang->load('joomla', JPATH_ROOT, $jlang->getDefault(), true);
			$jlang->load('joomla', JPATH_ROOT, null, true);

			throw new Exception(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), '403');
		}

		return true;
	}

	/**
	 * Is this a user who has not yet consented to the privacy policy?
	 *
	 * @param   User|User  $user
	 *
	 * @return  bool    Am I allowed to edit this user?
	 */
	private function allowEdit($user)
	{
		if (!$this->isJoomlaPrivacyEnabled())
		{
			return false;
		}

		return !$this->hasUserConsented($user);
	}

    /**
     * Check if any of the listed groups is inside the list of groups blocked by the site admin
     *
     * @param array    $user_groups
     * @param array    $blocked_groups
     *
     * @return bool
     */
    private function isBlockedGroup(array $user_groups, array $blocked_groups) : bool
    {
        foreach ($user_groups as $group)
        {
            if (in_array($group, $blocked_groups))
            {
                return true;
            }
        }

        return false;
    }
}
