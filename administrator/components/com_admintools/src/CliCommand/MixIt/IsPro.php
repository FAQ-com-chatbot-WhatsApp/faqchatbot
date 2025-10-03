<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt;

defined('_JEXEC') || die;

/**
 * Is this Akeeba Backup Pro?
 *
 * @since   7.5.0
 */
trait IsPro
{
	/**
	 * Caches whether this is the Pro version of the software.
	 *
	 * @var   null|bool
	 * @since 7.5.0
	 */
	private $isPro = null;

	/**
	 * is this the Professional version of the software?
	 *
	 * @return  bool
	 * @since   7.5.0
	 */
	private function isPro(): bool
	{
		if (!is_null($this->isPro))
		{
			return $this->isPro;
		}

		if (defined('ADMINTOOLS_PRO'))
		{
			$this->isPro = ADMINTOOLS_PRO == 1;
		}
		else
		{
			$componentFolder = JPATH_ADMINISTRATOR . '/components/com_admintools';
			$this->isPro     = is_dir($componentFolder . '/src/Scanner');
		}

		return $this->isPro;
	}
}
