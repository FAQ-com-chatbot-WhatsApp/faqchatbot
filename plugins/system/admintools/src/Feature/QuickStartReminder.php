<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Akeeba\Component\AdminTools\Administrator\Helper\Storage;
use Joomla\CMS\Language\Text;

/**
 * Detect if the Quick Start Wizard has ran (or Admin Tools has been manually configured). Otherwise display a message
 * reminding the user to run the wizard.
 */
class QuickStartReminder extends Base
{
	public function onBeforeRender(): void
	{
		if (!$this->app->isClient('administrator'))
		{
			return;
		}

		if ($this->app->getIdentity()->guest)
		{
			return;
		}

		/** @var Storage $storage */
		$storage      = Storage::getInstance();
		$wizardHasRan = $storage->getValue('quickstart', 0);

		if ($wizardHasRan)
		{
			return;
		}

		if (!$this->app->getIdentity()->authorise('core.manage', 'admintools.security'))
		{
			return;
		}

		if (!$this->app->getIdentity()->authorise('core.manage', 'admintools.maintenance'))
		{
			return;
		}

		$jlang = $this->app->getLanguage();
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, 'en-GB');
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, null, true);

		$msg = Text::sprintf('COM_ADMINTOOLS_QUICKSTART_MSG_PLEASERUNWIZARD', 'index.php?option=com_admintools&view=Quickstart');
		$this->app->enqueueMessage($msg, 'error');
	}
}
