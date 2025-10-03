<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\View\Quickstart;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Helper\ServerTechnology;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AdminTools\Administrator\Model\ConfigurewafModel;
use Akeeba\Component\AdminTools\Administrator\Model\ControlpanelModel;
use Akeeba\Component\AdminTools\Administrator\Model\QuickstartModel;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

class HtmlView extends BaseHtmlView
{
	use ViewTaskBasedEventsTrait;
	use ViewLoadAnyTemplateTrait;

	/**
	 * The detected IP of the current visitor
	 *
	 * @var  string
	 */
	public $myIp = '';

	/**
	 * The configuration of WAF
	 *
	 * @var  array
	 */
	public $wafconfig = null;

	/**
	 * Is this the first run of the Quick Setup wizard, i.e. no existing configuration was detected?
	 *
	 * @var  bool
	 */
	public $isFirstRun = true;

	/**
	 * Username for the Password Protect Administrator Directory feature
	 *
	 * @var  string
	 */
	public $admin_username;

	/**
	 * Password for the Password Protect Administrator Directory feature
	 *
	 * @var  string
	 */
	public $admin_password;

	/** @var  bool   Does the server technology seem to support .htaccess files? */
	public $hasHtaccess = false;

	protected function onBeforeMain()
	{
		// Get the reported IP
		/** @var ControlpanelModel $cpanelModel */
		$cpanelModel = $this->getModel('Controlpanel');
		$this->myIp  = $cpanelModel->getVisitorIP();

		// Get the WAF configuration
		/** @var ConfigurewafModel $wafConfigModel */
		$wafConfigModel  = $this->getModel('Configurewaf');
		$this->wafconfig = $wafConfigModel->getConfig();

		// Create an admin password if necessary
		if (empty($this->wafconfig['adminpw']))
		{
			// Let's not do that; people skim over this page and get locked out of their site...
			// $this->wafconfig['adminpw'] = $this->genRandomPassword(1, 'abcdefghijklmnopqrstuvwxyz') . $this->genRandomPassword(7);
		}

		// Populate email addresses if necessary
		$currentUser = Factory::getApplication()->getIdentity();

		if (empty($this->wafconfig['emailonadminlogin']))
		{
			$this->wafconfig['emailonadminlogin'] = $currentUser->email;
		}

		if (empty($this->wafconfig['emailbreaches']))
		{
			$this->wafconfig['emailbreaches'] = $currentUser->email;
		}

		if (empty($this->wafconfig['allowed_domains']))
		{
			$this->wafconfig['allowed_domains'] = [strtolower(Uri::getInstance()->getHost())];
		}

		// Get the administrator username/password
		$this->admin_username = '';
		$this->admin_password = '';

		/** @var QuickstartModel $model */
		$model            = $this->getModel();
		$this->isFirstRun = $model->isFirstRun();

		$this->hasHtaccess = ServerTechnology::isHtaccessSupported();

		$this->getDocument()->getWebAssetManager()
			->useScript('com_admintools.quickstart')
			->usePreset('choicesjs')
			->useScript('webcomponent.field-fancy-select');

		// Enable Bootstrap popovers
		HTMLHelper::_('bootstrap.popover', '[rel=popover]', [
			'html'      => true,
			'placement' => 'bottom',
			'trigger'   => 'click hover',
			'sanitize'  => false,
		]);

		Text::script('JNO', true);
		Text::script('JYES', true);

		ToolbarHelper::title(Text::_('COM_ADMINTOOLS_TITLE_QUICKSTART'));
		ToolbarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_admintools');

		ToolbarHelper::help(null, false, 'https://www.akeeba.com/documentation/admin-tools-joomla/quick-setup.html');
	}

	/**
	 * Generate a random password. Forked from Joomla to allow the use of a different salt (characters to use in the
	 * password).
	 *
	 * @param   integer  $length  Length of the password to generate
	 *
	 * @return  string  Random Password
	 */
	protected function genRandomPassword($length = 8, $salt = 'abcdefghijklmnopqrstuvwxyz0123456789')
	{
		$base     = strlen($salt);
		$makepass = '';

		/*
		 * Start with a cryptographic strength random string, then convert it to
		 * a string with the numeric base of the salt.
		 * Shift the base conversion on each character so the character
		 * distribution is even, and randomize the start shift so it's not
		 * predictable.
		 */
		$random = random_bytes($length + 1);
		$shift  = ord($random[0]);

		for ($i = 1; $i <= $length; ++$i)
		{
			$makepass .= $salt[($shift + ord($random[$i])) % $base];
			$shift    += ord($random[$i]);
		}

		return $makepass;
	}

}