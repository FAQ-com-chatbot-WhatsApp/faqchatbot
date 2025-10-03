<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;

class AdminSecretWord extends Base
{
	private const COOKIE_KEY_LENGTH = 32;

	private const COOKIE_LIFETIME_DAYS = 30;

	private string $action = 'redirect';

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

		$password     = $this->wafParams->getValue('adminpw', '');
		$this->action = $this->wafParams->getValue('adminpw_action', 'redirect');

		return !empty($password);
	}

	public function onAfterInitialise(): void
	{
		$input  = $this->input;
		$option = $input->getCmd('option', '');

		if ($this->isAdminAccessAttempt())
		{
			// com_ajax must be allowed even when we are not logged in since it _may_ be used by login plugins.
			if ($option == 'com_ajax')
			{
				return;
			}

			$this->checkSecretWord();

			return;
		}

		// If there is an administrator secret word set, upon logout redirect to the site's home page
		$password = $this->wafParams->getValue('adminpw', '');

		if (!empty($password))
		{
			$task = $input->getCmd('task', '');
			$uid  = $input->getInt('uid', 0);

			$loggingMeOut = true;

			if (!empty($uid))
			{
				$myUID        = $this->app->getIdentity()->id;
				$loggingMeOut = ($myUID == $uid);
			}

			if (($option == 'com_login') && ($task == 'logout') && $loggingMeOut)
			{
				$input          = $this->app->input;
				$method         = $input->getMethod();
				$return_encoded = base64_encode('index.php?' . urlencode($password));

				/**
				 * Since Joomla! 3.8.9 the per-method input is case sensitive. We will try using both lower and upper
				 * case (e.g. post and POST) to ensure backwards and forwards compatibility.
				 */
				foreach ([strtolower($method), strtoupper($method)] as $m)
				{
					$input->$m->set('return', $return_encoded);
				}
			}
		}
	}

	public function onUserAfterLogin($options): void
	{
		if (!$this->allowCookieMethod() || !$this->app->isClient('administrator'))
		{
			return;
		}

		$cookieName  = $this->getCookieName();
		$cookieValue = $this->app->input->cookie->get($cookieName);

		if ($cookieValue)
		{
			$cookieArray = explode('.', $cookieValue);
			$filter      = new InputFilter;
			$series      = $filter->clean($cookieArray[1], 'ALNUM');
			$isExisting  = true;
		}

		$db = $this->db;

		if (empty($series))
		{
			$isExisting = false;

			// Create a unique series which will be used over the lifespan of the cookie
			$unique     = false;
			$errorCount = 0;

			do
			{
				$series = UserHelper::genRandomPassword(20);
				$query  = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
					->select($db->quoteName('series'))
					->from($db->quoteName('#__user_keys'))
					->where($db->quoteName('series') . ' = :series')
					->bind(':series', $series);

				try
				{
					$results = $db->setQuery($query)->loadResult();

					if ($results === null)
					{
						$unique = true;
					}
				}
				catch (\RuntimeException $e)
				{
					$errorCount++;

					// We'll let this query fail up to 5 times before giving up.
					if ($errorCount === 5)
					{
						return;
					}
				}
			} while ($unique === false);
		}

		// Generate new cookie
		$token       = UserHelper::genRandomPassword(self::COOKIE_KEY_LENGTH);
		$cookieValue = $token . '.' . $series;
		$lifetime    = self::COOKIE_LIFETIME_DAYS * 86400;

		// Overwrite existing cookie with new value
		$this->app->input->cookie->set(
			$cookieName,
			$cookieValue,
			time() + ($lifetime),
			$this->app->get('cookie_path', '/'),
			$this->app->get('cookie_domain', ''),
			$this->app->isHttpsForced(),
			true
		);

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true));

		if (!$isExisting)
		{
			$future = (time() + $lifetime);

			// Create new record
			$query
				->insert($this->db->quoteName('#__user_keys'))
				->set($this->db->quoteName('user_id') . ' = :userid')
				->set($this->db->quoteName('series') . ' = :series')
				->set($this->db->quoteName('uastring') . ' = :uastring')
				->set($this->db->quoteName('time') . ' = :time')
				->bind(':userid', $options['user']->username)
				->bind(':series', $series)
				->bind(':uastring', $cookieName)
				->bind(':time', $future);
		}
		else
		{
			// Update existing record with new token
			$query
				->update($this->db->quoteName('#__user_keys'))
				->where($this->db->quoteName('user_id') . ' = :userid')
				->where($this->db->quoteName('series') . ' = :series')
				->where($this->db->quoteName('uastring') . ' = :uastring')
				->bind(':userid', $options['user']->username)
				->bind(':series', $series)
				->bind(':uastring', $cookieName);
		}

		$hashedToken = UserHelper::hashPassword($token);

		$query->set($this->db->quoteName('token') . ' = :token')
			->bind(':token', $hashedToken);

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (\RuntimeException $e)
		{
			// Ignore
		}
	}

	public function onUserLogout($user, $options): bool
	{
		// No Admin Tools cookie for frontend users
		if (!$this->allowCookieMethod() || !$this->app->isClient('administrator'))
		{
			return true;
		}

		$cookieName  = $this->getCookieName();
		$cookieValue = $this->app->input->cookie->get($cookieName);

		if (!$cookieValue)
		{
			return true;
		}

		$cookieArray = explode('.', $cookieValue);

		// Filter series since we're going to use it in the query
		$filter = new InputFilter;
		$series = $filter->clean($cookieArray[1], 'ALNUM');

		// Remove the record from the database
		$db    = $this->db;
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->delete($db->quoteName('#__user_keys'))
			->where($db->quoteName('series') . ' = :series')
			->bind(':series', $series);

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (\RuntimeException $e)
		{
			// Ignore errors here
		}

		$this->destroyCookie();

		return true;
	}

	/**
	 * Checks if the secret word is set in the URL query, or redirects the user
	 * back to the home page.
	 */
	protected function checkSecretWord()
	{
		$password = $this->wafParams->getValue('adminpw', '');
		$myURI    = Uri::getInstance();
		$randomPw = $password;

		while ($randomPw === $password)
		{
			$randomPw = UserHelper::genRandomPassword(64);
		}

		/**
		 * If the query param with the name $password is not defined, the default value $randomPw will be returned. If
		 * it is defined, it will return null or the value after the equal sign.
		 *
		 * Therefore, if the value we get is NOT $randomPw we know we got the correct secret word!
		 */
		if ($myURI->getVar($password, $randomPw) !== $randomPw)
		{
			return;
		}

		/**
		 * The user has not provided a valid secret URL parameter. Do I have a cookie instead?
		 */
		if ($this->allowCookieMethod() && $this->isValidCookie())
		{
			return;
		}

		// TODO Do I redirect, or do I block the request?
		if ($this->action === 'redirect')
		{
			// Uh oh... Unauthorized access! Let's redirect the intruder back to the site's home page.
			if (!$this->exceptionsHandler->logRequest('adminpw'))
			{
				return;
			}

			$this->redirectAdminToHome();

			return;
		}

		$this->exceptionsHandler->blockRequest('adminpw');
	}

	private function allowCookieMethod(): bool
	{
		return $this->wafParams->getValue('adminpw_cookie', '3') != 0;
	}

	private function destroyCookie(): void
	{
		$cookieName   = $this->getCookieName();
		$cookiePath   = $this->app->get('cookie_path', '/');
		$cookieDomain = $this->app->get('cookie_domain', '');

		$this->app->input->cookie->set($cookieName, '', 1, $cookiePath, $cookieDomain);
	}

	private function getCookieName(): string
	{
		return 'admintools_adminaccess_' . UserHelper::getShortHashedUserAgent();
	}

	private function isValidCookie(): bool
	{
		$cookieName  = $this->getCookieName();
		$cookieValue = $this->app->input->cookie->get($cookieName);

		if (empty($cookieValue))
		{
			return false;
		}


		// Check for valid cookie value
		$cookieArray = explode('.', $cookieValue);

		if (count($cookieArray) !== 2)
		{
			$this->destroyCookie();
			Log::add('Invalid Admin Tools cookie detected.', Log::WARNING, 'error');

			return false;
		}

		// Filter series since we're going to use it in the query
		$filter = new InputFilter();
		$series = $filter->clean($cookieArray[1], 'ALNUM');
		$db     = $this->db;

		// Remove expired tokens
		$this->removeExpiredTokens();

		// Find the matching record if it exists.
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->quoteName(['user_id', 'token', 'series', 'time']))
			->from($db->quoteName('#__user_keys'))
			->where($db->quoteName('series') . ' = :series')
			->where($db->quoteName('uastring') . ' = :uastring')
			->order($db->quoteName('time') . ' DESC')
			->bind(':series', $series)
			->bind(':uastring', $cookieName);

		try
		{
			$results = $db->setQuery($query)->loadObjectList();
		}
		catch (\RuntimeException $e)
		{
			return false;
		}

		if (count($results) !== 1)
		{
			$this->destroyCookie();

			return false;
		}

		// We have a user with one cookie with a valid series and a corresponding record in the database.
		if (!UserHelper::verifyPassword($cookieArray[0], $results[0]->token))
		{
			/**
			 * The cookie password does not match. This seems to be an attack against the site. Either the series was
			 * guessed correctly or a cookie was stolen and used twice (by the attacker and the victim). Immediately
			 * delete all tokens for this user to prevent successful exploitation.
			 */
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->delete($db->quoteName('#__user_keys'))
				->where($db->quoteName('user_id') . ' = :userid')
				->bind(':userid', $results[0]->user_id);

			try
			{
				$db->setQuery($query)->execute();
			}
			catch (\RuntimeException $e)
			{
				// Log an alert for the site admin
				Log::add(
					sprintf('Failed to delete Admin Tools cookie token for user %s with the following error: %s', $results[0]->user_id, $e->getMessage()),
					Log::WARNING,
					'security'
				);
			}

			// Destroy the cookie in the browser.
			$this->destroyCookie();

			// Log a security warning
			Log::add(sprintf('Admin Tools failed to assert that user %d should have access to the administrator login page using the cookie validation method.', $results[0]->user_id), Log::WARNING, 'security');

			return false;
		}

		// Make sure the user referenced by the cookie is valid
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->quoteName(['id', 'username', 'password']))
			->from($db->quoteName('#__users'))
			->where($db->quoteName('username') . ' = :userid')
			->where($db->quoteName('requireReset') . ' = 0')
			->bind(':userid', $results[0]->user_id);

		try
		{
			$result = $db->setQuery($query)->loadObject();
		}
		catch (\RuntimeException $e)
		{
			return false;
		}

		if (!$result)
		{
			return false;
		}

		// Show an optional reminder
		$featureToggleValue = $this->wafParams->getValue('adminpw_cookie', '3');

		if ($featureToggleValue >= 2)
		{
			$this->app->enqueueMessage(Text::_('PLG_ADMINTOOLS_MSG_ADMINPW_COOKIE'));
		}

		if ($featureToggleValue >= 3)
		{
			$user    = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($result->id);
			$fakeUri = new Uri(Uri::base());
			$fakeUrl = $fakeUri->toString() . '?<em>something</em>';

			$langString = $user->authorise('core.admin')
				? 'PLG_ADMINTOOLS_MSG_ADMINPW_COOKIE_SUPERUSER'
				: 'PLG_ADMINTOOLS_MSG_ADMINPW_COOKIE_NONSUPERUSER';

			$this->app->enqueueMessage(Text::sprintf($langString, $fakeUrl));
		}


		return true;
	}

	private function removeExpiredTokens(): void
	{
		$now = time();
		$db  = $this->db;

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->delete($db->quoteName('#__user_keys'))
			->where($db->quoteName('time') . ' < :now')
			->bind(':now', $now);

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (\RuntimeException $e)
		{
			// Ignore errors in this query.
		}
	}
}
