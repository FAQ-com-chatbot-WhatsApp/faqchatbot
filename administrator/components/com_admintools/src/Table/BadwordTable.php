<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;
use RuntimeException;

/**
 * @property integer id
 * @property string  word
 */
class BadwordTable extends AbstractTable
{
	public function __construct(DatabaseDriver $db, DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__admintools_badwords', 'id', $db, $dispatcher);
	}

	protected function onBeforeCheck()
	{
		if (!$this->word)
		{
			throw new RuntimeException(Text::_('COM_ADMINTOOLS_BADWORDS_ERR_NEEDS_WORD'));
		}
	}
}