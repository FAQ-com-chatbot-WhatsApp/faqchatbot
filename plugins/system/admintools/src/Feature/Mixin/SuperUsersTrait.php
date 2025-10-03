<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature\Mixin;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseQuery;

trait SuperUsersTrait
{
	/**
	 * Cache of all the user groups known to Joomla
	 *
	 * @var   array
	 * @since 5.3.0
	 */
	protected $allJoomlaUserGroups = [];

	/**
	 * Return the user IDs of all active (non-blocked) Super Users on the site.
	 *
	 * @return  array
	 *
	 * @since   5.3.0
	 */
	protected function getSuperUsers()
	{
		// Get the Super User groups
		$groups          = $this->getAllJoomlaUserGroups();
		$superUserGroups = array_filter(
			$groups, function ($group) {
			return Access::checkGroup($group, 'core.admin', 1);
		}
		);

		// Get all Super Users
		$superUsers = $this->getUsersByGroups($superUserGroups);
		$superUsers = array_unique($superUsers);

		// Return only active (non-blocked) Super User account IDs
		return array_filter(
			$superUsers,
			fn($userID) => self::getUserById($userID)->block == 0
		);
	}

	/**
	 * Returns all Joomla! user groups
	 *
	 * @return  array
	 *
	 * @since   5.3.0
	 */
	protected function getAllJoomlaUserGroups()
	{
		if (empty($this->allJoomlaUserGroups))
		{
			// Get all groups
			$db    = $this->db;
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select([$db->qn('id')])
				->from($db->qn('#__usergroups'));

			$this->allJoomlaUserGroups = $db->setQuery($query)->loadColumn(0);

			// This should never happen (unless your site is very dead, in which case I feel terribly sorry for you...)
			if (empty($this->allJoomlaUserGroups))
			{
				$this->allJoomlaUserGroups = [];
			}
		}

		return $this->allJoomlaUserGroups;
	}

	/**
	 * Returns all user IDs belonging to any of the group IDs specified.
	 *
	 * @param   array  $groups  List of all user group IDs we are interested in
	 *
	 * @return  array
	 *
	 * @since   5.3.0
	 */
	protected function getUsersByGroups(array $groups)
	{
		$db    = $this->db;
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([$db->qn('user_id')])
			->from($db->qn('#__user_usergroup_map'))
			->where(
				$db->qn('group_id') . ' IN(' . implode(
					',', array_map(
						function ($group) use ($db) {
							return $db->q(trim($group));
						}, $groups
					)
				) . ')'
			);
		$ret   = $db->setQuery($query)->loadColumn(0);

		if (empty($ret))
		{
			return [];
		}

		return $ret;
	}

	/**
	 * Get an array of User objects given a WAF options key containing a list of emails.
	 *
	 * If a user with an email equal to the configured email exists the user object will be that exact user. Otherwise,
	 * it's a fake user object with an ID of 0 and the username, name, and email address set to the recipient email
	 * address entered into the WAF configuration option.
	 *
	 * @param   string  $wafParamsKey
	 *
	 * @return  array<User>
	 * @since   7.7.1
	 */
	private function userListFromConfiguredEmailList(string $wafParamsKey): array
	{
		$emailList = $this->wafParams->getValue($wafParamsKey, '') ?: '';

		if (empty($emailList))
		{
			return [];
		}

		$emailList = is_array($emailList) ? $emailList : explode(',', trim($emailList));
		$emailList = array_map('trim', $emailList);
		$emailList = array_filter($emailList);

		return array_map(
			fn($email) => $this->userObjectByEmail($email),
			$emailList
		);
	}

	/**
	 * Get a user object given an email address.
	 *
	 * If a user with an email equal to the configured email exists the user object will be that exact user. Otherwise,
	 * it's a fake user object with an ID of 0 and the username, name, and email address set to the recipient email
	 * address entered into the WAF configuration option.
	 *
	 * @param   string  $email
	 *
	 * @return  User
	 * @since   7.7.1
	 */
	private function userObjectByEmail(string $email): User
	{
		$db = $this->db;
		/** @var DatabaseQuery $query */
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true));
		$query
			->select($db->qn('id'))
			->from($db->qn('#__users'))
			->where($db->qn('email') . ' = ' . $db->q($email));
		$userId = $db->setQuery($query)->loadResult();

		if (empty($userId))
		{
			$user            = new User();
			$user->username  = $email;
			$user->name      = $email;
			$user->email     = $email;
			$user->guest     = 0;
			$user->block     = 0;
			$user->sendEmail = 1;

			return $user;
		}

		return new User($userId);
	}

	/**
	 * Get an array of User objects with the site's Super User accounts.
	 *
	 * All non-Blocked Super User accounts are returned. This includes Super Users which are configured to not Receive
	 * Email in their user profile.
	 *
	 * @return  array<User>
	 * @since   7.7.1
	 */
	private function getSuperUserObjects(): array
	{
		return array_map(
			fn($id) => self::getUserById($id),
			$this->getSuperUsers()
		);
	}
}