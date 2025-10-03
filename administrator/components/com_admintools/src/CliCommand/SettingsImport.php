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
use Akeeba\Component\AdminTools\Administrator\Model\ExportimportModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * admintools:import
 *
 */
class SettingsImport extends AbstractCommand
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
	protected static $defaultName = 'admintools:import';

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

		$file = (string) ($this->cliInput->getOption('file') ?? '');

		if (!$file || !file_exists($file))
		{
			$this->ioStyle->error(Text::sprintf('COM_ADMINTOOLS_CLI_IMPORT_FILE_MISSING',$file));

			return 1;
		}

		$data = file_get_contents($file);

		/** @var ExportimportModel $model */
		$model = $this->getMVCFactory()->createModel('Exportimport', 'Administrator');

		try
		{
			// Tell Admin Tools to not load default configuration, otherwise we will have a fatal error since JForm can't
			// operate in CLI. This is an acceptable deal, since we have two scenarios:
			// 1. User is importing ALL the configuration data. No problem if we have some missing key
			// 2. Some data during the import is missing. Again no big deal since Admin Tools can operate without issues
			//    and it "fill" any missing data with defaults while working from the GUI (since we can load defaults)
			$model->importData($data, false);
		}
		catch (\Exception $e)
		{
			$this->ioStyle->error(Text::sprintf('COM_ADMINTOOLS_CLI_IMPORT_ERRORS', $e->getMessage()));

			return 1;
		}

		$this->ioStyle->success(Text::sprintf('COM_ADMINTOOLS_CLI_IMPORT_SUCCESS', $file));

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
		$this->addOption('file' , null, InputOption::VALUE_REQUIRED, Text::_('COM_ADMINTOOLS_CLI_IMPORT_OPT_FILE'));

		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_IMPORT_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_IMPORT_HELP'));
	}
}
