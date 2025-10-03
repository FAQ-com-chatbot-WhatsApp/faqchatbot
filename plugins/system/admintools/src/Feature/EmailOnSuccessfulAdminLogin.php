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
use Akeeba\Plugin\System\AdminTools\Utility\RescueUrl;

class EmailOnSuccessfulAdminLogin extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		if (!$this->app->isClient('administrator'))
		{
			return false;
		}

		if ($this->isAdminAccessAttempt())
		{
			return false;
		}

		$user = $this->app->getIdentity();

		if ($user->guest)
		{
			return false;
		}

		$email = $this->wafParams->getValue('emailonadminlogin', '');

		return !empty($email);
	}

	/**
	 * Sends an email upon accessing an administrator page other than the login screen
	 */
	public function onAfterInitialise(): void
	{
		// Check if the session flag is set (avoid sending thousands of emails!)
		$flag = $this->app->getSession()->get('plg_admintools.waf.loggedin', 0);

		if ($flag == 1)
		{
			return;
		}

		// Set the flag to prevent sending more emails
		$this->app->getSession()->set('plg_admintools.waf.loggedin', 1);

		// Load the component's administrator translation files
		$jlang = $this->app->getLanguage();
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, null, true, true);

		// Construct the replacement table
		$substitutions = [
			'REASON' => Text::_('COM_ADMINTOOLS_LOG_LBL_REASON_ADMINLOGINSUCCESS'),
		];

		try
		{
			$recipients = $this->wafParams->getValue('emailonadminlogin', '');
			$recipients = is_array($recipients) ? $recipients : explode(',', $recipients);
			$recipients = array_map('trim', $recipients);

			foreach ($recipients as $recipient)
			{
				if (empty($recipient))
				{
					continue;
				}

				$recipientUser            = new User();
				$recipientUser->username  = $recipient;
				$recipientUser->name      = $recipient;
				$recipientUser->email     = $recipient;
				$recipientUser->guest     = 0;
				$recipientUser->block     = 0;
				$recipientUser->sendEmail = 1;
				$data                     = array_merge(RescueUrl::getRescueInformation($recipient), $substitutions);

				$this->exceptionsHandler->sendEmail('com_admintools.adminloginsuccess', $recipientUser, $data);
			}
		}
		catch (Exception $e)
		{
			// Joomla! 3.5 and later throw an exception when crap happens instead of suppressing it and returning false
		}
	}
}
