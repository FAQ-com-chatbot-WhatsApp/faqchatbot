<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Helper\Storage;
use Akeeba\Component\AdminTools\Administrator\Mixin\RunPluginsTrait;
use Exception;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryAwareInterface;
use Joomla\CMS\Form\FormFactoryAwareTrait;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\FormBehaviorTrait;
use Joomla\CMS\MVC\Model\FormModelInterface;
use Joomla\CMS\Plugin\PluginHelper;

#[\AllowDynamicProperties]
class ConfigurewafModel extends BaseDatabaseModel implements FormFactoryAwareInterface, FormModelInterface
{
	use FormBehaviorTrait;
	use FormFactoryAwareTrait;
	use RunPluginsTrait;

	/**
	 * These configuration keys are handled by subforms
	 *
	 * @var   string[]
	 * @since 7.0.0
	 */
	protected $subformConfigKeys = [
		'neverblockips', 'whitelist_domains',
		'blockedemaildomains', 'criticalfiles_global', '404shield', 'allowed_domains',
		'disableobsoleteadmins_protected', 'blockusernames_forbid', 'blockusernames_allow'
	];

	/**
	 * Maps events to plugin groups.
	 *
	 * @var    array
	 * @since  7.0.0
	 */
	protected $events_map = null;

	/**
	 * Default configuration variables
	 *
	 * @var array
	 */
	private $defaultConfig = [
		'ipwl'                            => 0,
		'ipbl'                            => 0,
		'adminpw'                         => '',
		'adminpw_action'                  => 'redirect',
		'nonewadmins'                     => 1,
		'nonewadmins_groups'              => [],
		'nonewfrontendadmins'             => 1,
		'nonewfrontendadmins_groups'      => [],
		'sqlishield'                      => 1,
		'antispam'                        => 0,
		'custgenerator'                   => 0,
		'generator'                       => '',
		'tmpl'                            => 1,
		'template'                        => 1,
		'logbreaches'                     => 1,
		'emailonadminlogin'               => '',
		'emailonfailedadminlogin'         => '',
		'emailbreaches'                   => '',
		'muashield'                       => 1,
		'rfishield'                       => 1,
		'phpshield'                       => 1,
		'dfishield'                       => 1,
		'sessionshield'                   => 1,
		'badbehaviour'                    => 0,
		'bbstrict'                        => 0,
		'bbhttpblkey'                     => '',
		'bbwhitelistip'                   => '',
		'tsrenable'                       => 0,
		'tsrstrikes'                      => 3,
		'tsrnumfreq'                      => 1,
		'tsrfrequency'                    => 'hour',
		'tsrbannum'                       => 1,
		'tsrbanfrequency'                 => 'day',
		'spammermessage'                  => 'We have detected suspicious activity from your IP address. Your access to this site is temporarily suspended.',
		'nofesalogin'                     => 0,
		'tmplwhitelist'                   => ['component', 'system', 'raw', 'koowa', 'cartupdate'],
		'neverblockips'                   => [
			'20.191.45.212', '23.21.227.69', '40.88.21.235', '50.16.241.113', '50.16.241.114', '50.16.241.117',
			'50.16.247.234', '52.5.190.19', '52.204.97.54', '54.197.234.188', '54.208.100.253', '54.208.102.37',
			'107.21.1.8',
		],
		'emailafteripautoban'             => '',
		'custom403msg'                    => '',
		'httpblenable'                    => 0,
		'httpblthreshold'                 => 25,
		'httpblmaxage'                    => 30,
		'httpblblocksuspicious'           => 0,
		'allowsitetemplate'               => 0,
		'trackfailedlogins'               => 1,
		'use403view'                      => 0,
		'iplookup'                        => 'whatismyipaddress.com/ip/{ip}',
		'iplookupscheme'                  => 'https',
		'saveusersignupip'                => 0,
		'twofactorauth'                   => 0,
		'twofactorauth_secret'            => '',
		'twofactorauth_panic'             => '',
		'whitelist_domains'               => [
			'.crawl.baidu.com', '.crawl.baidu.jp', '.google.com', '.googlebot.com', '.search.msn.com',
			'.crawl.yahoo.net', '.yandex.ru', '.yandex.net', '.yandex.com',
		],
		'reasons_nolog'                   => ['ipbl'],
		'reasons_noemail'                 => ['ipbl'],
		'resetjoomlatfa'                  => 0,
		'email_throttle'                  => 1,
		'permaban'                        => 0,
		'permabannum'                     => 0,
		'deactivateusers_num'             => 0,
		'deactivateusers_numfreq'         => 1,
		'deactivateusers_frequency'       => 'day',
		'awayschedule_from'               => '',
		'awayschedule_to'                 => '',
		'adminlogindir'                   => '',
		// PLEASE NOTE: Previously this field was used only to BLOCK email domains,
		// but now is used to hold the list of blocked OR allowed domains.
		'blockedemaildomains'             => [],
		'configmonitor_global'            => 1,
		'configmonitor_components'        => 1,
		'configmonitor_action'            => 'email',
		'selfprotect'                     => 1,
		'criticalfiles'                   => 0,
		'criticalfiles_global'            => [],
		'superuserslist'                  => 0,
		'consolewarn'                     => 1,
		'404shield_enable'                => 1,
		'404shield'                       => ["wp-admin.php", "wp-login.php", "wp-content/*", "wp-admin/*"],
		'emailphpexceptions'              => '',
		'logfile'                         => 0,
		'filteremailregistration'         => 'block',
		'leakedpwd'                       => 0,
		'leakedpwd_groups'                => [],
		'disableobsoleteadmins'           => 0,
		'disableobsoleteadmins_freq'      => 60,
		'disableobsoleteadmins_groups'    => [],
		'disableobsoleteadmins_maxdays'   => 90,
		'disableobsoleteadmins_action'    => 'reset',
		'disableobsoleteadmins_protected' => [],
		'logusernames'                    => 0,
		'troubleshooteremail'             => 1,
		'allowed_domains'                 => [],
		'nopwonwebauthn'                  => 0,
		'disablepwdreset'                 => 0,
		'disablepwdreset_groups'          => [],
		'itemidshield'                    => 2,
		'suspicious_params'               => 1,
		'blockusernames'                  => 0,
		'blockusernames_forbid'           => [],
		'blockusernames_allow'            => [],
	];

