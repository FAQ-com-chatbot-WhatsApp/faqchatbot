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
use Akeeba\Component\AdminTools\Administrator\Model\UnblockipModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

class UnblockipController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerRegisterTasksTrait;

	public function unblock()
	{
		$this->checkToken();

		$ip = $this->input->getString('ip', '');

		/** @var UnblockipModel $model */
		$model  = $this->getModel();
		$status = $model->unblockIP($ip);

		$url = Route::_('index.php?option=com_admintools&view=Unblockip', false);

		$msg     = Text::_('COM_ADMINTOOLS_UNBLOCKIP_LBL_OK');
		$msgType = 'success';

		if (!$status)
		{
			$msg     = Text::_('COM_ADMINTOOLS_UNBLOCKIP_LBL_NOTFOUND');
			$msgType = 'warning';
		}

		$this->setRedirect($url, $msg, $msgType);
	}

}