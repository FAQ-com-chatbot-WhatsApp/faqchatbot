<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;

/**
 * @deprecated 8.0  Use the Joomla Scheduled Tasks instead
 */
class CacheExpiration extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return ($this->params->get('cacheexpire', 0) == 1);
	}

	public function onAfterInitialise(): void
	{
		$minutes = (int) $this->params->get('cacheexp_freq', 0);

		if ($minutes <= 0)
		{
			return;
		}

		$lastJob = $this->getTimestamp('cache_expire');
		$nextJob = $lastJob + $minutes * 60;

		$now = clone Factory::getDate();

		if ($now->toUnix() >= $nextJob)
		{
			$this->setTimestamp('cache_expire');
			$this->expireCache();
		}
	}

	/**
	 * Expires cache items
	 */
	private function expireCache()
	{
		$er = @error_reporting(0);
		/** @var \Joomla\CMS\Cache\CacheController $cache */
		$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController(
			'callback', ['defaultgroup' => '']
		);
		$cache->gc();
		@error_reporting($er);
	}
}
