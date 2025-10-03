<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\View\Webconfigmaker;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\View\Htaccessmaker\HtmlView as HtaccessmakerHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends HtaccessmakerHtmlView
{
	public function onBeforePreview()
	{
		// Required otherwise our anti-tamper protection won't get the correct class
		parent::onBeforePreview();

		ToolbarHelper::help(null, false, 'https://www.akeeba.com/documentation/admin-tools-joomla/webconfig-maker.html');
	}
}