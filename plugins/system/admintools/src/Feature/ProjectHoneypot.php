<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Akeeba\Plugin\System\AdminTools\Utility\Filter;

class ProjectHoneypot extends Base
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
		if (!$this->app->isClient('site'))
		{
			return false;
		}

		return ($this->wafParams->getValue('httpblenable', 0) == 1);
	}

	/**
	 * Runs the Project Honeypot HTTP:BL integration
	 */
	public function onAfterInitialise(): void
	{
		if (!$this->isIPBlocked())
		{
			return;
		}

		$this->exceptionsHandler->blockRequest('httpbl', '', $this->extraInfo);
	}

	/**
	 * Is the IP blocked by a Geo-blocking rule?
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

		// Load parameters
		$httpbl_key = $this->wafParams->getValue('bbhttpblkey', '');
		$minthreat  = $this->wafParams->getValue('httpblthreshold', 25);
		$maxage     = $this->wafParams->getValue('httpblmaxage', 30);
		$suspicious = $this->wafParams->getValue('httpblblocksuspicious', 0);

		// Make sure we have an HTTP:BL  key set
		if (empty($httpbl_key))
		{
			return false;
		}

		if ($ip == '0.0.0.0')
		{
			return false;
		}

		if (strpos($ip, '::') === 0)
		{
			$ip = substr($ip, strrpos($ip, ':') + 1);
		}

		// No point continuing if we can't get an address, right?
		if (empty($ip))
		{
			return false;
		}

		// IPv6 addresses are not supported by HTTP:BL yet
		if (strpos($ip, ":"))
		{
			return false;
		}

		$find   = implode('.', array_reverse(explode('.', $ip)));
		$result = gethostbynamel($httpbl_key . ".{$find}.dnsbl.httpbl.org.");

		if (empty($result))
		{
			return false;
		}

		$ip = explode('.', $result[0]);

		// Make sure it's a valid response
		if ($ip[0] != 127)
		{
			return false;
		}

		// Do not block search engines
		if ($ip[3] == 0)
		{
			return false;
		}

		// Block harvesters and comment spammers
		$block = ($ip[3] & 2) || ($ip[3] & 4);

		// Do not block "suspicious" (not confirmed) IPs unless asked so
		if (!$suspicious && ($ip[3] & 1))
		{
			$block = false;
		}

		$block = $block && ($ip[1] <= $maxage);
		$block = $block && ($ip[2] >= $minthreat);

		if (!$block)
		{
			return false;
		}

		$classes = [];

		if ($ip[3] & 1)
		{
			$classes[] = 'Suspicious';
		}

		if ($ip[3] & 2)
		{
			$classes[] = 'Email Harvester';
		}

		if ($ip[3] & 4)
		{
			$classes[] = 'Comment Spammer';
		}

		$class           = implode(', ', $classes);
		$this->extraInfo = <<<ENDINFO
HTTP:BL analysis for blocked spammer's IP address $ip
	Attacker class		: $class
	Last activity		: $ip[1] days ago
	Threat level		: $ip[2] --> see http://is.gd/mAwMTo for more info

ENDINFO;

		return true;
	}

}
