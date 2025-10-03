<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Controller;

use Akeeba\Component\AdminTools\Administrator\Model\HtaccessmakerModel;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class HtaccessmakerController extends ServerconfigmakerController
{
	/**
	 * The prefix for the language strings of the information and error messages
	 *
	 * @var string
	 */
	protected $langKeyPrefix = 'COM_ADMINTOOLS_HTACCESSMAKER_LBL_';

    public function addphphandler()
    {
        $this->checkToken('get');

        /** @var HtaccessmakerModel $model */
        $model = $this->getModel();

        $msg  = Text::_('COM_ADMINTOOLS_HTACCESSMAKER_LBL_PHPHANDLERS_SAVED');
        $type = null;

        try
        {
            $model->includePhpHandlers();
        }
        catch (\Exception $e)
        {
            $msg  = $e->getMessage();
            $type = 'warning';
        }

        $this->setRedirect('index.php?option=com_admintools&view=HtaccessMaker', $msg, $type);
    }
}