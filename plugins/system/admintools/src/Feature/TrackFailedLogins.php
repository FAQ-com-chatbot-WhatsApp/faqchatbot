<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserHelper;
use Akeeba\Plugin\System\AdminTools\Utility\Filter;

class TrackFailedLogins extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return ($this->wafParams->getValue('trackfailedlogins', 0) == 1);
	}

	/**
	 * Treat failed logins as security exceptions
	 *
	 * @param   AuthenticationResponse  $response
	 */
	public function onUserLoginFailure($response): void
	{
		// Exit if the IP is blacklisted; logins originating from blacklisted IPs will be blocked anyway
		if ($this->parentPlugin->runShortCircuitFeature('isIPBlocked', false, []))
		{
			return;
		}

		$extraInfo = null;
		$user      = $this->input->getString('username', null);

		// Log the username only if we have a user AND we told Admin Tools to store usernames, too
		if ($this->wafParams->getValue('logusernames', 0) && !empty($user))
		{
			$extraInfo = 'Username: ' . $user;
		}

		$this->exceptionsHandler->logRequest('loginfailure', $user, $extraInfo);

		$this->deactivateUser($user);
	}

	private function deactivateUser($username)
	{
		$userParams = ComponentHelper::getParams('com_users');

		// User registration disabled or no user activation - Let's stop here
		if (!$userParams->get('allowUserRegistration') || ($userParams->get('useractivation') == 0))
		{
			return;
		}

		$ip = Filter::getIp();

		// If I can't detect the IP there's not point in continuing
		if (!$ip)
		{
			return;
		}

		$limit     = $this->wafParams->getValue('deactivateusers_num', 3);
		$numfreq   = $this->wafParams->getValue('deactivateusers_numfreq', 1);
		$frequency = $this->wafParams->getValue('deactivateusers_frequency', 'hour');

		// The user didn't set any limit nor frequency value, let's stop here
		if (!$limit || !$numfreq)
		{
			return;
		}

		$userid = UserHelper::getUserId($username);

		// The user doesn't exists, let's stop here
		if (!$userid)
		{
			return;
		}

		$user = self::getUserById($userid);

		// Username doesn't match, the user is blocked or is not active? Let's stop here
		if ($user->username != $username || $user->block || !(empty($user->activation)))
		{
			return;
		}

		// If I'm here, it means that this is a valid user, let's see if I have to deactivate him
		$where = [
			'ip'     => $ip,
			'reason' => 'loginfailure',
		];

		$deactivate = $this->checkLogFrequency($limit, $numfreq, $frequency, $where);

		if (!$deactivate)
		{
			return;
		}

		PluginHelper::importPlugin('user');
		$db = $this->db;

		$randomPassword        = UserHelper::genRandomPassword();
		$data['activation']    = ApplicationHelper::getHash($randomPassword);
		$data['block']         = 1;
		$data['lastvisitDate'] = null;

		// If an admin needs to activate the user, I have to set the activate flag
		if ($userParams->get('useractivation') == 2)
		{
			$user->setParam('activate', 1);
		}

		if (!$user->bind($data))
		{
			return;
		}

		if (!$user->save())
		{
			return;
		}

		try
		{
			$uri      = Uri::getInstance();
			$base     = $uri->toString(['scheme', 'user', 'pass', 'host', 'port']);
			$activate = $base . Route::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

			// Send e-mail to the user
			if ($userParams->get('useractivation') == 1)
			{
				$effectiveUsers = [$user];
			}
			// Send e-mail to Super Users
			elseif ($userParams->get('useractivation') == 2)
			{
				// get all admin users
				$effectiveUsers = [];
				$query          = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
					->select($db->qn(['name', 'email', 'sendEmail', 'id']))
					->from($db->qn('#__users'))
					->where($db->qn('sendEmail') . ' = ' . 1);

				$rows = $db->setQuery($query)->loadObjectList();

				// Send mail to all users with users creating permissions and receiving system emails
				foreach ($rows as $row)
				{
					$usercreator = self::getUserById($row->id);

					if ($usercreator->authorise('core.create', 'com_users') && !empty($usercreator->email))
					{
						$effectiveUsers[] = $usercreator;
					}
				}
			}
			else
			{
				// Future-proof check
				return;
			}

			$tokens = [
				'ACTIVATE' => $activate,
				'USER'     => $user->username . ' (' . $user->name . ' <' . $user->email . '>)',
			];

			foreach ($effectiveUsers as $user)
			{
				$this->exceptionsHandler->sendEmail('com_admintooos.userreactivate', $user, $tokens);
			}
		}
		catch (Exception $e)
		{
			// Joomla! 3.5 and later throw an exception when crap happens instead of suppressing it and returning false
		}
	}

	/**
	 * @param          $limit
	 * @param          $numfreq
	 * @param          $frequency
	 * @param   array  $extraWhere
	 *
	 * @return bool
	 */
	private function checkLogFrequency($limit, $numfreq, $frequency, array $extraWhere)
	{
		$db = $this->db;

		$mindatestamp = 0;

		switch ($frequency)
		{
			case 'second':
				break;

			case 'minute':
				$numfreq *= 60;
				break;

			case 'hour':
				$numfreq *= 3600;
				break;

			case 'day':
				$numfreq *= 86400;
				break;

			case 'ever':
				$mindatestamp = 946706400; // January 1st, 2000
				break;
		}

		$jNow = clone Factory::getDate();

		if ($mindatestamp == 0)
		{
			$mindatestamp = $jNow->toUnix() - $numfreq;
		}

		$jMinDate = clone Factory::getDate($mindatestamp);
		$minDate  = $jMinDate->toSql();

		$sql = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select('COUNT(*)')
			->from($db->qn('#__admintools_log'))
			->where($db->qn('logdate') . ' >= ' . $db->q($minDate));

		foreach ($extraWhere as $column => $value)
		{
			$sql->where($db->qn($column) . ' = ' . $db->q($value));
		}

		$db->setQuery($sql);

		try
		{
			$numOffenses = $db->loadResult();
		}
		catch (Exception $e)
		{
			$numOffenses = 0;
		}

		if ($numOffenses < $limit)
		{
			return false;
		}

		return true;
	}
}
