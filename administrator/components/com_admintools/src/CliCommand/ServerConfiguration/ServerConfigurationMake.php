<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\CliCommand\ServerConfiguration;

use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureEnv;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureIO;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\PrintFormattedArray;
use Akeeba\Component\AdminTools\Administrator\Model\ServerconfigmakerModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ServerConfigurationMake extends AbstractCommand
{
	use ConfigureEnv;
	use ConfigureIO;
	use PrintFormattedArray;
	use MVCFactoryAwareTrait;

	/** @var string Which engine are we working on? */
	protected $server_engine = '';

	/** @var string Server version that we should use by default */
	protected $default_server_version = '';

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 *
	 * @since   7.5.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureEnv();
		$this->configureSymfonyIO($input, $output);

		$preview = $this->cliInput->getOption('preview') ?? false;
		$version = $this->cliInput->getOption('server_version') ?? $this->default_server_version;

		/** @var ServerconfigmakerModel $model */
		$model = $this->getMVCFactory()->createModel(ucfirst($this->server_engine), 'Administrator');
		$model->setServerVersion($version);

		if ($preview)
		{
			$output = $model->makeConfigFile();

			$this->ioStyle->text($output);

			return 0;
		}

		if (!$model->writeConfigFile())
		{
			$this->ioStyle->error(Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGMAKE_OPT_WRITEKO'));

			return 1;
		}

		$this->ioStyle->success(Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGMAKE_OPT_WRITEOK'));

		return 0;
	}

	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   7.5.0
	 */
	protected function configure(): void
	{
		$this->addOption('preview', null, InputOption::VALUE_NONE, Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGMAKE_OPT_PREVIEW'));
		$this->addOption('server_version', null, InputOption::VALUE_OPTIONAL, Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGMAKE_OPT_VERSION'));

		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGMAKE_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGMAKE_HELP'));
	}
}