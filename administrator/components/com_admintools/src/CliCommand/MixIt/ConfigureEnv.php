<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt;

defined('_JEXEC') || die;


/**
 * Configures Joomla environment before running any command
 *
 */
trait ConfigureEnv
{
	/**
	 * Configure the environment with the data required (ie define component constants)
	 **
	 * @return  void
	 *
	 */
	private function configureEnv()
	{
		if (!defined('JPATH_COMPONENT'))
		{
			define('JPATH_COMPONENT', JPATH_ADMINISTRATOR . '/components/com_admintools');
		}

		// These constants are deprecated, but let's add them anyway
		if (!defined('JPATH_COMPONENT_SITE'))
		{
			define('JPATH_COMPONENT_SITE', JPATH_ROOT . '/components/com_admintools');
		}

		if (!defined('JPATH_COMPONENT_ADMINISTRATOR'))
		{
			define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_admintools');
		}
	}
}
