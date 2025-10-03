<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerCustomACLTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerRegisterTasksTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\SendTroubleshootingEmailTrait;
use Akeeba\Component\AdminTools\Administrator\Model\ServerconfigmakerModel;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\Input\Input;

class ServerconfigmakerController extends BaseController
{
	use ControllerCustomACLTrait;
	use ControllerEventsTrait;
	use ControllerRegisterTasksTrait;
	use SendTroubleshootingEmailTrait;
	use ControllerReusableModelsTrait;

	/**
	 * The prefix for the language strings of the information and error messages
	 *
	 * @var string
	 */
	protected $langKeyPrefix = 'COM_ADMINTOOLS_HTACCESSMAKER_LBL_';

	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks();
	}

	public function preview()
	{
		parent::display(false);
	}

	public function save()
	{
		$this->saveOrApply(false);

		$redirectURL = Route::_('index.php?option=com_admintools&view=' . $this->getName(), false);
		$this->setRedirect($redirectURL, Text::_($this->langKeyPrefix . 'SAVED'));
	}

	public function apply()
	{
		$status = $this->saveOrApply(true);

		$redirectURL = Route::_('index.php?option=com_admintools&view=' . $this->getName(), false);

		if (!$status)
		{
			$this->setRedirect($redirectURL, Text::_($this->langKeyPrefix . 'NOTAPPLIED'), 'error');

			return;
		}

		$this->setRedirect($redirectURL, Text::_($this->langKeyPrefix . 'APPLIED'));
	}

	protected function saveOrApply(bool $writeFile = false): bool
	{
		$this->checkToken();

		/** @var ServerconfigmakerModel $model */
		$model = $this->getModel();

		/**
		 * Get the raw, incoming data.
		 *
		 * Note that Joomla's getArray() applies filtering. So we need to run it once to get the submitted keys, then
		 * loop through this keys using getRaw to get the actual raw values. THEN AND ONLY THEN can the form truly
		 * validate the data correctly. MASSIVE GROAN.
		 */
		$data = $this->input->getArray();
		$keys = array_keys($data);

		foreach ($keys as $k)
		{
			$data[$k] = $this->input->getRaw($k);
		}

		// Save data in the session
	    $this->app->setUserState('com_admintools.' . $this->getName() . '.data', $model->convertFormDataToDatabaseData($data));

		$form = $model->getForm($data, true);
		$data = $model->validate($form, $data);

		if (is_null($data))
		{
			foreach ($form->getErrors() as $error)
			{
				$this->app->enqueueMessage($error->getMessage(), 'warning');
			}

			return false;
		}

		$data = $model->convertFormDataToDatabaseData($data);

		// Wrong $live_site value, force no wwwredir
		if (!$model->enableRedirects())
		{
			$data['wwwredir'] = 0;
		}

		$model->saveConfiguration($data);
		$status = true;

		// Clear data from the session
		$this->app->setUserState('com_admintools.' . $this->getName() . '.data', null);

		if ($writeFile)
		{
			$this->sendTroubelshootingEmail($this->getName());
			$status = $model->writeConfigFile();
		}

		return $status;
	}

	public function reset()
	{
		$this->checkToken();

		/** @var ServerconfigmakerModel $model */
		$model = $this->getModel();

		/**
		 * The configuration you are saving is merged with the default configuration from the XML form before saving.
		 * Therefore, I only need to pass an empty configuration to make this work :)
		 */
		$model->saveConfiguration([]);

		// Clear data from the session
		$this->app->setUserState('com_admintools.' . $this->getName() . '.data', null);

		$redirectURL = Route::_('index.php?option=com_admintools&view=' . $this->getName(), false);

		$this->setRedirect($redirectURL, Text::_($this->langKeyPrefix . 'RESET_DONE'));
	}
}