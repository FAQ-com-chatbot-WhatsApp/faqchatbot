<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Helper\Storage;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerRegisterTasksTrait;
use Akeeba\Component\AdminTools\Administrator\Model\ScansModel;
use Akeeba\Component\AdminTools\Administrator\Scanner\Complexify;
use Akeeba\Component\AdminTools\Administrator\Scanner\Util\Session;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;

class FilescannerController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerRegisterTasksTrait;

	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks('start');
	}

	/**
	 * Overriden to load the backend Scans model by default
	 *
	 * @param   string        $name
	 * @param   string        $prefix
	 * @param   array|bool[]  $config
	 *
	 * @return bool|\Joomla\CMS\MVC\Model\BaseDatabaseModel
	 */
	public function getModel($name = 'Scans', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Starts a new front-end PHP File Change Scanner job
	 *
	 * @return  void
	 */
	public function start()
	{
		$this->enforceFrontendRequirements();

		/** @var ScansModel $model */
		$model = $this->getModel();

		$model->removeIncompleteScans();
		$this->resetPersistedEngineState();

		$resultArray = $model->startScan('frontend');

		$this->persistEngineState();
		$this->processResultArray($resultArray);
	}

	/**
	 * Steps through an already running front-end PHP File Change Scanner job
	 *
	 * @return  void
	 */
	public function step()
	{
		$this->enforceFrontendRequirements();
		$this->retrieveEngineState();

		/** @var ScansModel $model */
		$model       = $this->getModel();
		$resultArray = $model->stepScan();

		$this->persistEngineState();
		$this->processResultArray($resultArray);
	}

	/**
	 * Ensure that front-end scans are enabled and that the URL includes a correct, complex enough secret key.
	 *
	 * If any of these conditions is not met we return a 403.
	 *
	 * @return  void
	 */
	private function enforceFrontendRequirements()
	{
		$cParams = ComponentHelper::getParams('com_admintools');

		// Is frontend backup enabled?
		$febEnabled = $cParams->get('frontend_enable', 0) != 0;

		// Is the Secret Key strong enough?
		$validKey = $cParams->get('frontend_secret_word', '');

		if (!Complexify::isStrongEnough($validKey, false))
		{
			$febEnabled = false;
		}

		if (!$febEnabled)
		{
			@ob_end_clean();
			echo '403 ' . Text::_('COM_ADMINTOOLS_ERR_NOT_ENABLED');
			flush();

			$this->app->close();

			return;
		}

		// Is the key good?
		$key          = $this->input->getRaw('key', '');
		$validKeyTrim = trim($validKey);

		if (($key != $validKey) || (empty($validKeyTrim)))
		{
			@ob_end_clean();
			echo '403 ' . Text::_('COM_ADMINTOOLS_ERR_INVALID_KEY');
			flush();

			$this->app->close();
		}
	}

	/**
	 * Immediately issue a custom redirection and close the application.
	 *
	 * Unlike the regular Controller::redirect() this acts immediately and does not go through Joomla. Therefore we can
	 * use custom HTTP headers.
	 *
	 * @param   string  $url     URL to redirect to
	 * @param   string  $header  HTTP/1.1 header to use. Default: 302 Found (temporary redirection)
	 */
	private function issueRedirection($url, $header = '302 Found')
	{
		header('HTTP/1.1 ' . $header);
		header('Location: ' . $url);
		header('Content-Type: text/plain');
		header('Connection: close');

		$this->app->close();
	}

	/**
	 * Process the scanner engine's result array and send the correct response to the browser
	 *
	 * This included issuing a custom redirection to the URL of the next step if such a thing is necessary. In either
	 * case, the application is immediately closed right at the end of this method's execution.
	 *
	 * @param   array  $resultArray  The result array to parse
	 *
	 * @return  void
	 */
	private function processResultArray(array $resultArray)
	{
		// Is this an error?
		if ($resultArray['error'] != '')
		{
			$this->resetPersistedEngineState();

			// An error occured
			die('500 ERROR -- ' . $resultArray['error']);
		}

		// Are we finished already?
		if ($resultArray['done'])
		{
			$this->resetPersistedEngineState();

			@ob_end_clean();
			header('Content-type: text/plain');
			header('Connection: close');
			echo '200 OK';
			flush();

			$this->app->close();

			return;
		}

		// We have more work to do. Should we redirect...?
		$noredirect = $this->input->get('noredirect', 0, 'int');

		if ($noredirect != 0)
		{
			@ob_end_clean();
			header('Content-type: text/plain');
			header('Connection: close');
			echo "301 More work required";
			flush();

			$this->app->close();

			return;
		}

		$curUri  = Uri::getInstance();
		$ssl     = $curUri->isSSL() ? 1 : 0;
		$tempURL = Route::_('index.php', false, $ssl);
		$uri     = new Uri($tempURL);

		$uri->setVar('option', 'com_admintools');
		$uri->setVar('view', 'filescanner');
		$uri->setVar('task', 'step');
		$uri->setVar('format', 'raw');
		$uri->delVar('key');

		// Maybe we have a multilingual site?
		$languageTag = $this->app->getLanguage()->getTag();

		$uri->setVar('lang', $languageTag);
		$uri->setVar('key', urlencode($this->input->getRaw('key', '')));

		$redirectionURL = $uri->toString();

		$this->issueRedirection($redirectionURL);
	}

	/**
	 * Resets the persisted scanner engine state.
	 *
	 * @return  void
	 */
	private function resetPersistedEngineState()
	{
		$storage = new Storage();
		$storage->setValue('filescanner.memory', null);
		$storage->setValue('filescanner.timestamp', 0);
		$storage->save();
	}

	/**
	 * Persist the scanner engine state in the database.
	 *
	 * @return  void
	 */
	private function persistEngineState()
	{
		$session     = Session::getInstance();
		$storage     = new Storage();
		$sessionData = array_combine($session->getKnownKeys(), array_map(function ($key) use ($session) {
			return $session->get($key);
		}, $session->getKnownKeys()));

		$storage->setValue('filescanner.memory', json_encode($sessionData));
		$storage->setValue('filescanner.timestamp', time());
		$storage->save();
	}

	/**
	 * Retrieve the persisted scanner engine state from the database.
	 *
	 * It will result in a 403 error if there is no state, the state is invalid or it was stored more than 90 seconds
	 * ago.
	 *
	 * @return  void
	 */
	private function retrieveEngineState()
	{
		// Retrieve the engine's session from Admin Tools' storage in the database
		$storage    = new Storage();
		$jsonMemory = $storage->getValue('filescanner.memory', null);
		$timestamp  = $storage->getValue('filescanner.timestamp', 0);
		$valid      = !empty($jsonMemory) && (time() - $timestamp <= 90);
		try
		{
			$sessionData = @json_decode($jsonMemory, true);
		}
		catch (Exception $e)
		{
			$sessionData = null;
		}

		// If we have no data stored, invalid data stored or it was stored more than 90 seconds ago we can't proceed.
		if (!$valid || is_null($sessionData))
		{
			$this->resetPersistedEngineState();

			@ob_end_clean();
			echo '403 ' . Text::_('COM_ADMINTOOLS_ERR_NOT_ENABLED');
			flush();

			$this->app->close();
		}

		// Populate the session from the persisted state
		$session = Session::getInstance();
		array_walk($sessionData, function ($value, $key) use ($session) {
			$session->set($key, $value);
		});
	}
}