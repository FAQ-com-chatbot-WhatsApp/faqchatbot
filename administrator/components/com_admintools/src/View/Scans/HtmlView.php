<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\View\Scans;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Mixin\ViewListLimitFixTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewTableUITrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AdminTools\Administrator\Model\ScansModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

class HtmlView extends BaseHtmlView
{
	use ViewLoadAnyTemplateTrait;
	use ViewTaskBasedEventsTrait;
	use ViewTableUITrait;
	use ViewListLimitFixTrait;
	use ViewToolbarTrait;

	/**
	 * The search tools form
	 *
	 * @var    Form
	 * @since  7.0.0
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var    array
	 * @since  7.0.0
	 */
	public $activeFilters = [];

	/**
	 * An array of items
	 *
	 * @var    array
	 * @since  7.0.0
	 */
	protected $items = [];

	/**
	 * The pagination object
	 *
	 * @var    Pagination
	 * @since  7.0.0
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var    Registry
	 * @since  7.0.0
	 */
	protected $state;

	/**
	 * Is this view an Empty State
	 *
	 * @var   boolean
	 * @since 7.0.0
	 */
	private $isEmptyState = false;

	public function display($tpl = null)
	{
		/** @var ScansModel $model */
		$model               = $this->getModel();
		$this->fixListLimitPastTotal($model);
		$this->items         = $model->getItems();
		$this->pagination    = $model->getPagination();
		$this->state         = $model->getState();
		$this->filterForm    = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();

		// Check for errors.
		if (method_exists($this->getModel(), 'getErrors'))
		{
			/** @noinspection PhpDeprecationInspection */
			$errors = $this->getModel()->getErrors();

			if (is_countable($errors) && count($errors))
			{
				throw new GenericDataException(implode("\n", $errors), 500);
			}
		}

		if (!\count($this->items) && $this->isEmptyState = $this->getModel()->getIsEmptyState())
		{
			$this->setLayout('emptystate');
		}

		$msg      = Text::_('COM_ADMINTOOLS_SCAN_LBL_MSG_LASTSERVERRESPONSE');
		$urlStart = Route::_('index.php?option=com_admintools&view=Scans&task=startscan&format=raw', false);
		$urlStep  = Route::_('index.php?option=com_admintools&view=Scans&task=stepscan&format=raw', false);
		$urlBack  = Route::_('index.php?option=com_admintools&view=Scans', false);

		$this->getDocument()->addScriptOptions('admintools.Scan.lastResponseMessage', $msg);
		$this->getDocument()->addScriptOptions('admintools.Scan.urlStart', $urlStart);
		$this->getDocument()->addScriptOptions('admintools.Scan.urlStep', $urlStep);
		$this->getDocument()->addScriptOptions('admintools.Scan.urlBack', $urlBack);

		$this->getDocument()->getWebAssetManager()
			->useScript('com_admintools.scan');

		$this->addToolbar();

		parent::display($tpl);
	}

	private function addToolbar()
	{
		$user = Factory::getApplication()->getIdentity();

		// Get the toolbar object instance
		$toolbar = $this->getToolbarCompat();

		ToolbarHelper::title(sprintf(Text::_('COM_ADMINTOOLS_TITLE_SCANS')), 'icon-admintools');

		$canScan   = $user->authorise('core.manage', 'com_admintools');
		$canDelete = $user->authorise('core.delete', 'com_admintools');

		if ($canScan)
		{
			$toolbar
				->link('COM_ADMINTOOLS_SCAN_LBL_MSG_SCANNOW', '#')
				->icon('fa fa-play')
				->name('startScan');

			$toolbar
				->link('COM_ADMINTOOLS_SCAN_LBL_MSG_PURGE', Route::_('index.php?option=com_admintools&view=Scans&task=purge'))
				->icon('fa fa-trash')
				->name('startScan');
		}

		if ($canDelete)
		{
			$toolbar
				->delete('scans.delete')
				->message('JGLOBAL_CONFIRM_DELETE')
				->listCheck(true);
		}

		ToolbarHelper::preferences('com_admintools');
		ToolbarHelper::back('COM_ADMINTOOLS_TITLE_CONTROLPANEL', 'index.php?option=com_admintools');

		ToolbarHelper::help(null, false, 'https://www.akeeba.com/documentation/admin-tools-joomla/php-file-scanner-reports.html');
	}

}
