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
use Akeeba\Component\AdminTools\Administrator\Model\ControlpanelModel;
use Akeeba\Component\AdminTools\Administrator\Model\QuickstartModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

class QuickstartController extends BaseController
{
	use ControllerCustomACLTrait;
	use ControllerReusableModelsTrait;
	use ControllerRegisterTasksTrait;
	use SendTroubleshootingEmailTrait;
	use ControllerEventsTrait;

	public function onBeforeMain()
	{
		/** @var ConfigurewafModel $wafConfigModel */
		$wafConfigModel = $this->getModel('Configurewaf');
		$this->getView()->setModel($wafConfigModel);

		/** @var ControlpanelModel $cpanelModel */
		$cpanelModel = $this->getModel('Controlpanel');
		$this->getView()->setModel($cpanelModel);
	}

	public function commit()
	{
		// CSRF prevention
		$this->checkToken();

		$this->sendTroubelshootingEmail($this->getName());

		/** @var QuickstartModel $model */
		$model = $this->getModel();

		$stateVariables = [
			'adminpw', 'admin_username', 'admin_password', 'emailonadminlogin', 'ipwl', 'detectedip', 'nonewadmins',
			'nofesalogin', 'enablewaf', 'autoban', 'autoblacklist', 'emailbreaches', 'bbhttpblkey',
			'htmaker', 'allowed_domains'
		];

		foreach ($stateVariables as $k)
		{
			$model->setState($k, $this->input->getRaw($k));
		}

		$model->applyPreferences();

		$message = Text::_('COM_ADMINTOOLS_QUICKSTART_MSG_DONE');
		$this->setRedirect(Route::_('index.php?option=com_admintools&view=Controlpanel', false), $message, 'success');
	}
}