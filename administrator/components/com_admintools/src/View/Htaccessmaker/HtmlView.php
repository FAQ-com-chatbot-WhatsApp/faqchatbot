<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\View\Htaccessmaker;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AdminTools\Administrator\Model\ServerconfigmakerModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	use ViewTaskBasedEventsTrait;
	use ViewLoadAnyTemplateTrait;
	use ViewToolbarTrait;

	/**
	 * Server configuration file contents for preview
	 *
	 * @var  string
	 */
	public $configFile;

	/**
	 * Is this supported? 0 No, 1 Yes, 2 Maybe
	 *
	 * @var  int
	 */
	public $isSupported;

	/**
	 * Should I enable www and non-www redirects, based on the value of $live_site?
	 *
	 * @var bool
	 */
	public $enableRedirects;

	/**
	 * The Joomla form used to generate the controls
	 *
	 * @var Form
	 */
	public $form;

    /**
     * Any PHP handler directives inside the server configuration file.
     *
     * @var string|null
     */
    public ?string $handlers = null;

	protected function onBeforePreview()
	{
		/** @var ServerconfigmakerModel $model */
		$model            = $this->getModel();
		$this->configFile = $model->makeConfigFile();
		$this->setLayout('plain');
	}

	protected function onBeforeMain()
	{
		$this->addToolbar();

		/** @var ServerconfigmakerModel $model */
		$model                 = $this->getModel();
		$this->form            = $model->getForm();
		$this->isSupported     = $model->isSupported();
		$this->enableRedirects = $model->enableRedirects();
        $this->handlers        = $model->getPhpHandlers();

        // If we have PHP handlers inside the .htaccess file BUT we do have stored them inside the Custom Rules field,
        // do not display any notice to the user
        $data = $model->loadConfiguration();

        if ($model->extractHandler($data['custfoot'] ?? '') === $this->handlers)
        {
            $this->handlers = null;
        }
	}

	protected function addToolbar()
	{
		$view = $this->getName();

		ToolbarHelper::title(Text::_('COM_ADMINTOOLS_TITLE_' . $view), 'admintools');

		$bar = $this->getToolbarCompat();

		$saveGroup = $bar->dropdownButton('save-group');
		$childBar  = $saveGroup->getChildToolbar();

		$childBar->apply('apply', 'COM_ADMINTOOLS_' . $view . '_LBL_APPLY');
		$childBar->save('save', 'COM_ADMINTOOLS_' . $view . '_LBL_SAVE');
		$childBar->popupButton('preview', 'Preview', 'preview')
			->icon('fa fa-file-code')
			->url(Route::_('index.php?option=com_admintools&view=' . $view . '&task=preview&tmpl=component'))
			->bodyHeight(380)
			->modalWidth(600);

		$bar->confirmButton('reset', 'COM_ADMINTOOLS_' . $view . '_LBL_RESET', 'reset')
			->icon('fa fa-bolt')
			->buttonClass('btn btn-danger')
			->message('COM_ADMINTOOLS_LBL_SERVERTECH_RESET_CONFIRM');


		ToolbarHelper::back('JTOOLBAR_BACK', Route::_('index.php?option=com_admintools'));

		ToolbarHelper::inlinehelp();

		ToolbarHelper::help(null, false, 'https://www.akeeba.com/documentation/admin-tools-joomla/htaccess-maker.html');
	}
}
