<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\View\Autobannedaddresses;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Mixin\ViewListLimitFixTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewTableUITrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AdminTools\Administrator\Model\AutobannedaddressesModel;
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
		/** @var AutobannedaddressesModel $model */
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

		$this->addToolbar();

		parent::display($tpl);
	}

	private function addToolbar()
	{
		$user = Factory::getApplication()->getIdentity();

		// Get the toolbar object instance
		$toolbar = $this->getToolbarCompat();

		ToolbarHelper::title(sprintf(Text::_('COM_ADMINTOOLS_TITLE_AUTOBANNEDADDRESSES')), 'icon-admintools');

		$canDelete    = $user->authorise('core.delete', 'com_admintools');

		if ($canDelete)
		{
			$toolbar->delete('autobannedaddresses.delete')
				->message('JGLOBAL_CONFIRM_DELETE')
				->listCheck(true);
		}

		ToolbarHelper::back('JTOOLBAR_BACK', Route::_('index.php?option=com_admintools&view=Webapplicationfirewall', false));

		ToolbarHelper::help(null, false, 'https://www.akeeba.com/documentation/admin-tools-joomla/waf-autoipban.html');

	}
}
