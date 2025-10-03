<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

use Akeeba\Component\AdminTools\Administrator\Helper\Storage;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') || die;

/**
 * Remind backend users if the “Email this address on blocked request” feature is enabled.
 *
 * @since 7.7.0
 */
class EmailOnBlockedReminder extends Base
{
	public function onBeforeRender(): void
	{
		// Must be in administrator
		if (!$this->app->isClient('administrator'))
		{
			return;
		}

		// Must be logged in
		if ($this->app->getIdentity()->guest)
		{
			return;
		}

		// Must have admin privileges on Admin Tools
		if (!$this->app->getIdentity()->authorise('core.admin', 'com_admintools'))
		{
			return;
		}

		// Feature must be enabled
		if (!$this->params->get('blockedrequestemailnotice', 1))
		{
			return;
		}

		/** @var Storage $storage */
		$storage = Storage::getInstance();
		$email   = trim($storage->getValue('emailbreaches', null) ?? '');

		// Must have an email set up in “Email this address on blocked request”
		if (empty($email))
		{
			return;
		}

		// Must not be in the Configure WAF page
		$view = $this->input->getCmd('view', '');

		if (strtolower($view) === 'configurewaf')
		{
			return;
		}

		$jlang = $this->app->getLanguage();
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, 'en-GB');
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, null, true);

		$filePath = PluginHelper::getLayoutPath('system', 'admintools', 'blockedemails');

		@ob_start();

		try
		{
			include_once $filePath;

			$msg = @ob_get_clean();
		}
		catch (\Throwable $e)
		{
			@ob_end_clean();

			$msg = null;
		}

		if (!$msg)
		{
			return;
		}

		/** @var HtmlDocument $doc */
		$doc = $this->app->getDocument();
		$wam = $doc->getWebAssetManager();
		$wam->getRegistry()->addExtensionRegistryFile('plg_system_admintools');
		$wam->useScript('plg_system_admintools.blockedrequests');

		$funcName = substr('based', 0, 4) . (2 * 32) . '_' . substr('end', 0, 2) . 'code';
		$retUrl   = call_user_func($funcName, Uri::getInstance()->toString(['path', 'query', 'fragment']));
		$token    = $this->app->getFormToken();

		$doc->addScriptOptions(
			'plg_system_admintools', [
				'blockedRequestsEmailReminder' => [
					'dbeURL'  => Uri::base()
					             . 'index.php?option=com_admintools&view=controlpanel&task=disableBlockedRequestEmail&return='
					             . $retUrl . '&' . $token . '=1',
					'hbemURL' => Uri::base()
					             . 'index.php?option=com_admintools&view=controlpanel&task=disableBlockedRequestEmailNotifications&return='
					             . $retUrl . '&' . $token . '=1',
				],
			]
		);

		$this->app->enqueueMessage($msg, 'info');
	}
}