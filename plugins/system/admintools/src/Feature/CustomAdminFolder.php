<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserHelper;
use Akeeba\Plugin\System\AdminTools\Utility\Filter;

/**
 * Allows users to "rename" their administrator directory. In fact, the "rename" is a smokes and mirrors trick,
 * manipulating Joomla!'s SEF routing to mask the administrator directory.
 */
class CustomAdminFolder extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		$folder = $this->wafParams->getValue('adminlogindir');

		// Custom admin folder is disabled
		if (!$folder || !$this->app->get('sef') || !$this->app->get('sef_rewrite'))
		{
			return false;
		}

		return true;
	}

	/**
	 * Hooks to Joomla!'s earliest plugin handler
	 */
	public function onAfterInitialise(): void
	{
		$this->customAdminFolder();

		if ($this->isAdminAccessAttempt())
		{
			$this->checkCustomAdminFolder();
		}

		if ($this->isAdminLogout())
		{
			$this->setLogoutCookie();
		}
	}

	/**
	 * If the user is trying to access the custom admin folder set the necessary cookies and redirect them to the
	 * administrator page.
	 */
	protected function customAdminFolder()
	{
		$ip = Filter::getIp();

		// I couldn't detect the ip, let's stop here
		if (empty($ip) || ($ip == '0.0.0.0'))
		{
			return;
		}

		// Some user agents don't set a UA string at all
		if (!array_key_exists('HTTP_USER_AGENT', $_SERVER))
		{
			return;
		}

		$ua             = $this->app->client;
		$uaString       = $ua->userAgent;
		$browserVersion = $ua->browserVersion;

		$uaShort = str_replace($browserVersion, 'abcd', $uaString);

		$uri = Uri::getInstance();
		$db  = $this->db;

		// We're not trying to access to the custom folder
		$folder = $this->wafParams->getValue('adminlogindir');

		if (str_replace($uri->root(), '', trim($uri->current(), '/')) != $folder)
		{
			return;
		}

		$hash = UserHelper::hashPassword($ip . $uaShort);

		$data = (object) [
			'series'      => UserHelper::genRandomPassword(64),
			'client_hash' => $hash,
			'valid_to'    => date('Y-m-d H:i:s', time() + 180),
		];

		$db->insertObject('#__admintools_cookies', $data);

		$cookie_domain = $this->app->get('cookie_domain', '');
		$cookie_path   = $this->app->get('cookie_path', '/');
		$isSecure      = $this->app->get('force_ssl', 0) ? true : false;

		setcookie('admintools', $data->series, time() + 180, $cookie_path, $cookie_domain, $isSecure, true);
		setcookie('admintools_logout', null, 1, $cookie_path, $cookie_domain, $isSecure, true);

		$uri->setPath(str_replace($folder, 'administrator/index.php', $uri->getPath()));

		$this->app->redirect($uri->toString(), 307);
	}

	/**
	 * When the user is trying to access the administrator folder without being logged in make sure they had already
	 * entered the custom administrator folder before coming here. Otherwise they are unauthorised and must be booted to
	 * the site's front-end page.
	 */
	protected function checkCustomAdminFolder()
	{
		// Initialise
		$seriesFound = false;
		$db          = $this->db;

		// Get the series number from the cookie
		$series = $this->input->cookie->get('admintools', null);

		// If we are told that this is a user logging out redirect them to the front-end home page, do not log a
		// security exception, expire the cookie
		$logout = $this->input->cookie->get('admintools_logout', null, 'string');
		if ($logout == '!!!LOGOUT!!!')
		{
			$cookie_domain = $this->app->get('cookie_domain', '');
			$cookie_path   = $this->app->get('cookie_path', '/');
			$isSecure      = $this->app->get('force_ssl', 0) ? true : false;
			setcookie('admintools_logout', null, 1, $cookie_path, $cookie_domain, $isSecure, true);

			$this->redirectAdminToHome();

			return;
		}

		// Do we have a series?
		$isValid = !empty($series);

		// Does the series exist in the db? If so, load it
		if ($isValid)
		{
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select('*')
				->from($db->qn('#__admintools_cookies'))
				->where($db->qn('series') . ' = ' . $db->q($series));
			$db->setQuery($query);
			$storedData = $db->loadObject();

			$seriesFound = true;

			if (!is_object($storedData))
			{
				$isValid     = false;
				$seriesFound = false;
			}
		}

		// Is the series still valid or did someone manipulate the cookie expiration?
		if ($isValid)
		{
			$jValid = strtotime($storedData->valid_to);

			if ($jValid < time())
			{
				$isValid = false;
			}
		}

		// Does the UA match the stored series?
		if ($isValid)
		{
			$ip = Filter::getIp();

			$ua             = $this->app->client;
			$uaString       = $ua->userAgent;
			$browserVersion = $ua->browserVersion;

			$uaShort = str_replace($browserVersion, 'abcd', $uaString);

			$notSoSecret = $ip . $uaShort;

			$isValid = UserHelper::verifyPassword($notSoSecret, $storedData->client_hash);
		}

		// Last check: session state variable
		if ($this->app->getSession()->get('com_admintools.adminlogindir', 0))
		{
			$isValid = true;
		}

		// Delete the series cookie if found
		if ($seriesFound)
		{
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->delete($db->qn('#__admintools_cookies'))
				->where($db->qn('series') . ' = ' . $db->q($series));
			$db->setQuery($query);
			$db->execute();
		}

		// Log an exception and redirect to homepage if we can't validate the user's cookie / session parameter
		if (!$isValid)
		{
			$this->exceptionsHandler->logRequest('admindir');

			$this->redirectAdminToHome();

			return;
		}

		// Otherwise set the session parameter
		if ($seriesFound)
		{
			$this->app->getSession()->set('com_admintools.adminlogindir', 1);
		}
	}

	protected function setLogoutCookie()
	{
		$cookie_domain = $this->app->get('cookie_domain', '');
		$cookie_path   = $this->app->get('cookie_path', '/');
		$isSecure      = $this->app->get('force_ssl', 0) ? true : false;

		setcookie('admintools_logout', '!!!LOGOUT!!!', time() + 180, $cookie_path, $cookie_domain, $isSecure, true);
	}

	/**
	 * Checks if a user is trying to log out
	 *
	 * @return bool
	 */
	protected function isAdminLogout()
	{
		// Not back-end at all. Bail out.
		if (!$this->app->isClient('administrator'))
		{
			return false;
		}

		// If the user is not already logged in we don't have a logout attempt
		$user = $this->app->getIdentity();

		if ($user->guest)
		{
			return false;
		}

		$input  = $this->input;
		$option = $input->getCmd('option', null);
		$task   = $input->getCmd('task', null);

		if (($option == 'com_login') && ($task == 'logout'))
		{
			return true;
		}

		// Check for malicious direct post without a valid token. In this case it's not a logout.
		$token = $this->app->getFormToken();
		$token = $this->input->get($token, false, 'raw');

		if ($token === false)
		{
			return Session::checkToken('request');
		}

		return false;
	}
}
