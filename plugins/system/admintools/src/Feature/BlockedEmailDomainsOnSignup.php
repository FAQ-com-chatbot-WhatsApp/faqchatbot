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

class BlockedEmailDomainsOnSignup extends Base
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

		$domains = $this->wafParams->getValue('blockedemaildomains', []);

		if (empty($domains))
		{
			return false;
		}

		return true;
	}

	public function onUserBeforeSave($olduser, $isnew, $user): bool
	{
		$allowed = false;
		$block   = ($this->wafParams->getValue('filteremailregistration', 'block') == 'block');
		$domains = $this->wafParams->getValue('blockedemaildomains', []);

		if (is_string($domains))
		{
			$domains = array_map('trim', explode(',', $domains));
		}

		$domains = array_map(
			function ($x) {
				return is_array($x) ? $x[0] : $x;
			}, is_array($domains) ? $domains : []
		);

		foreach ($domains as $domain)
		{
			// Block specific domains and we have a match
			if ($block && (stripos($user['email'], trim($domain)) !== false))
			{
				// Load the component's administrator translation files
				$jlang = $this->app->getLanguage();
				$jlang->load('com_admintools', JPATH_ADMINISTRATOR, 'en-GB', true);
				$jlang->load('com_admintools', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
				$jlang->load('com_admintools', JPATH_ADMINISTRATOR, null, true);

				throw new Exception(Text::sprintf('PLG_ADMINTOOLS_ERR_BLOCKEDEMAILDOMAINS', $domain));
			}

			// Allow only specific domains and the user is using a domain that is NOT in the list
			if (!$block && (stripos($user['email'], trim($domain)) !== false))
			{
				// Let's raise the flag to mark that we got a match
				$allowed = true;
			}
		}

		// If I have to allow only specific email domains and we didn't have a match, let's block the registration
		if (!$block && !$allowed)
		{
			$jlang = $this->app->getLanguage();
			$jlang->load('com_admintools', JPATH_ADMINISTRATOR, 'en-GB', true);
			$jlang->load('com_admintools', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
			$jlang->load('com_admintools', JPATH_ADMINISTRATOR, null, true);

			throw new Exception(Text::sprintf('PLG_ADMINTOOLS_ERR_BLOCKEDEMAILDOMAINS', $user['email']));
		}

		return true;
	}
}
