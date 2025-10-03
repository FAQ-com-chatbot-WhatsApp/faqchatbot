<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Controller\ControlpanelController;
use Akeeba\Component\AdminTools\Administrator\Controller\ServerconfigmakerController;
use Akeeba\Component\AdminTools\Administrator\Helper\ServerTechnology;
use Akeeba\Component\AdminTools\Administrator\Helper\Storage;
use Akeeba\Component\AdminTools\Administrator\Mixin\RunPluginsTrait;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryAwareInterface;
use Joomla\CMS\Form\FormFactoryAwareTrait;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\FormBehaviorTrait;
use Joomla\CMS\MVC\Model\FormModelInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

#[\AllowDynamicProperties]
abstract class ServerconfigmakerModel extends BaseDatabaseModel implements FormFactoryAwareInterface, FormModelInterface
{
	use FormBehaviorTrait;
	use FormFactoryAwareTrait;
	use RunPluginsTrait;

	/**
	 * The current configuration of this feature
	 *
	 * @var  object
	 */
	protected $config = null;

	/**
	 * The Admin Tools configuration key under which we'll save $config as a JSON-encoded string
	 *
	 * @var  string
	 */
	protected $configKey = '';

	/**
	 * The methods which are allowed to call the saveConfiguration method. Each line is in the format:
	 * Full\Class\Name::methodName
	 *
	 * @var  array
	 */
	protected $allowedCallersForSave = [];

	/**
	 * The methods which are allowed to call the writeConfigFile method. Each line is in the format:
	 * Full\Class\Name::methodName
	 *
	 * @var  array
	 */
	protected $allowedCallersForWrite = [];

	/**
	 * The methods which are allowed to call the makeConfigFile method. Each line is in the format:
	 * Full\Class\Name::methodName
	 *
	 * @var  array
	 */
	protected $allowedCallersForMake = [];

	/**
	 * These configuration keys are handled by subforms
	 *
	 * @var   string[]
	 * @since 7.0.0
	 */
	protected $subformConfigKeys = [
		'bepexdirs', 'fepexdirs', 'exceptionfiles', 'exceptiondirs', 'fullaccessdirs', 'httpsurls', 'proxy_ips',
	];

	/**
	 * Configuration keys which SHOULD contain arrays but MIGHT contain strings or objects
	 *
	 * @var   array
	 * @since 7.0.11
	 */
	protected $arrayConfigKeys = [
		'hoggeragents', 'httpsurls', 'exceptionfiles', 'exceptiondirs', 'fullaccessdirs',
		'exceptionfiles', 'exceptiondirs', 'fullaccessdirs', 'bepextypes', 'fepextypes',
		'restrictpip_custom',
	];

	/**
	 * Maps events to plugin groups.
	 *
	 * @var    array
	 * @since  7.0.0
	 */
	protected $events_map = null;

	/**
	 * The base name of the configuration file being saved by this feature, e.g. ".htaccess". The file is always saved
	 * in the site's root. Any old files under that name are renamed with a .admintools suffix.
	 *
	 * @var string
	 */
	protected $configFileName = '';

	/**
	 * Stores the version of the server engine.
	 *
	 * @var string
	 */
	protected $serverVersion = '';

	/**
	 * Should I put the config file in the public root?
	 *
	 * On Joomla 5 the public root (where the .htaccess and web.config file is written) may be different to the
	 * Joomla! installation root.
	 *
	 * For the .htaccess and web.config Maker we must always place the generated file in the public root, be it
	 * JPATH_ROOT or JPATH_PUBLIC since the server always looks for the file there.
	 *
	 * For the NginX Conf Maker it makes more sense to always use JPATH_ROOT. The server only looks for this file where
	 * the user explicitly tells it to, therefore it is safer to put in a non-public directory. The NginX Conf Maker
	 * sets this flag to false, communicating that.
	 *
	 * @var   bool
	 * @since 7.4.3
	 */
	protected $usePublicRoot = true;

