<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\CliCommand;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureEnv;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureIO;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\IsPro;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\MemoryInfo;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\TimeInfo;
use Akeeba\Component\AdminTools\Administrator\Model\ScansModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Scan extends AbstractCommand
{
	use ConfigureEnv;
	use ConfigureIO;
	use IsPro;
	use MemoryInfo;
	use TimeInfo;
	use MVCFactoryAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.0.0
	 */
	protected static $defaultName = 'admintools:scan';

	/**
	 * @inheritDoc
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureEnv();
		$this->configureSymfonyIO($input, $output);

		$this->ioStyle->title(Text::_('COM_ADMINTOOLS_CLI_SCAN_HEAD'));

		$this->ioStyle->comment([
			sprintf('Admin Tools PHP File Change Scanner %s (%s)', ADMINTOOLS_VERSION, ADMINTOOLS_DATE),
			sprintf('Copyright (c) 2010-%s Akeeba Ltd / Nicholas K. Dionysopoulos', gmdate('Y')),
			'-------------------------------------------------------------------------------',
			'Admin Tools is Free Software, distributed under the terms of the GNU General',
			'Public License version 3 or, at your option, any later version.',
			'This program comes with ABSOLUTELY NO WARRANTY as per sections 15 & 16 of the',
			'license. See http://www.gnu.org/licenses/gpl-3.0.html for details.',
			'-------------------------------------------------------------------------------',
			sprintf('PHP %s (%s)', PHP_VERSION, PHP_SAPI)
		]);


		$mark        = microtime(true);

		if (function_exists('set_time_limit'))
		{
			$this->ioStyle->comment('Unsetting time limit restrictions.');

			@set_time_limit(0);
		}
		else
		{
			$this->ioStyle->comment('Could not unset time limit restrictions; you may get a timeout error.');
		}

		$this->ioStyle->comment([
			'Site paths determined by this command:',
			sprintf('JPATH_BASE          : %s', JPATH_BASE),
			sprintf('JPATH_ADMINISTRATOR : %s', JPATH_ADMINISTRATOR),
		]);

		/** @var ScansModel $model */
		$model = $this->getMVCFactory()->createModel('Scans', 'Administrator');

		$this->ioStyle->section('Removing old, incomplete scans (if any)');

		$model->removeIncompleteScans();

		$this->ioStyle->section('File scanning');

		$warnings_flag = false;
		$ret           = $model->startScan('cli');

		while ($ret['status'] && !$ret['done'] && empty($ret['error']))
		{
			$time         = date('Y-m-d H:i:s \G\M\TO (T)');
			$memusage     = $this->memUsage();
			$warnings     = "no warnings issued (good)";
			$stepWarnings = false;

			if (!empty($ret['warnings']))
			{
				$warnings_flag = true;
				$stepWarnings  = true;

				$warnings = sprintf("POTENTIAL PROBLEMS DETECTED; %s warnings issued (see below).\n", count($ret['warnings']));

				foreach ($ret['Warnings'] as $line)
				{
					$warnings .= "\t$line\n";
				}
			}


			$stepInfo = <<<ENDSTEPINFO
Last Tick   : $time
Memory used : $memusage
Warnings    : $warnings

ENDSTEPINFO;
			$this->ioStyle->comment($stepInfo);

			$ret = $model->stepScan();
		}

		$this->ioStyle->comment(sprintf("Peak memory usage: %s", $this->peakMemUsage()));

		if (!empty($ret['error']))
		{
			$this->ioStyle->error([
				'An error has occurred:',
				$ret['error']
			]);

			return 2;
		}

		if ($warnings_flag)
		{
			$this->ioStyle->warning([
				'Admin Tools issued warnings during the scanning process. You have to review them',
				'and make sure that your scan has completed successfully.',
			]);

			return 1;
		}

		$this->ioStyle->success([
			sprintf("File scanning job finished successfully after approximately %s", $this->timeago($mark, time(), '', false))
		]);

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
		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_SCAN_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_SCAN_HELP'));
	}

}