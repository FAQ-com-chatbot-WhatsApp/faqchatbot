<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\View\Schedulinginformation;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Model\SchedulinginformationModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	/**
	 * Info about scheduling
	 *
	 * @var  object
	 */
	public $croninfo;

	public function display($tpl = null)
	{
		/** @var SchedulinginformationModel $model */
		$model = $this->getModel();

		// Get the CRON paths
		$this->croninfo = $model->getPaths();

		ToolbarHelper::title(sprintf(Text::_('COM_ADMINTOOLS_TITLE_SCHEDULINGINFORMATION')), 'icon-admintools');
		ToolbarHelper::back('COM_ADMINTOOLS_TITLE_CONTROLPANEL', 'index.php?option=com_admintools');
		ToolbarHelper::preferences('com_admintools');

		ToolbarHelper::help(null, false, 'https://www.akeeba.com/documentation/admin-tools-joomla/php-file-scanner-joomlascheduled.html');

		parent::display($tpl);
	}
}