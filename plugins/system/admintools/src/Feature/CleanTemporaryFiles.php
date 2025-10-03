<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Akeeba\Component\AdminTools\Administrator\Model\CleantempdirectoryModel;
use Exception;
use Joomla\CMS\Factory;

/**
 * @deprecated 8.0  Use the Joomla Scheduled Tasks instead
 */
class CleanTemporaryFiles extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return ($this->params->get('cleantemp', 0) == 1);
	}

	public function onAfterInitialise(): void
	{
		$minutes = (int) $this->params->get('cleantemp_freq', 0);

		if ($minutes <= 0)
		{
			return;
		}

		$lastJob = $this->getTimestamp('clean_temp');
		$nextJob = $lastJob + $minutes * 60;

		$now = clone Factory::getDate();

		if ($now->toUnix() >= $nextJob)
		{
			$this->setTimestamp('clean_temp');
			$this->tempDirectoryCleanup();
		}
	}

	/**
	 * Cleans up the temporary director
	 */
	private function tempDirectoryCleanup()
	{
		try
		{
			$model = new CleantempdirectoryModel();

			while (!$model->process())
			{
				// Just to keep static analysers happy.
				$i++;
			}
		}
		catch (Exception $e)
		{
			// Avoid any blank page on error
		}
	}
}
