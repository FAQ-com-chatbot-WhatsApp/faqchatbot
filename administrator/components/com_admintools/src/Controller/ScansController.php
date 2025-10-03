<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerCustomACLTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerRegisterTasksTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\AdminTools\Administrator\Model\ScansModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;

class ScansController extends AdminController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerRegisterTasksTrait;
	use ControllerReusableModelsTrait;

	protected $text_prefix = 'COM_ADMINTOOLS_SCANS';

	public function getModel($name = 'Scan', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function startscan()
	{
		/** @var ScansModel $model */
		$model = $this->getModel('Scans', 'Administrator', ['ignore_request' => true]);

		$retArray = $model->startScan();

		@ob_clean();
		echo json_encode($retArray);
		$this->app->close();
	}

	public function stepscan()
	{
		/** @var ScansModel $model */
		$model = $this->getModel('Scans', 'Administrator', ['ignore_request' => true]);

		$retArray = $model->stepScan();

		@ob_clean();
		echo json_encode($retArray);
		$this->app->close();
	}

	public function purge()
	{
		/** @var ScansModel $model */
		$model = $this->getModel('Scans', 'Administrator', ['ignore_request' => true]);
		$type  = 'success';

		if ($model->purgeFilesCache())
		{
			$msg = Text::_('COM_ADMINTOOLS_SCAN_LBL_MSG_PURGE_COMPLETED');
		}
		else
		{
			$msg  = Text::_('COM_ADMINTOOLS_SCAN_LBL_MSG_PURGE_ERROR');
			$type = 'error';
		}

		$this->setRedirect(Route::_('index.php?option=com_admintools&view=Scans', false), $msg, $type);
	}

	protected function onBeforeMain()
	{
		/** @var ScansModel $model */
		$model = $this->getModel('Scans', 'Administrator', ['ignore_request' => true]);

		$model->removeIncompleteScans();
	}
}