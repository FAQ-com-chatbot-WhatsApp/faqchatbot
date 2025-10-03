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
use Joomla\CMS\Uri\Uri;
use Akeeba\Plugin\System\AdminTools\Utility\Cache;
use Akeeba\Plugin\System\AdminTools\Utility\Filter;
use Akeeba\Plugin\System\AdminTools\Utility\RescueUrl;

class IPDenyList extends Base
{
	/** @var  string  Extra info to log when blocking an IP */
	private $extraInfo = null;

	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return ($this->wafParams->getValue('ipbl', 0) == 1);
	}

	/**
	 * Filters visitor access by IP. If the IP of the visitor is included in the
	 * blacklist, she gets a 403 error
	 */
	public function onAfterInitialise(): void
	{
		if (!$this->isIPBlocked())
		{
			return;
		}

		$message = $this->wafParams->getValue('custom403msg', '');

		if (empty($message))
		{
			$message = 'PLG_ADMINTOOLS_MSG_BLOCKED';
		}

		// Merge the default translation with the current translation
		$jlang = $this->app->getLanguage();

		// Front-end translation
		$jlang->load('plg_system_admintools', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_system_admintools', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_system_admintools', JPATH_ADMINISTRATOR, null, true);

		// Do we have an override?
		$langOverride = $this->params->get('language_override', '');

		if (!empty($langOverride))
		{
			$jlang->load('plg_system_admintools', JPATH_ADMINISTRATOR, $langOverride, true);
		}

		$message = Text::_($message);

		if ($message == 'PLG_ADMINTOOLS_MSG_BLOCKED')
		{
			$message = "Access Denied";
		}

		// Replace the Rescue URL placeholder
		$message = RescueUrl::processRescueInfoInMessage($message);

		// Show the 403 message
		if ($this->wafParams->getValue('use403view', 0))
		{
			// Using a view
			$session = $this->app->getSession();

			if (!$session->get('com_admintools.block', false) || $this->app->isClient('administrator'))
			{
				// This is inside an if-block so that we don't end up in an infinite redirection loop
				$session->set('com_admintools.block', true);
				$session->set('com_admintools.message', $message);

				// Close the session (logs out the user)
				$session->close();

				$base = Uri::base();

				if ($this->app->isClient('administrator'))
				{
					$base = rtrim($base);
					$base = substr($base, 0, -13);
				}

				$this->app->redirect($base, 307);
			}

			return;
		}

		// Rescue URL check
		RescueUrl::processRescueURL($this->exceptionsHandler);

		if ($this->app->isClient('administrator'))
		{
			// You can't use Joomla!'s error page in the admin area. Improvise!
			header('HTTP/1.1 403 Forbidden');
			echo $message;

			$this->app->close();
		}

		// Using Joomla!'s error page
		throw new Exception($message, 403);
	}

	/**
	 * Is the IP blocked by a permanent IP blacklist rule?
	 *
	 * @param   string  $ip  The IP address to check. Skip or pass empty string / null to use the current visitor's IP.
	 *
	 * @return  bool
	 */
	public function isIPBlocked($ip = null)
	{
		if (empty($ip))
		{
			// Get the visitor's IP address
			$ip = Filter::getIp();
		}

		$ipTable = Cache::getCache('ipblock');

		return !empty($ipTable) && (Filter::IPinList($ipTable, $ip) === true);
	}
}
