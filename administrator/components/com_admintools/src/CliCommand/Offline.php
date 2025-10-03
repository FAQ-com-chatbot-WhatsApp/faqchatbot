<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\CliCommand;

defined('_JEXEC') || die;

use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureEnv;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureIO;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\PrintFormattedArray;
use Akeeba\Component\AdminTools\Administrator\Model\EmergencyofflineModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * admintools:offline
 *
 */
class Offline extends AbstractCommand
{
	use ConfigureEnv;
	use ConfigureIO;
	use PrintFormattedArray;
	use MVCFactoryAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string
	 */
	protected static $defaultName = 'admintools:offline';

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

		$enable = $this->cliInput->getOption('enable') ?? false;
		$disable = $this->cliInput->getOption('disable') ?? false;

		if (!$enable && !$disable)
		{
			$this->ioStyle->error(Text::_('COM_ADMINTOOLS_CLI_OFFLINE_MISSING_MODE'));

			return 1;
		}

		/** @var EmergencyofflineModel $model */
		$model        = $this->getMVCFactory()->createModel('Emergencyoffline', 'Administrator');

		if ($enable)
		{
			$success = $model->putOffline();
		}
		else
		{
			$success = $model->putOnline();
		}

		if ($success)
		{
			$this->ioStyle->success(Text::_('COM_ADMINTOOLS_CLI_OFFLINE_RESULT_SUCCESS'));

			return 0;
		}


		$this->ioStyle->error(Text::_('COM_ADMINTOOLS_CLI_OFFLINE_RESULT_FAILURE'));

		return 1;
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
		$this->addOption('enable' , null, InputOption::VALUE_NONE, Text::_('COM_ADMINTOOLS_CLI_OFFLINE_OPT_ENABLE'));
		$this->addOption('disable', null, InputOption::VALUE_NONE, Text::_('COM_ADMINTOOLS_CLI_OFFLINE_OPT_DISABLE'));

		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_OFFLINE_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_OFFLINE_HELP'));
	}
}
