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
use Akeeba\Component\AdminTools\Administrator\Mixin\SendTroubleshootingEmailTrait;
use Akeeba\Component\AdminTools\Administrator\Model\ConfigurewafModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

class ConfigurewafController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use SendTroubleshootingEmailTrait;
	use ControllerReusableModelsTrait;
	use ControllerRegisterTasksTrait;

	public function main()
	{
		$view = $this->getView();
		$cPanelModel = $this->getModel('Controlpanel', 'Administrator', ['ignore_request' => true]);
		$view->setModel($cPanelModel, false);

		$this->display(false);
	}

	public function apply()
	{
		$redirectURL = Route::_('index.php?option=com_admintools&view=Configurewaf', false);
		$msg         = Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_CONFIGSAVED');
		$msgType     = 'success';

		if (!$this->saveOrApply())
		{
			$redirectURL = Route::_('index.php?option=com_admintools&view=Configurewaf', false);
			$msg         = Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_CONFIGNOTSAVED');
			$msgType     = 'error';
		}

		$this->setRedirect($redirectURL, $msg, $msgType);
	}

	public function save()
	{
		$redirectURL = Route::_('index.php?option=com_admintools&view=Webapplicationfirewall', false);
		$msg         = Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_CONFIGSAVED');
		$msgType     = 'success';

		if (!$this->saveOrApply())
		{
			$redirectURL = Route::_('index.php?option=com_admintools&view=Configurewaf', false);
			$msg         = Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_CONFIGNOTSAVED');
			$msgType     = 'error';
		}

		$this->setRedirect($redirectURL, $msg, $msgType);
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	private function saveOrApply(): bool
	{
		$this->checkToken();

		/** @var ConfigurewafModel $model */
		$model = $this->getModel();

		$data = $this->input->getArray();

		// Save data in the session
		$this->app->setUserState('com_admintools.' . $this->getName() . '.data', $model->convertFormDataToDatabaseData($data));

		$form = $model->getForm($data, true);
		$data = $model->validate($form, $data);

		if (is_null($data))
		{
			foreach ($form->getErrors() as $error)
			{
				$this->app->enqueueMessage($error->getMessage(), 'warning');
			}

			return false;
		}

		$data = $model->convertFormDataToDatabaseData($data);

		$this->sendTroubelshootingEmail($this->getName());

		$model->saveConfig($data);

		return true;
	}
}