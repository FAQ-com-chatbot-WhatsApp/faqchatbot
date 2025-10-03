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
 * admintools:htmaker:make
 *
 * Generate server configuration file accordingly to Admin Tools' rules
 *
 */
class HtaccessMakerMake extends ServerConfigurationMake
{
	/**
	 * The default command name
	 *
	 * @var    string
	 */
	protected static $defaultName = 'admintools:htmaker:make';

	/** @var string Which engine are we working on? */
	protected $server_engine = 'htaccessmaker';

	/** @var string Server version that we should use by default */
	protected $default_server_version = '2.4';
}
