<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\CliCommand;

defined('_JEXEC') || die;

use Akeeba\Component\AdminTools\Administrator\CliCommand\ServerConfiguration\ServerConfigurationList;

/**
 * admintools:webconfigmaker:list
 *
 * List all Nginx configuration options
 *
 */
class WebconfigMakerList extends ServerConfigurationList
{
	/**
	 * The default command name
	 *
	 * @var    string
	 */
	protected static $defaultName = 'admintools:webconfigmaker:list';

	/** @var string Which engine are we working on? */
	protected $server_engine = 'webconfigmaker';
}
