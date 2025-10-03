<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

class ItemidShield extends Base
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

		if ($this->skipFiltering)
		{
			return false;
		}

		return ($this->wafParams->getValue('itemidshield', 2) != 0);
	}

	/**
	 * First run of this feature, before SEF routing has taken place.
	 *
	 * @since  7.4.5
	 */
	public function onAfterInitialise(): void
	{
		$this->onAfterRoute();
	}

	/**
	 * Check if Itemid contains a valid value (integers only), after SEF routing has taken place.
	 */
	public function onAfterRoute(): void
	{
		$waf_value = $this->wafParams->getValue('itemidshield', 2);

		// Always cast it to a string. If we're fetching a default value from the database (ie the homepage) it will be
		// an integer instead of a string
		$itemid    = (string)$this->input->get('Itemid', '', 'raw');

		if (!$itemid || !$waf_value)
		{
			return;
		}

		$is_valid = (string)(intval($itemid)) === $itemid && $itemid >= 0;

		if ($is_valid)
		{
			return;
		}

		if ($waf_value == 2)
		{
			$cleaned = intval($itemid);
			$this->input->set('Itemid', $cleaned);

			return;
		}

		// If I'm here, it means that I have to block and log the request
		$this->exceptionsHandler->blockRequest('itemidshield');
	}
}
