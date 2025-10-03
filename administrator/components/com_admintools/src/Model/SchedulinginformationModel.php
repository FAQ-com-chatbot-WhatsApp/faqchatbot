<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\PHPFinder\PHPFinder;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

#[\AllowDynamicProperties]
class SchedulinginformationModel extends BaseModel
{
	public function getPaths()
	{
		$ret = (object) [
			'cli'      => (object) [
				'supported' => false,
				'path'      => false,
			],
			'joomla'   => (object) [
				'supported' => false,
			],
			'frontend' => (object) [
				'supported' => false,
				'path'      => false,
			],
			'info'     => (object) [
				'windows'      => false,
				'php_path'     => false,
				'php_accurate' => false,
				'root_url'     => false,
				'secret'       => '',
				'feenabled'    => false,
			],
		];

		$absolute_root = rtrim(realpath(JPATH_ROOT), '/\\');
		$isWindows     = (DIRECTORY_SEPARATOR == '\\') || (substr(strtoupper(PHP_OS), 0, 3) == 'WIN');
		$phpPath       = $this->getPhpPath();

		$ret->info->windows      = $isWindows;
		$ret->info->php_path     = $phpPath ?? ($isWindows ? 'c:\path\to\php.exe' : '/path/to/php');
		$ret->info->php_accurate = $phpPath !== null;
		// Get front-end backup secret key
		$cParams              = ComponentHelper::getParams('com_admintools');
		$ret->info->secret    = $cParams->get('frontend_secret_word', '');
		$ret->info->feenabled = $cParams->get('frontend_enable', false);
		// Get root URL
		$ret->info->root_url = rtrim(Uri::root(), '/');

		// Get information for CLI CRON script
		$ret->cli->supported = true;
		$ret->cli->path      = $absolute_root . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'joomla.php';

		// Get information for Joomla Scheduled Tasks
		$ret->joomla->supported = PluginHelper::isEnabled('task', 'admintools');

		// Get information for front-end backup
		$ret->frontend->supported = true;
		if (trim($ret->info->secret) && $ret->info->feenabled)
		{
			$ret->frontend->path = 'index.php?option=com_admintools&view=filescanner&format=raw&key='
			                       . urlencode($ret->info->secret);
		}

		return $ret;
	}

	private function getPhpPath($component = 'com_admintools'): ?string
	{
		static $phpPath = null;

		$cParams     = ComponentHelper::getComponent($component)->getParams();
		$tryAccurate = $cParams->get('accurate_php_cli', 1) == 1;

		if (!$tryAccurate)
		{
			return $phpPath = null;
		}

		$paramsService = Factory::getApplication()
			->bootComponent($component)
			->getComponentParametersService();

		$cParams->set('accurate_php_cli', 0);
		$paramsService->save($cParams);

		$phpPath ??= PHPFinder::make()->getBestPath(PHP_VERSION);

		$cParams->set('accurate_php_cli', 1);
		$paramsService->save($cParams);

		return $phpPath;
	}

}