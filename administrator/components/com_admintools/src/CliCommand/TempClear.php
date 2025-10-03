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
use Akeeba\Component\AdminTools\Administrator\Model\CleantempdirectoryModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TempClear extends AbstractCommand
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
	protected static $defaultName = 'admintools:temp:clear';

	/**
	 * @inheritDoc
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureEnv();
		$this->configureSymfonyIO($input, $output);

		/** @var CleantempdirectoryModel $model */
		$model = $this->getMVCFactory()->createModel('Cleantempdirectory', 'Administrator');
		$model->setMinAge(@intval($this->cliInput->getOption('age')) ?: 60);

		$state = $model->process();

		while (!$state)
		{
			$model->process();
		}

		return Command::SUCCESS;
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
		$this->addOption('age', 'a', InputOption::VALUE_OPTIONAL, Text::_('COM_ADMINTOOLS_CLI_TEMP_CLEAR_OPT_AGE'), 60);

		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_TEMP_CLEAR_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_TEMP_CLEAR_HELP'));
	}
}