<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Helper\Storage;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerCustomACLTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\AdminTools\Administrator\Model\ExportimportModel;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\Input\Input;

class ExportimportController extends BaseController
{
	use ControllerCustomACLTrait;
	use ControllerEventsTrait;
	use ControllerReusableModelsTrait;

	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		$config['default_task'] = $config['default_task'] ?? 'export';

		parent::__construct($config, $factory, $app, $input);
	}

	public function export()
	{
		$this->getModel()->setState('task', 'export');
		$this->display(false);
	}

	public function import()
	{
		$this->display(false);
	}

	public function doexport()
	{
		/** @var ExportimportModel $model */
		$model = $this->getModel();
		$data  = $model->exportData();

		if (!$data)
		{
			$redirectUrl = Route::_('index.php?option=com_admintools&view=Exportimport&task=export', false);
			$this->setRedirect($redirectUrl, Text::_('COM_ADMINTOOLS_EXPORTIMPORT_LBL_SELECT_DATA_WARN'), 'warning');

			return;
		}

		$json = json_encode($data);

		// Clear cache
		while (@ob_end_clean())
		{
		}

		// Disable caching
		header("Cache-Control: no-store, max-age=0, must-revalidate, no-transform", true);

		// Send MIME headers
		header('Content-Type: application/json');
		header("Accept-Ranges: bytes");
		header('Content-Disposition: attachment; filename="admintools_settings.json"');
		header('Content-Transfer-Encoding: text');
		header('Connection: close');
		header('Content-Length: ' . strlen($json));

		error_reporting(0);
		set_time_limit(0);

		echo $json;

		$this->app->close();
	}

	public function doimport()
	{
		$params = Storage::getInstance();
		$params->setValue('quickstart', 1, true);

		/** @var ExportimportModel $model */
		$model = $this->getModel();

		try
		{
			$model->importDataFromRequest();

			$type = null;
			$msg  = Text::_('COM_ADMINTOOLS_EXPORTIMPORT_LBL_IMPORT_OK');
		}
		catch (Exception $e)
		{
			$type = 'error';
			$msg  = $e->getMessage();
		}

		$redirectUrl = Text::_('index.php?option=com_admintools&view=Exportimport&task=import', false);
		$this->setRedirect($redirectUrl, $msg, $type);
	}
}