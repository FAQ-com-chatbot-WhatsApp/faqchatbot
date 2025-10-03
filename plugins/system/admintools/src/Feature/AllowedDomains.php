<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Joomla\CMS\Uri\Uri;

class AllowedDomains extends Base
{
	protected $allowedDomains = [];

	public function isEnabled()
	{
		$allowedDomains = $this->wafParams->getValue('allowed_domains', []);
		$allowedDomains = is_object($allowedDomains) ? (array) $allowedDomains : $allowedDomains;

		// In case we are migrating from Admin Tools 6
		if (is_string($allowedDomains))
		{
			$allowedDomains = explode(',', $allowedDomains);
		}

		$allowedDomains = array_map(function ($x) {
			return is_array($x) ? $x[0] : $x;
		}, $allowedDomains);

		$this->setAllowedDomains($allowedDomains);

		return !empty($this->allowedDomains);
	}

	public function onAfterInitialise(): void
	{
		$uri  = Uri::getInstance();
		$host = $uri->getHost();

		// No host information passed from the server? Can't protect you.
		if (empty($host))
		{
			return;
		}

		// Is the host explicitly allowed
		$host = strtolower($host);

		if (in_array($host, $this->allowedDomains))
		{
			return;
		}

		// Does the host match the live_site variable?
		$lsHost = $this->getLiveSiteHost();

		if (!empty($lsHost) && ($host === $lsHost))
		{
			return;
		}

		// Domains resolving to localhost are always allowed (lets you restore locally)
		$ip = gethostbyname($host);

		if (($ip === '127.0.0.1') || ($ip === '::1'))
		{
			return;
		}

		$plural = count($this->allowedDomains) > 1 ? 's' : '';
		$allowedDomainsText = implode('</code>, <code>', $this->allowedDomains);

		header('HTTP/1.0 421');
		echo <<< HTML
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>421 Misdirected Request</title>
</head><body>
<h1>Bad Request</h1>
<p>
	The domain you are using to access this site is not allowed. This site can only be accessed through the domain name$plural <code>$allowedDomainsText</code>.
</p>
<p>
	If you clicked a link which brought you here please do NOT contact the administrator of the site$plural you see above. The problem is a wrong configuration in the web server of the company hosting the site, something that the site owner cannot control.
</p>
<p>
	If you are the administrator of this domain name, and you were not expecting this error please go to your site's backend, Components, Admin Tools, Web Application Firewall, Configure WAF, Allowed Domains and add the domain name <code>$host</code> to the list, then click on Save to update your site's settings.
</p>
</body></html>
HTML;

		$this->app->close();
	}

	private function setAllowedDomains(?array $domains)
	{
		$this->allowedDomains = [];

		if (empty($domains))
		{
			return;
		}

		$domains = array_map('trim', $domains);
		$domains = array_map('strtolower', $domains);
		$domains = array_filter($domains, function ($x) {
			return !empty($x);
		});

		$extraDomains = array_map(function ($x) {
			if (($x == 'localhost') || (substr($x, -6) === '.local') || (substr($x, -12) === '.localdomain'))
			{
				return '';
			}

			if (substr($x, 0, 4) === 'www.')
			{
				return substr($x, 4);
			}

			return 'www.' . $x;
		}, $domains);

		$domains = array_merge($domains, $extraDomains);
		$domains = array_filter($domains, function ($x) {
			return !empty($x);
		});

		$this->allowedDomains = array_unique($domains);
	}

	private function getLiveSiteHost()
	{
		$live_site = trim($this->app->get('live_site') ?: '');

		if (empty($live_site))
		{
			return null;
		}

		$uri = Uri::getInstance($live_site);

		return strtolower($uri->getHost() ?: '') ?: null;
	}
}