<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Akeeba\Plugin\System\AdminTools\Utility\Cache;
use Akeeba\Plugin\System\AdminTools\Utility\Filter;
use Akeeba\Plugin\System\AdminTools\Utility\RescueUrl;

class AdminIPExclusiveAllow extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		if (!$this->isAdminAccessAttempt())
		{
			return false;
		}

		return ($this->wafParams->getValue('ipwl', 0) == 1);
	}

	/**
	 * Filters back-end access by IP. If the IP of the visitor is not included
	 * in the whitelist, he gets redirected to the home page
	 */
	public function onAfterInitialise(): void
	{
		$ipTable = Cache::getCache('adminiplist');

		if (empty($ipTable) || (Filter::IPinList($ipTable) !== false))
		{
			return;
		}

		// Rescue URL check
		RescueUrl::processRescueURL($this->exceptionsHandler);

		if (!$this->exceptionsHandler->logRequest('ipwl'))
		{
			return;
		}

		$this->redirectAdminToHome();
	}
}