	public function __construct($config = [], MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
	{
		$config['events_map'] = $config['events_map'] ?? [];

		$this->events_map = array_merge(
			['validate' => 'content'],
			$config['events_map']
		);

		parent::__construct($config, $factory);

		$this->setFormFactory($formFactory);

		// Set up the allowed callers
		$classParts = explode('\\', get_class($this));
		$viewName   = substr(array_pop($classParts), 0, -5);

		$this->allowedCallersForSave = [
			ServerconfigmakerController::class . '::reset',
			ServerconfigmakerController::class . '::saveOrApply',
			'Akeeba\Component\AdminTools\Administrator\Controller\\' . $viewName . 'Controller::reset',
			'Akeeba\Component\AdminTools\Administrator\Controller\\' . $viewName . 'Controller::saveOrApply',
            'Akeeba\Component\AdminTools\Administrator\Model\HtaccessmakerModel::includePhpHandlers',
			QuickstartModel::class . '::applyPreferences',
			QuickstartModel::class . '::applyHtmaker',
			ExportimportModel::class . '::importData',
			'Akeeba\Component\AdminTools\Administrator\CliCommand\ServerConfiguration\ServerConfigurationSet::doExecute',
			'Akeeba\Component\AdminTools\Administrator\CliCommand\ServerConfiguration\ServerConfigurationMake::doExecute',
			ControlpanelModel::class . '::serverConfigUpdateFrontendDirectories',
		];

		$this->allowedCallersForWrite = [
			ServerconfigmakerController::class . '::apply',
			ServerconfigmakerController::class . '::saveOrApply',
			'Akeeba\Component\AdminTools\Administrator\Controller\\' . $viewName . 'Controller::apply',
			'Akeeba\Component\AdminTools\Administrator\Controller\\' . $viewName . 'Controller::saveOrApply',
			ControlpanelController::class . '::regenerateServerConfig',
			QuickstartModel::class . '::applyPreferences',
			QuickstartModel::class . '::applyHtmaker',
			'Akeeba\Component\AdminTools\Administrator\CliCommand\ServerConfiguration\ServerConfigurationSet::doExecute',
			'Akeeba\Component\AdminTools\Administrator\CliCommand\ServerConfiguration\ServerConfigurationMake::doExecute',
			ControlpanelModel::class . '::serverConfigUpdateFrontendDirectories',
		];

		$this->allowedCallersForMake = [
			ServerconfigmakerController::class . '::apply',
			ServerconfigmakerController::class . '::saveOrApply',
			'Akeeba\Component\AdminTools\Administrator\Controller\\' . $viewName . 'Controller::apply',
			'Akeeba\Component\AdminTools\Administrator\Controller\\' . $viewName . 'Controller::saveOrApply',
			ControlpanelController::class . '::regenerateServerConfig',
			ControlpanelModel::class . '::serverConfigEdited',
			self::class . '::writeConfigFile',
			QuickstartModel::class . '::applyPreferences',
			QuickstartModel::class . '::applyHtmaker',
			'Akeeba\Component\AdminTools\Administrator\View\\' . $viewName . '\HtmlView::onBeforeMain',
			'Akeeba\Component\AdminTools\Administrator\View\\' . $viewName . '\HtmlView::onBeforePreview',
			'Akeeba\Component\AdminTools\Administrator\CliCommand\ServerConfiguration\ServerConfigurationSet::doExecute',
			'Akeeba\Component\AdminTools\Administrator\CliCommand\ServerConfiguration\ServerConfigurationMake::doExecute',
			ControlpanelModel::class . '::getServerConfigInformation',
		];
	}

	/**
	 * Return the list of keys that are arrays
	 *
	 * @return array|string[]
	 */
	public function getArrayConfigKeys() : array
	{
		return $this->arrayConfigKeys;
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
	 * Forces the server engine to a specific version
	 *
	 * @param string $version
	 *
	 * @return void
	 */
	public function setServerVersion(string $version)
	{
		$this->serverVersion = $version;
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
	 * Get the configuration file name.
	 *
	 * @param   bool  $absolutePath  Should I return the absolute path to it? Default: false (returns basename only).
	 *
	 * @return  string
	 */
	public function getConfigFileName(bool $absolutePath = false): string
	{
		if ($absolutePath)
		{
			$publicRoot = ($this->usePublicRoot && defined('JPATH_PUBLIC')) ? JPATH_PUBLIC : JPATH_ROOT;

			return $publicRoot . '/' . $this->configFileName;
		}

		return $this->configFileName;
	}

	/**
	 * Loads the feature's configuration from the database
	 *
	 * @param   bool  $withDefaults Should I load the defaults as well?
	 *
	 * @return  object
	 * @throws  Exception
	 */
	public function loadConfiguration(bool $withDefaults = true)
	{
		if (!is_null($this->config))
		{
			return $this->config;
		}

		$params      = Storage::getInstance();
		$savedConfig = $params->getValue($this->configKey, '') ?: '';

		if (function_exists('base64_encode') && function_exists('base64_encode'))
		{
			$savedConfig = @base64_decode($savedConfig) ?: '[]';
		}

		$savedConfig  = @json_decode($savedConfig, true) ?: [];

		if ($withDefaults)
		{
			$this->config = array_merge($this->getDefaultConfig(), $savedConfig ?: []);
		}
		else
		{
			$this->config = $savedConfig ?: [];
		}

		$this->handlePotentialArrayKeys($this->config);

		return $this->config;
	}

	/**
	 * Get the default configuration from the XML form's default value attributes
	 *
	 * @return  array
	 * @throws  Exception
	 * @since   7.0.0
	 */
	public function getDefaultConfig(): array
	{
		$form     = $this->getForm([], false);
		$defaults = [];

		foreach ($form->getFieldsets() as $fieldset)
		{
			foreach ($form->getFieldset($fieldset->name) as $name => $field)
			{
				$value = $field->getAttribute('default');

				if (in_array($name, $this->subformConfigKeys))
				{
					$value = array_map('trim', explode(',', $value));
				}

				$defaults[$name] = $value;
			}
		}

		/**
		 * Finally, I get to fill in some values which cannot be placed in a static XML configuration file.
		 * These are things like the domain name, relative path to the site's root etc.
		 */
		$app      = Factory::getApplication();
		$uri      = Uri::getInstance(Uri::root(false));
		$rootPath = Uri::root(true);

		$defaults['httphost']    = $uri->toString(['host', 'port']);
		$defaults['httpshost']   = $uri->toString(['host', 'port']);
		$defaults['rewritebase'] = $rootPath;

		return $defaults;
	}

	/**
	 * Save the configuration to the database
	 *
	 * @param   object|array  $data          The data to save
	 * @param   bool          $withDefaults  Should I get the default values before saving the configuration?
	 */
	public function saveConfiguration($data, bool $withDefaults = true)
	{
		// Make sure we are called by an expected caller
		ServerTechnology::checkCaller($this->allowedCallersForSave);

		$data            = is_object($data) ? (array) $data : $data;
		$defaultConfig   = ($withDefaults ? $this->getDefaultConfig() : []);
		$knownConfigKeys = array_keys($defaultConfig);
		$data            = array_filter($data, function ($key) use ($knownConfigKeys) {
			return in_array($key, $knownConfigKeys);
		}, ARRAY_FILTER_USE_KEY);
		$config          = array_merge($defaultConfig, $data);

		$this->handlePotentialArrayKeys($config);

		// Make sure nobody tried to add the php extension to the list of allowed extension
		$filterPhpExtension = function ($v) {
			return strtolower($v) != 'php';
		};

		$config['bepextypes'] = array_filter($config['bepextypes'], $filterPhpExtension);
		$config['fepextypes'] = array_filter($config['fepextypes'], $filterPhpExtension);

		$this->config = $config;

		$config = json_encode($this->config);

		// This keeps Registry from happily corrupting our data :@
		if (function_exists('base64_encode') && function_exists('base64_encode'))
		{
			$config = base64_encode($config);
		}

		$params = Storage::getInstance();

		$params->setValue($this->configKey, $config);
		$params->setValue('quickstart', 1);

		$params->save();
	}

	/**
	 * Create and return the configuration file's contents. This is the heart of these features.
	 *
	 * @return  string
	 */
	abstract public function makeConfigFile();

	/**
	 * Make the configuration file and write it to the disk
	 *
	 * @return  bool
	 */
	public function writeConfigFile(): bool
	{
		// Make sure we are called by an expected caller
		ServerTechnology::checkCaller($this->allowedCallersForWrite);

		/**
		 * On Joomla 5 the public root (where the .htaccess and web.config file is written) may be different to the
		 * Joomla! installation root. However, for the NginX Conf Maker it makes more sense to always use JPATH_ROOT,
		 * as the file has to be explicitly included by the server's configuration, i.e. the server IS NOT necessarily
		 * looking for it in the public root.
		 */
		$htaccessPath = $this->getConfigFileName(true);
		$backupPath   = $htaccessPath . '.admintools';

		if (@file_exists($htaccessPath))
		{
			try
			{
				File::copy(basename($htaccessPath), basename($backupPath), dirname($htaccessPath));
			}
			catch (Exception $e)
			{
				// Swallow
			}
		}

		$configFileContents = $this->makeConfigFile();

		/**
		 * Convert CRLF to LF before saving the file. This would work around an issue with Windows browsers using CRLF
		 * line endings in text areas which would then be transferred verbatim to the output file. Most servers don't
		 * mind, but NginX will break hard when it sees the CR in the configuration file.
		 */
		$configFileContents = str_replace("\r\n", "\n", $configFileContents);

		try
		{
			return File::write($htaccessPath, $configFileContents);
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Given a server configuration file, strips out header comments and create an hash of the remaining contents
	 *
	 * @param   string  $contents
	 *
	 * @return string
	 */
	public function getConfigHash(string $contents): string
	{
		// Get the lines of the configuration
		$lines = explode("\n", $contents);

		// Trim the lines and convert comments to empty lines
		$lines = array_map(function ($line) {
			$line = trim($line);

			if (substr($line, 0, 1) === '#')
			{
				$line = '';
			}

			return $line;
		}, $lines);

		// Remove empty lines
		$lines = array_filter($lines, function ($line) {
			return !empty($line);
		});

		// Get the MD5 of the normalised contents
		return hash('md5', implode("\n", $lines));
	}

	/**
	 * Checks if current redirection rules do match the URL saved inside the live_site variable. For example:
	 * - live_site: www.example.com - Redirect www to non-www   WRONG!
	 * - live_site: www.example.com - Redirect non-www to www   CORRECT!
	 *
	 * @return bool Are the live_site variable and current redirection rules compatible?
	 */
	public function enableRedirects(): bool
	{
		$live_site = Factory::getApplication()->get('live_site', '');

		// No value set (90% of cases), we're good to go
		if (!$live_site)
		{
			return true;
		}

		// The user put the protocol in the live site? That's an hard no
		if (stripos($live_site, 'http') !== false)
		{
			return false;
		}

		$config = $this->loadConfiguration();

		// No redirection set? We're good to go
		if (!$config->wwwredir)
		{
			return true;
		}

		// Got www site and a redirect from www to non-www, that's wrong
		if ((stripos($live_site, 'www.') === 0) && ($config->wwwredir === 2))
		{
			return false;
		}

		// Got non-www site and a redirect from non-www to www, that's wrong
		if ((stripos($live_site, 'www.') === false) && ($config->wwwredir === 1))
		{
			return false;
		}

		// Otherwise we're good to go
		return true;
	}

	/**
	 * Is this configuration file type supported on this server?
	 *
	 * @return  int  0: no; 1: yes; 2: maybe (unsure)
	 */
	abstract public function isSupported(): int;

	public function convertFormDataToDatabaseData($data)
	{
		/**
		 * Subforms give us data in the format:
		 * ['__field1' => ['item' => 'directory1'], '__field2' => ['item' => 'directory2']]
		 * I need to convert to the format:
		 * ['directory1', 'directory2']
		 */
		foreach ($this->subformConfigKeys as $key)
		{
			$value = array_map(function ($subformFields) {
				return $subformFields['item'] ?? '';
			}, ($data[$key] ?? []) ?: []);

			$data[$key] = array_values(array_filter($value, function ($x) {
				return !empty($x);
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
			$result['__field'.$i] = ['item' => $item];

			$i++;
		}

		return $result;
	}

    /**
     * Checks if the current configuration file contains any directive for handling the PHP interpreter. We're adding this
     * here for consistency, but in reality this would only happen under Apache, since in NginX users already have to provide
     * this info and under IIS this is handled inside its management console.
     *
     * @param string $server_config
     *
     * @return string|null
     */
    public function extractHandler(string $server_config): ?string
    {
        return null;
    }

    /**
     * Does the current server configuration have any directive for PHP handlers?
     *
     * @return bool
     */
    public function hasPhpHandlers(): bool
    {
        return false;
    }

    /**
     * Gets any PHP handler directly from root .htaccess file
     *
     * @return string|null
     */
    public function getPhpHandlers(): ?string
    {
        return null;
    }

	protected function loadFormData()
	{
		$data = $this->loadConfiguration();

		/** @var CMSApplication $app */
		$app  = Factory::getApplication();

		// Fetch the data from the state only if we're not under CLI
		if (!$app->isClient('cli'))
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

		foreach ($this->subformConfigKeys as $key)
		{
			$value      = $data[$key] ?? [];
			$data[$key] = array_map(function ($x) {
				return ['item' => $x];
			}, $value);
		}

		if (method_exists($this, 'enableRedirects') && !$this->enableRedirects())
		{
			$data['wwwredirs'] = 0;
		}

		// Get the dispatcher and load the users plugins.
		PluginHelper::importPlugin($group);

		// Trigger the data preparation event.
		$this->triggerPluginEvent('onContentPrepareData', [$context, &$data]);
	}

	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		/**
		 * Special case: wwwredirs must be disabled (value and interaction) if there's a $live_site URL hard-coded.
		 */
		if (method_exists($this, 'enableRedirects') && !$this->enableRedirects())
		{
			$form->setFieldAttribute('wwwredirs', 'disabled', 'true');
			$form->setFieldAttribute('wwwredirs', 'required', 'false');
			$form->setFieldAttribute('wwwredirs', 'filter', 'unset');
		}

		// Import the appropriate plugin group.
		PluginHelper::importPlugin($group);

		// Trigger the form preparation event.
		$this->triggerPluginEvent('onContentPrepareForm', [$form, $data]);

	}

	/**
	 * Escapes a string so that it's a neutral string inside a regular expression.
	 *
	 * @param   string  $str  The string to escape
	 *
	 * @return  string  The escaped string
	 */
	protected function escape_string_for_regex($str): string
	{
		//All regex special chars (according to arkani at iol dot pt below):
		// \ ^ . $ | ( ) [ ]
		// * + ? { } , -

		$patterns = [
			'/\//', '/\^/', '/\./', '/\$/', '/\|/',
			'/\(/', '/\)/', '/\[/', '/\]/', '/\*/', '/\+/',
			'/\?/', '/\{/', '/\}/', '/\,/', '/\-/',
		];

		$replace = [
			'\/', '\^', '\.', '\$', '\|', '\(', '\)',
			'\[', '\]', '\*', '\+', '\?', '\{', '\}', '\,', '\-',
		];

		return preg_replace($patterns, $replace, $str);
	}

	protected function bugfixBackendProtectionExclusionDirectories(array $directories): array
	{
		if (in_array('component', $directories) && !in_array('components', $directories))
		{
			$index = array_search('component', $directories);
			$directories[$index] = 'components';
		}

		return $directories;
	}

	protected function handlePotentialArrayKeys(array &$config): void
	{
		foreach ($this->arrayConfigKeys as $key)
		{
			$value = $config[$key] ?? [];

			if (is_object($value))
			{
				$config[$key] = (array) $value;

				continue;
			}

			if (is_string($value))
			{
				$config[$key] = array_map('trim', explode(',', $value));
				$config[$key] = array_filter($config[$key], function ($x) {
					return ($x !== null) && ($x !== '');
				});

				continue;
			}

			if (!is_array($value))
			{
				$config[$key] = [];
			}
		}

	}
}