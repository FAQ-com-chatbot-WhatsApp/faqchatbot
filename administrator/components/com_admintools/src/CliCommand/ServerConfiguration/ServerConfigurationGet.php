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

abstract class ServerConfigurationGet extends AbstractCommand
{
	use ConfigureEnv;
	use ConfigureIO;
	use PrintFormattedArray;
	use MVCFactoryAwareTrait;

	/** @var string Which engine are we working on? */
	protected $server_engine = '';

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

		$option = (string) $this->cliInput->getOption('option');

		/** @var ServerconfigmakerModel $model */
		$model = $this->getMVCFactory()->createModel(ucfirst($this->server_engine), 'Administrator');

		$configuration = $model->loadConfiguration();

		if (!$option || !isset($configuration[$option]))
		{
			$this->ioStyle->error(Text::sprintf('COM_ADMINTOOLS_CLI_SERVERCONFIG_ERR_CANNOT_FIND', $option));

			return 1;
		}

		$output = [
			$option => $configuration[$option]
		];

		return $this->printFormattedAndReturn($output, 'json');
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
		$this->addOption('option', null, InputOption::VALUE_REQUIRED, Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIG_OPT_RULE'));
		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGGET_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGGET_HELP'));
	}
}