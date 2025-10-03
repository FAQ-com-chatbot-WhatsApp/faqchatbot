<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\View\Exportimport;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	use ViewTaskBasedEventsTrait;

	public function onBeforeExport($tpl = null)
	{
		$this->setLayout('export');

		$this->addToolbar();

		ToolbarHelper::apply('doexport', Text::_('COM_ADMINTOOLS_TITLE_EXPORT_SETTINGS'));
	}

	public function onBeforeImport($tpl = null)
	{
		$this->setLayout('import');

		$this->addToolbar();

		ToolbarHelper::apply('doimport', Text::_('COM_ADMINTOOLS_TITLE_IMPORT_SETTINGS'));
	}

	private function addToolbar(): void
	{
		ToolbarHelper::title(Text::_('COM_ADMINTOOLS_TITLE_EXPORTIMPORT'), 'admintools');
		ToolbarHelper::back('COM_ADMINTOOLS_TITLE_CONTROLPANEL', 'index.php?option=com_admintools');

		ToolbarHelper::help(null, false, 'https://www.akeeba.com/documentation/admin-tools-joomla/import-export-settings.html');
	}
}