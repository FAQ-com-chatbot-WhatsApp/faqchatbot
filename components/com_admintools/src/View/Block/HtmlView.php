<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Site\View\Block;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Helper\Storage;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
	protected $message;

	public function display($tpl = null)
	{
		$app           = Factory::getApplication();
		$message       = $app->getSession()->get('com_admintools.message', null) ?:
			(Storage::getInstance()->getValue('custom403msg', '') ?: 'PLG_ADMINTOOLS_MSG_BLOCKED');
		$message       = Text::_($message);
		$this->message = ($message == 'PLG_ADMINTOOLS_MSG_BLOCKED') ? 'Access Denied' : $message;

		echo $this->loadTemplate($tpl);
	}
}