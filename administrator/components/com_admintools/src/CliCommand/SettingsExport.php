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
 * admintools:export
 *
 */
class SettingsExport extends AbstractCommand
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
	protected static $defaultName = 'admintools:export';

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

		/** @var ExportimportModel $model */
		$model = $this->getMVCFactory()->createModel('Exportimport', 'Administrator');

		$model->setState('exportdata', [
			'wafconfig'     => true,
			'wafblacklist'  => true,
			'wafexceptions' => true,
			'ipblacklist'   => true,
			'ipwhitelist'   => true,
			'badwords'      => true,
			'useragents'    => true,
            'serverconfig'  => true,
		]);

		$data = $model->exportData();

		if ($file)
		{
			file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

			$this->ioStyle->success(Text::sprintf('COM_ADMINTOOLS_CLI_EXPORT_TOFILE', $file));

			return 0;
		}

		return $this->printFormattedAndReturn($data, 'json');
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
		$this->addOption('file' , null, InputOption::VALUE_OPTIONAL, Text::_('COM_ADMINTOOLS_CLI_EXPORT_OPT_FILE'));

		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_EXPORT_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_EXPORT_HELP'));
	}
}