	public function __construct($config = [], MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
	{
		$config['events_map'] = $config['events_map'] ?? [];

		$this->events_map = array_merge(
			['validate' => 'content'],
			$config['events_map']
		);

		parent::__construct($config, $factory);

		$this->setFormFactory($formFactory);
	}

	/**
	 * Returns the list of configuration keys that are handled by a subform (ie they have array values)
	 *
	 * @return string[]
	 */
	public function getSubformConfigKeys(): array
	{
		return $this->subformConfigKeys;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   Form         $form   The form to validate against.
	 * @param   array        $data   The data to validate.
	 * @param   string|null  $group  The name of the field group to validate.
	 *
	 * @return  array|null  Array of filtered data if valid, null otherwise.
	 *
	 * @throws  Exception
	 * @see     FormRule
	 * @see     InputFilter
	 * @since   7.0.0
	 */
	public function validate(Form $form, array $data, ?string $group = null): ?array
	{
		// Include the plugins for the delete events.
		PluginHelper::importPlugin($this->events_map['validate']);

		foreach ($this->getSubformConfigKeys() as $key)
		{
			if (!isset($data[$key]))
			{
				continue;
			}

			if (!is_array($data[$key]))
			{
				$data[$key] = [];

				continue;
			}

			$data[$key] = array_filter(
				$data[$key],
				fn($row) => !empty($row['item'] ?? null)
			);
		}

		$this->triggerPluginEvent('onContentBeforeValidateData', [$form, &$data]);

		// Filter and validate the form data.
		$data   = $form->filter($data);
		$return = $form->validate($data, $group);

		// Check for an error.
		if ($return instanceof Exception)
		{
			if (!method_exists($this, 'setError'))
			{
				throw new \RuntimeException($return->getMessage());
			}

			/** @noinspection PhpDeprecationInspection only called when deprecated code is not removed */
			$this->setError($return->getMessage());

			return null;
		}

		// Check the validation results.
		if ($return === false)
		{
			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				if (!method_exists($this, 'setError'))
				{
					throw new \RuntimeException($message);
				}

				/** @noinspection PhpDeprecationInspection only called when deprecated code is not removed */
				$this->setError($message);
			}

			return null;
		}

		return $data;
	}

	/**
	 * @inheritDoc
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_admintools.' . $this->getName(),
			$this->getName(),
			[
				'control'   => false,
				'load_data' => $loadData,
			]
		) ?: false;

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Get the default Web Application Firewall configuration
	 *
	 * @return array
	 */
	public function getDefaultConfig(): array
	{
		if (!defined('JPATH_COMPONENT'))
		{
			define('JPATH_COMPONENT', JPATH_ADMINISTRATOR . '/components/com_admintools');
		}

		Form::addFormPath(JPATH_ADMINISTRATOR . "/components/com_admintools/forms");
		Form::addFormPath(JPATH_ADMINISTRATOR . "/components/com_admintools/models/forms");
		Form::addFieldPath(JPATH_ADMINISTRATOR . "/components/com_admintools/models/fields");
		Form::addFormPath(JPATH_ADMINISTRATOR . "/components/com_admintools/model/form");
		Form::addFieldPath(JPATH_ADMINISTRATOR . "/components/com_admintools/model/field");

		$form = $this->loadForm(
			'com_admintools.' . $this->getName(),
			$this->getName(),
			[
				'control'   => false,
				'load_data' => false,
			]
		) ?: false;

		if (empty($form))
		{
			return $this->defaultConfig;
		}

		$defaultConfig = [];

		foreach ($form->getFieldsets() as $fieldsetName => $fieldsetMeta)
		{
			foreach ($form->getFieldset($fieldsetName) as $fieldName => $field)
			{
				$defaultValue = $form->getFieldAttribute($fieldName, 'default', null)
					?? $form->getFieldAttribute($fieldName, 'admintools_default', '');

				if (in_array($fieldName, $this->getSubformConfigKeys()) || is_array($this->defaultConfig[$fieldName] ?? ''))
				{
					$defaultValue = explode(',', $defaultValue);
					$defaultValue = array_map('trim', $defaultValue);
					$defaultValue = array_filter($defaultValue, fn($x) => !empty($x));
				}

				$defaultConfig[$fieldName] = $defaultValue;
			}
		}

		return array_merge($this->defaultConfig, $defaultConfig);
	}

	/**
	 * Load the WAF configuration
	 *
	 * @return  array
	 */
	public function getConfig()
	{
		$params = Storage::getInstance();

		$config        = [];
		$defaultConfig = $this->getDefaultConfig();

		foreach ($defaultConfig as $k => $v)
		{
			$config[$k] = $params->getValue($k, $v);
		}

		// Migrate old imploded-style and one-per-line fields to PHP array-style
		foreach (array_merge($this->getSubformConfigKeys(), [
			'tmplwhitelist', 'reasons_nolog', 'reasons_noemail',
		]) as $key)
		{
			$config[$key] = $config[$key] ?? [];

			if (is_object($config[$key]))
			{
				$config[$key] = (array)($config[$key]);
			}

			if (is_array($config[$key]))
			{
				continue;
			}

			// Trim the value before doing anything else
			$value = trim($config[$key]);
			// Some browsers returned the wrong line endings for strings. Let's fix that.
			$value = str_replace(["\r\n", "\r"], ["\n", "\n"], $value);
			// If we have \n line endings it's a one-per-line style value. Otherwise it's comma separated.
			if (strpos($value, "\n") !== false)
			{
				$value = explode("\n", $value);
			}
			else
			{
				$value = explode(",", $value);
			}
			// Trim values and filter out empty and/or duplicate values
			$value        = array_map('trim', $value);
			$value        = array_filter($value, function ($x) {
				return !empty($x);
			});
			$config[$key] = array_unique($value);
		}

		// Make sure the IP lookup service is set up
		$this->migrateIplookup($config);

		// Make sure there's a non–empty collection of user groups to check for comrpomised passwords
		$config['leakedpwd_groups'] = $this->getSuperUserGroupsToCheck($config);

		return $config;
	}

	public function convertFormDataToDatabaseData($data)
	{
		/**
		 * Subforms give us data in the format:
		 * ['__field1' => ['item' => 'directory1'], '__field2' => ['item' => 'directory2']]
		 * I need to convert to the format:
		 * ['directory1', 'directory2']
		 */
		foreach ($this->getSubformConfigKeys() as $key)
		{
			$value = array_map(function ($subformFields) {
				$value       = $subformFields['item'] ?? null;
				$description = $subformFields['description'] ?? '';

				return [$value, $description];
			}, ($data[$key] ?? []) ?: []);

			$data[$key] = array_values(array_filter($value, function ($x) {
				return is_array($x) ? !empty($x[0] ?? null) : !empty($x);
			}));
		}

		return $data;
	}

	/**
	 * Converts data in a database format (['value1', 'value2']) to a format accepted by Joomla Form object
	 * ['__field1' => ['item' => 'value1'], '__field2' => ['item' => 'value2']]
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public function convertDatabaseDataToFormData($data): array
	{
		$result = [];
		$i      = 1;

		foreach ($data as $item)
		{
			$result['__field'.$i] = (is_array($item)
				? [
					'item'        => $item[0],
					'description' => $item[1],
				]
				: [
					'item' => $item,
					'description' => '',
				]);

			$i++;
		}

		return $result;
	}

	/**
	 * Merge and save $newParams into the WAF configuration
	 *
	 * @param   array  $data  New parameters to save
	 *
	 * @return  void
	 */
	public function saveConfig(array $data)
	{
		// Apply migrations
		$this->migrateIplookup($data);

		$defaultConfig   = $this->getDefaultConfig();
		$knownConfigKeys = array_keys($defaultConfig);
		$data            = array_filter($data, function ($key) use ($knownConfigKeys) {
			return in_array($key, $knownConfigKeys);
		}, ARRAY_FILTER_USE_KEY);

		// Joomla does not send us multiple selection fields if they are empty. So, I have to fake them.
		foreach ($defaultConfig as $k => $v)
		{
			$data[$k] = $data[$k] ?? (is_array($v) ? [] : '');
		}

		$data   = array_merge($defaultConfig, $data);
		$params = Storage::getInstance();

		foreach ($data as $key => $value)
		{
			// Sanity check for Away Schedule time format
			if (($key == 'awayschedule_from') || ($key == 'awayschedule_to'))
			{
				if (!preg_match('#^([0-1]?[0-9]|[2][0-3]):([0-5][0-9])$#', $value))
				{
					$value = '';
				}
			}

			$params->setValue($key, $value);
		}

		// Mark Admin Tools configured when we save the WAF configuration (so we don't prompt for Quick Start any more).
		$params->setValue('quickstart', 1);

		$params->save();

		/**
		 * Special case: when superuserslist is set to 0 we need to remove the saved Super User IDs from the
		 * #__admintools_storage table
		 */
		if ($params->getValue('superuserslist', 0) == 0)
		{
			$db    = $this->getDatabase();
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->delete($db->quoteName('#__admintools_storage'))
				->where($db->quoteName('key') . ' = ' . $db->quote('superuserslist'));
			$db->setQuery($query);
			$db->execute();
		}

		/** @var CMSApplication $app */
		$app = Factory::getApplication();

		if (method_exists($app, 'setUserState'))
		{
			$app->setUserState('com_admintools.' . $this->getName() . '.data', null);
		}
	}

	protected function loadFormData()
	{
		/** @var CMSApplication $app */
		$app  = Factory::getApplication();
		$data = $this->getConfig();

		if (method_exists($app, 'getUserState'))
		{
			$data = $app->getUserState('com_admintools.' . $this->getName() . '.data', $data);
		}

		$this->preprocessData('com_admintools.' . $this->getName(), $data);

		return $data;
	}

	protected function preprocessData($context, &$data, $group = 'content')
	{
		if (is_object($data))
		{
			$data = (array) $data;
		}

		foreach ($this->getSubformConfigKeys() as $key)
		{
			$data[$key] = array_values($this->convertDatabaseDataToFormData($data[$key]));
		}

		foreach ($this->getDefaultConfig() as $k => $v)
		{
			$data[$k] = $data[$k] ?? (is_array($v) ? [] : '');
		}

		// Get the dispatcher and load the users plugins.
		PluginHelper::importPlugin($group);

		// Trigger the data preparation event.
		$this->triggerPluginEvent('onContentPrepareData', [$context, &$data]);
	}

	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		/**
		 * =============================================================================================================
		 * Pre-process “Deactivate users on failed login”
		 * =============================================================================================================
		 */
		// Detect user registration and activation type
		$userParams = ComponentHelper::getParams('com_users');

		// User registration disabled
		if (!$userParams->get('allowUserRegistration'))
		{
			$disabled = true;
			$class    = 'alert-error alert w-100';
			$message  = 'COM_ADMINTOOLS_CONFIGUREWAF_LBL_ALERT_NOREGISTRATION';
		}
		// Super User user activation
		elseif ($userParams->get('useractivation') == 2)
		{
			$disabled = false;
			$class    = 'alert-warning alert w-100';
			$message  = 'COM_ADMINTOOLS_CONFIGUREWAF_LBL_ALERT_ADMINACTIVATION';
		}
		// No user activation
		elseif ($userParams->get('useractivation') == 0)
		{
			$disabled = true;
			$class    = 'alert-error alert w-100';
			$message  = 'COM_ADMINTOOLS_CONFIGUREWAF_LBL_ALERT_NOUSERACTIVATION';
		}
		else
		{
			$disabled = false;
			$class    = 'alert-info alert w-100';
			$message  = 'COM_ADMINTOOLS_CONFIGUREWAF_LBL_ALERT_WORKS';
		}

		if ($disabled)
		{
			$form->setFieldAttribute('deactivateusers_num', 'disabled', 'true');
			$form->setFieldAttribute('deactivateusers_num', 'value', '0');
			$form->setFieldAttribute('deactivateusers_numfreq', 'disabled', 'true');
			$form->setFieldAttribute('deactivateusers_frequency', 'disabled', 'true');
		}

		$form->setFieldAttribute('deactivateusers_footer', 'description', $message);
		$form->setFieldAttribute('deactivateusers_footer', 'class', $class);

		// Unset the `default` attribute for all form fields. This allows displaying empty fields as such.
		foreach ($form->getFieldsets() as $fieldsetName => $fieldsetMeta)
		{
			foreach ($form->getFieldset($fieldsetName) as $fieldName => $field)
			{
				$form->setFieldAttribute($fieldName, 'admintools_default', $form->getFieldAttribute($fieldName, 'default', null));
				$form->setFieldAttribute($fieldName, 'default', null);
			}
		}

		/**
		 * =============================================================================================================
		 * Use plugin events for form preparation
		 * =============================================================================================================
		 */
		// Import the appropriate plugin group.
		PluginHelper::importPlugin($group);

		// Trigger the form preparation event.
		$this->triggerPluginEvent('onContentPrepareForm', [$form, $data]);
	}


