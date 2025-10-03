<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\View\Configurewaf;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AdminTools\Administrator\Model\ConfigurewafModel;
use Akeeba\Component\AdminTools\Administrator\Model\ControlpanelModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	use ViewLoadAnyTemplateTrait;
	use ViewToolbarTrait;

	/**
	 * WAF configuration
	 *
	 * @var  array
	 */
	public $wafconfig;

	/**
	 * The detected visitor's IP address
	 *
	 * @var  string
	 */
	public $myIP = '';

	/**
	 * The Joomla form used to generate the controls
	 *
	 * @var Form
	 */
	public $form;

	public function display($tpl = null)
	{
		/** @var ConfigurewafModel $model */
		$model = $this->getModel();
		/** @var ControlpanelModel $cpanelModel */
		$cpanelModel = $this->getModel('Controlpanel');

		$this->form      = $model->getForm();
		$this->myIP      = $cpanelModel->getVisitorIP();
		$this->wafconfig = $model->getConfig();

		// Push translations
		Text::script('JNO', true);
		Text::script('JYES', true);

		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		$view = $this->getName();

		ToolbarHelper::title(Text::_('COM_ADMINTOOLS_TITLE_' . $view), 'admintools');

		$bar = $this->getToolbarCompat();

		$saveGroup = $bar->dropdownButton('save-group');
		$childBar  = $saveGroup->getChildToolbar();

		$childBar->apply('apply');
		$childBar->save('save');

		ToolbarHelper::back('JTOOLBAR_BACK', Route::_('index.php?option=com_admintools&view=Webapplicationfirewall', false));

		ToolbarHelper::inlinehelp();

		ToolbarHelper::help(null, false, 'https://www.akeeba.com/documentation/admin-tools-joomla/web-application-firewall.html#waf-configure');
	}

}
