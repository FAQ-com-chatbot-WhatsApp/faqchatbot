<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\CliCommand;

defined('_JEXEC') || die;

use Akeeba\Component\AdminTools\Administrator\CliCommand\ServerConfiguration\ServerConfigurationMake;

/**
 * admintools:nginxmaker:make
 *
 * Generate server configuration file accordingly to Admin Tools' rules
 *
 */
class NginxconfMakerMake extends ServerConfigurationMake
{
	/**
	 * The default command name
	 *
	 * @var    string
	 */
	protected static $defaultName = 'admintools:nginxmaker:make';

	/** @var string Which engine are we working on? */
	protected $server_engine = 'nginxconfmaker';
}
