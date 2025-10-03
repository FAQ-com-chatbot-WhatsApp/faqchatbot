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
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\AdminTools\Administrator\Model\BlockedrequestslogsModel;
use Exception;
use Joomla\CMS\Document\JsonDocument;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;

class BlockedrequestslogController extends AdminController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerReusableModelsTrait;

	protected $text_prefix = 'COM_ADMINTOOLS_LOG';

	public function getModel($name = 'Blockedrequestslog', $prefix = '', $config = [])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function display($cachable = false, $urlparams = [])
	{
		if ($this->app->getDocument() instanceof JsonDocument)
		{
			$model = $this->getModel('Blockedrequestslogs', '', ['ignore_request' => true]);

			$model->setState('groupbydate', $this->input->getInt('groupbydate'));
			$model->setState('groupbytype', $this->input->getInt('groupbytype'));
			$model->setState('datefrom', $this->input->getString('datefrom'));
			$model->setState('dateto', $this->input->getString('dateto'));
			$model->setState('ip', $this->input->getString('ip'));
			$model->setState('url', $this->input->getString('url'));
			$model->setState('reason', $this->input->getString('reason'));

			$limit = $this->input->getInt('limit', 20);
			$model->setState('list.limit', $limit);

			$value      = $this->input->getInt('limitstart', 0);
			$limitstart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);
			$model->setState('list.start', $limitstart);
		}
		else
		{
			$model = $this->getModel('Blockedrequestslogs', 'Administrator');
		}

		$view = $this->getView();
		$view->setModel($model, true);

		$cPanelModel = $this->getModel('Controlpanel', 'Administrator');
		$view->setModel($cPanelModel, false);

		$view->setDocument($this->app->getDocument());

		$view->display();

		return $this;
	}


	public function ban()
	{
		$this->checkToken('request');

		$url     = Route::_('index.php?option=com_admintools&view=Blockedrequestslog', false);
		$msg     = Text::_('COM_ADMINTOOLS_DISALLOWLIST_LBL_SAVED');
		$msgType = 'success';

		try
		{
			$id = $this->input->getString('id', '');

			if (empty($id))
			{
				throw new Exception(Text::_('COM_ADMINTOOLS_LOG_ERR_NOID'), 500);
			}

			/** @var BlockedrequestslogsModel $model */
			$model = $this->getModel('Blockedrequestslogs', 'Administrator');

			try
			{
				$isBanned     = $model->ban($id);
				/** @noinspection PhpDeprecationInspection qualified access will work when getError is removed */
				$errorMessage = method_exists($model, 'getError') ? $model->getError() : '';
			}
			catch (Exception $e)
			{
				$isBanned     = false;
				$errorMessage = $e->getMessage();
			}

			if (!$isBanned)
			{
				throw new Exception($errorMessage);
			}
		}
		catch (Exception $e)
		{
			$msg     = $e->getMessage();
			$msgType = 'error';
		}

		$this->setRedirect($url, $msg, $msgType);
	}

	public function unban()
	{
		$this->checkToken('request');

		$url     = Route::_('index.php?option=com_admintools&view=Blockedrequestslog', false);
		$msg     = Text::_('COM_ADMINTOOLS_DISALLOWLIST_LBL_DELETED');
		$msgType = 'success';

		try
		{
			$id = $this->input->getString('id', '');

			if (empty($id))
			{
				throw new Exception(Text::_('COM_ADMINTOOLS_LOG_ERR_NOID'), 500);
			}

			/** @var BlockedrequestslogsModel $model */
			$model = $this->getModel('Blockedrequestslogs', 'Administrator');
			$model->unban($id);
		}
		catch (Exception $e)
		{
			$msg     = $e->getMessage();
			$msgType = 'error';
		}

		$this->setRedirect($url, $msg, $msgType);
	}
}