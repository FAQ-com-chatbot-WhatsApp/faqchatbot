<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Akeeba\Component\AdminTools\Administrator\Model\ExportimportModel;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;

/**
 * @deprecated 8.0  Use the Joomla Scheduled Tasks instead
 */
class ImportSettings extends Base
{
	private $remote_url = '';

	private $freq = 0;

	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		$this->remote_url = $this->params->get('autoimport_url', '');
		$this->freq       = $this->params->get('autoimport_freq', 0);

		// Do not run if we don't have an URL or a frequency set
		return ($this->remote_url && ($this->freq > 0));
	}

	/**
	 * Run the settings import  on a schedule
	 */
	public function onAfterInitialise(): void
	{
		$lastJob = $this->getTimestamp('autoimport_settings');
		$nextJob = $lastJob + $this->freq * 60 * 60;

		$now = clone Factory::getDate();

		if ($now->toUnix() >= $nextJob)
		{
			$this->setTimestamp('autoimport_settings');
			$this->importSettings();
		}
	}

	/**
	 * Actually imports settings file from a remote URL
	 */
	private function importSettings()
	{
		$http = HttpFactory::getHttp();

		try
		{
			$response = $http->get($this->remote_url);
			$settings = $response->getBody();

			if ($response->getStatusCode() > 299)
			{
				$settings = '';
			}
		}
		catch (Exception $e)
		{
			$settings = '';
		}

		// Something happened during the download, simply ignore it to avoid the site to crash
		if (empty($settings))
		{
			return;
		}

		/** @var ExportimportModel $importModel */
		$importModel = new ExportimportModel();

		try
		{
			$importModel->importData($settings);
		}
		catch (Exception $e)
		{
			// Do not die if anything goes wrong (ie bad or invalid settings file)
		}
	}
}