	/**
	 * Used to transparently set the IP lookup service to a sane default when none is specified
	 *
	 * @param   array  $data  The configuration data we'll modify
	 *
	 * @return  void
	 */
	private function migrateIplookup(&$data)
	{
		$iplookup       = $data['iplookup'];
		$iplookupscheme = $data['iplookupscheme'];

		if (empty($iplookup))
		{
			$iplookup       = 'whatismyipaddress.com/ip/{ip}';
			$iplookupscheme = 'https';
		}

		$test = strtolower($iplookup);

		if (substr($test, 0, 7) == 'http://')
		{
			$iplookup       = substr($iplookup, 7);
			$iplookupscheme = 'http';
		}
		elseif (substr($test, 0, 8) == 'https://')
		{
			$iplookup       = substr($iplookup, 8);
			$iplookupscheme = 'https';
		}

		$data['iplookup']       = $iplookup;
		$data['iplookupscheme'] = $iplookupscheme;
	}

	/**
	 * If empty, fills the groups where we should check for leaked passwords
	 *
	 * @param   array  $data  The configuration data already read from the DB
	 */
	private function getSuperUserGroupsToCheck($data)
	{
		// Get the user–configured groups and make sure it is an array
		$groups = $data['leakedpwd_groups'] ?? [];
		$groups = $groups ?: [];
		$groups = is_string($groups) ? explode(',', $groups) : $groups;
		$groups = is_object($groups) ? (array) $groups : $groups;
		$groups = is_array($groups) ? $groups : [];

		// If there are no groups default to the user groups with Super User (core.admin) permissions
		if (empty($groups))
		{
			try
			{
				// Get all groups
				$db     = $this->getDatabase();
				$query  = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
					->select([$db->qn('id')])
					->from($db->qn('#__usergroups'));
				$groups = $db->setQuery($query)->loadColumn(0) ?: [];
				$groups = array_filter($groups, function ($group) {
					return Access::checkGroup($group, 'core.admin');
				});
			}
			catch (\Exception $e)
			{
				$groups = [];
			}
		}

		return $groups;
	}
}