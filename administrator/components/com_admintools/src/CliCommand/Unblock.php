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
use Akeeba\Component\AdminTools\Administrator\Model\UnblockipModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * admintools:unblock
 *
 * Unblock an IP address
 *
 */
class Unblock extends AbstractCommand
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
	protected static $defaultName = 'admintools:unblock';

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

		$unblockIP = (string) $this->cliInput->getOption('ip');

		if (!$unblockIP)
		{
			$this->ioStyle->error(Text::sprintf('COM_ADMINTOOLS_CLI_COMMON_MISSING_REQUIRED', 'ip'));

			return 1;
		}

		/** @var UnblockipModel $model */
		$model        = $this->getMVCFactory()->createModel('Unblockip', 'Administrator');
		$model->unblockIP($unblockIP);

		$this->ioStyle->success(Text::sprintf('COM_ADMINTOOLS_CLI_WAFUNBLOCK_LBL_IP', $unblockIP));

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
		$this->addOption('ip', null, InputOption::VALUE_REQUIRED, Text::_('COM_ADMINTOOLS_CLI_WAFUNBLOCK_OPT_IP'));
		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_WAFUNBLOCK_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_WAFUNBLOCK_HELP'));
	}
}
