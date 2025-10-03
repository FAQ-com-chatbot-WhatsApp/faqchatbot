<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Helper\TemplateEmails;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerCustomACLTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerEventsTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

class EmailtemplatesController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;

	public function updateEmails($cachable = false, $urlparams = [])
	{
		$this->checkToken('get');

		$returnURL = Route::_('index.php?option=com_admintools&view=Emailtemplates', false);
		$this->setRedirect($returnURL);

		$affected = TemplateEmails::updateAllTemplates();

		$message = ($affected > 0) ?
			Text::plural('COM_ADMINTOOLS_EMAILTEMPLATES_LBL_N_UPDATED', $affected) :
			Text::_('COM_ADMINTOOLS_EMAILTEMPLATES_ERR_NOUPDATE');

		$this->setMessage($message, ($affected > 0) ? 'success' : 'warning');
	}

	public function resetEmails($cachable = false, $urlparams = [])
	{
		$this->checkToken('get');

		$returnURL = Route::_('index.php?option=com_admintools&view=Emailtemplates', false);
		$this->setRedirect($returnURL);

		$affected = TemplateEmails::resetAllTemplates();

		$message = ($affected > 0) ?
			Text::plural('COM_ADMINTOOLS_EMAILTEMPLATES_LBL_N_RESET', $affected) :
			Text::_('COM_ADMINTOOLS_EMAILTEMPLATES_ERR_RESET');

		$this->setMessage($message, ($affected > 0) ? 'success' : 'error');
	}

}