<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

class TmplSwitch extends Base
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

		return ($this->wafParams->getValue('tmpl', 0) == 1);
	}

	/**
	 * Disable template switching in the URL
	 */
	public function onAfterInitialise(): void
	{
		$tmpl = $this->input->getCmd('tmpl', null);

		if (empty($tmpl))
		{
			return;
		}

		$whitelist = $this->wafParams->getValue('tmplwhitelist', 'component,system');
		$whitelist = is_array($whitelist) ? $whitelist : array_map('trim', explode(',', $whitelist));

		if (empty($whitelist))
		{
			$whitelist = ['component', 'system'];
		}

		$whitelist = array_map('trim', $whitelist);
		$whitelist = array_merge(['component', 'system'], $whitelist);

		if (!is_null($tmpl) && !in_array($tmpl, $whitelist))
		{
			$this->exceptionsHandler->blockRequest('tmpl');
		}
	}
}
