<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Akeeba\Plugin\System\AdminTools\Utility\Filter;

class SaveUserSignupIPAsNote extends Base
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

		if ($this->wafParams->getValue('saveusersignupip', 0) != 1)
		{
			return false;
		}

		return true;
	}

	public function onUserAfterSave($user, $isnew, $success, $msg): void
	{
		$process = true;

		// Only trigger on successful user creation
		if (!$success)
		{
			$process = false;
		}

		// Only trigger on new user creation, not subsequent edits
		if (!$isnew)
		{
			$process = false;
		}

		// Only trigger on front-end user creation.
		if (!$this->app->isClient('site'))
		{
			$process = false;
		}

		if (!$process)
		{
			return;
		}

		// Create a new user note

		// Get the user's ID
		$user_id = (int) $user['id'];

		// Get the IP address
		$ip = Filter::getIp();

		if ((strpos($ip, '::') === 0) && (strstr($ip, '.') !== false))
		{
			$ip = substr($ip, strrpos($ip, ':') + 1);
		}

		// Get the user agent string
		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

		// Get current date and time in database format
		$now = clone Factory::getDate();
		$now = $now->toSql();

		// Load the component's administrator translation files
		$jlang = $this->app->getLanguage();
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, null, true);

		$catd_id = $this->wafParams->getValue('usernote_category', 0);

		// Create and save the user note
		$userNote = (object) [
			'user_id'         => $user_id,
			'catid'           => $catd_id,
			'subject'         => Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_SIGNUPIP_SUBJECT'),
			'body'            => Text::sprintf('COM_ADMINTOOLS_CONFIGUREWAF_LBL_SIGNUPIP_BODY', $ip, $user_agent),
			'state'           => 1,
			'created_user_id' => 42,
			'created_time'    => $now,
			'modified_time'   => $this->db->getNullDate(),
		];

		/** @var \Joomla\CMS\MVC\Factory\MVCFactoryInterface $mvcFactory */
		$mvcFactory = $this->app->bootComponent('com_users')->getMVCFactory();
		$userNoteTable = $mvcFactory->createTable('Note', 'Administrator');

        try
        {
            $userNoteTable->save($userNote);
        }
        catch (\Exception $e)
        {
            // Do not die if we can't store the user note for any reason
        }
	}
}
