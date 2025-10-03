<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Akeeba\Plugin\System\AdminTools\Feature\Mixin\SuperUsersTrait;
use Akeeba\Plugin\System\AdminTools\Utility\RescueUrl;
use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;

/**
 * Monitors com_config changes and emails the user
 */
class ConfigurationMonitoring extends Base
{
	use SuperUsersTrait;

	/**
	 * Which action should I take when a change is detected? 'email' for sending a warning email, 'block' for treating
	 * the request as a security exception.
	 *
	 * @var   string
	 */
	private $action = 'email';

	/**
	 * Should we monitor changes to Component Configuration?
	 *
	 * @var   bool
	 */
	private $enabledComponents = false;

	/**
	 * Should we monitor changes to Global Configuration?
	 *
	 * @var   bool
	 */
	private $enabledGlobal = false;

	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		$this->enabledGlobal     = $this->wafParams->getValue('configmonitor_global', 0) == 1;
		$this->enabledComponents = $this->wafParams->getValue('configmonitor_components', 0) == 1;
		$this->action            = $this->wafParams->getValue('configmonitor_action', 'email');

		return $this->enabledGlobal || $this->enabledComponents;
	}

	/**
	 * Disables creating new admins or updating new ones
	 */
	public function onAfterInitialise(): void
	{
		$input  = $this->input;
		$option = $input->getCmd('option', '');
		$task   = $input->getCmd('task', '');

		if ($option != 'com_config')
		{
			return;
		}

		$block = false;

		if ($this->enabledGlobal)
		{
			$block |= in_array(
				$task, [
					'config.save.application.apply',
					'config.save.application.save',
					'application.apply',
					'application.save',
				]
			);
		}

		if ($this->enabledComponents)
		{
			$block |= in_array(
				$task,
				['config.save.component.apply', 'config.save.component.save', 'component.apply', 'component.save']
			);
		}

		if (!$block)
		{
			return;
		}

		// Get the correct reason (is this Global Configuration or component configuration)?
		$id            = $input->getInt('id', 0);
		$component     = $input->getCmd('component', '');
		$componentName = $this->getComponentName($id, $component);

		// Default reason for blocking / reporting: Global Configuration
		$jlang = $this->app->getLanguage();
		$jlang->load('com_cpanel', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('com_cpanel', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('com_cpanel', JPATH_ADMINISTRATOR, null, true);
		$extraInfo = Text::_('COM_CPANEL_LINK_GLOBAL_CONFIG');

		// Missing language string? Let's try with another one
		if ($extraInfo == 'COM_CPANEL_LINK_GLOBAL_CONFIG')
		{
			$jlang->load('com_config', JPATH_ADMINISTRATOR, 'en-GB', true);
			$jlang->load('com_config', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
			$jlang->load('com_config', JPATH_ADMINISTRATOR, null, true);
			$extraInfo = Text::_('COM_CONFIG_GLOBAL_CONFIGURATION');
		}

		// If, however, there is a component we need to report extension configuration monitor as the reason
		if (!empty($componentName))
		{
			$jlang = $this->app->getLanguage();
			$jlang->load($componentName . '.sys', JPATH_ADMINISTRATOR, 'en-GB', true);
			$jlang->load($componentName . '.sys', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
			$jlang->load($componentName . '.sys', JPATH_ADMINISTRATOR, null, true);

			// Now set the extra information
			$extraInfo = Text::_($componentName);
		}

		// If we are set to block requests hook into Admin Tools' log and block system
		if ($this->action == 'block')
		{
			$this->exceptionsHandler->blockRequest('configmonitor', null, null, $extraInfo);

			return;
		}

		// Otherwise we need to send an email
		$this->sendEmail($extraInfo);
	}

	/**
	 * Get the component name based either on the extension ID or (preferably) the component name from the request.
	 *
	 * @param   int     $id         An extension ID passed in the request. Must belong to a component.
	 * @param   string  $component  A component name passed in the request.
	 *
	 * @return  string  The component name, or an empty string if there is no corresponding component.
	 */
	private function getComponentName($id, $component)
	{
		$component = trim(strtolower($component));

		// We have a component name
		if (!empty($component))
		{
			return $component;
		}

		// We don't have a component name or ID. Nothing to do
		if (empty($id))
		{
			return '';
		}

		// We have an ID. Try to get the component name from the #__extensions table.
		$db            = $this->db;
		$query         = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->qn('element'))
			->from($db->qn('#__extensions'))
			->where($db->qn('extension_id') . ' = ' . $db->q((int) $id))
			->where($db->qn('type') . ' = ' . $db->q('component'));
		$componentName = $db->setQuery($query)->loadResult();

		if (empty($componentName))
		{
			return '';
		}

		return $componentName;
	}

	/**
	 * Sends a warning email to the addresses set up to receive security exception emails
	 *
	 * @param   string  $configArea  The human readable name of the configuration area being edited
	 */
	private function sendEmail($configArea)
	{
		// Load the component's administrator translation files
		$jlang = $this->app->getLanguage();
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, null, true);

		// Construct the replacement table
		$substitutions = [
			'AREA'   => $configArea,
			'REASON' => Text::_('COM_ADMINTOOLS_WAFEMAILTEMPLATE_REASON_ADMINLOGINFAIL'),
		];

		try
		{
			/**
			 * The email recipients are taken from one of the following sources:
			 *
			 * - Email this address on monitored configuration changes (`configmonitor_email`).
			 * - Email this address after an automatic IP ban (`emailafteripautoban`).
			 * - Super Users which are not Blocked, and have Receive Email enabled.
			 *
			 * The first source to return non-zero items wins.
			 */
			$recipients = $this->userListFromConfiguredEmailList('configmonitor_email')
				?: $this->userListFromConfiguredEmailList('emailafteripautoban')
					?: array_filter($this->getSuperUserObjects(), fn(User $user) => $user->sendEmail);

			foreach ($recipients as $recipient)
			{
				if (empty($recipient) || !$recipient instanceof User)
				{
					continue;
				}

				$data = array_merge(RescueUrl::getRescueInformation($recipient->email), $substitutions);

				$this->exceptionsHandler->sendEmail('com_admintools.configmonitor', $recipient, $data);
			}
		}
		catch (Exception $e)
		{
			// Joomla! 3.5 and later throw an exception when crap happens instead of suppressing it and returning false.
		}
	}
}
