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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;

class AutobannedaddressesController extends AdminController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerRegisterTasksTrait;
	use ControllerReusableModelsTrait;

	protected $text_prefix = 'COM_ADMINTOOLS_AUTOBANNEDADDRESS';

	public function getModel(
		$name = 'Autobannedaddress', $prefix = 'Administrator', $config = ['ignore_request' => true]
	)
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function delete()
	{
		// Check for request forgeries
		$this->checkToken();

		// Get items to remove from the request.
		$cid = $this->input->get('cid', [], 'array');

		if (!\is_array($cid) || \count($cid) < 1)
		{
			$this->app->getLogger()->warning(
				Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), ['category' => 'jerror']
			);
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Remove the items.
			try
			{
				$isDeleted    = $model->delete($cid);
				/** @noinspection PhpDeprecationInspection qualified access will work when getError is removed */
				$errorMessage = method_exists($model, 'getError') ? $model->getError() : '';
			}
			catch (\Exception $e)
			{
				$isDeleted    = false;
				$errorMessage = $e->getMessage();
			}

			if ($isDeleted)
			{
				$this->setMessage(Text::plural($this->text_prefix . '_N_ITEMS_DELETED', \count($cid)));
			}
			else
			{
				$this->setMessage($errorMessage, 'error');
			}

			// Invoke the postDelete method to allow for the child class to access the model.
			$this->postDeleteHook($model, $cid);
		}

		$this->setRedirect(
			Route::_(
				'index.php?option=' . $this->option . '&view=' . $this->view_list
				. $this->getRedirectToListAppend(), false
			)
		);
	}


}