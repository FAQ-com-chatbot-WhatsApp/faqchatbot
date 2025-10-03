<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

/**
 * @property string $ip
 * @property string $reason
 * @property string $until
 */
class AutobannedaddressTable extends AbstractTable
{
	public function __construct(DatabaseDriver $db, DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__admintools_ipautoban', 'ip', $db, $dispatcher);
	}
}