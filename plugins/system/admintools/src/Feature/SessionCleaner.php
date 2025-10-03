<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Akeeba\Component\AdminTools\Administrator\Model\DatabasetoolsModel;
use Joomla\CMS\Factory;
use Throwable;

/**
 * @deprecated 8.0  Use the Joomla Scheduled Tasks instead
 */
class SessionCleaner extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return ($this->params->get('sescleaner', 0) == 1);
	}

	/**
	 * Run the session cleaner (garbage collector) on a schedule
	 */
	public function onAfterInitialise(): void
	{
		$minutes = (int) $this->params->get('ses_freq', 0);

		if ($minutes <= 0)
		{
			return;
		}

		$lastJob = $this->getTimestamp('session_clean');
		$nextJob = $lastJob + $minutes * 60;

		$now = clone Factory::getDate();

		if ($now->toUnix() >= $nextJob)
		{
			$this->setTimestamp('session_clean');
			$this->purgeSession();
		}
	}

	/**
	 * Purges expired sessions
	 */
	private function purgeSession()
	{
		try
		{
			/** @var DatabasetoolsModel $model */
			$model = new DatabasetoolsModel();

			// This also runs the first batch of deletions
			$model->garbageCollectSessions();
		}
		catch (Throwable $e)
		{
			// Avoid any blank page on error
		}
	}
}
