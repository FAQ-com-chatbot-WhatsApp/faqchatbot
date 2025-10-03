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
use Akeeba\Component\AdminTools\Administrator\Model\WafexceptionModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * admintools:wafexceptions:add
 *
 * List all WAF configuration options
 *
 */
class WafExceptionsAdd extends AbstractCommand
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
	protected static $defaultName = 'admintools:wafexceptions:add';

	/**
	 * Internal function to execute the command.
	 *
	 * @param InputInterface  $input  The input to inject into the command.
	 * @param OutputInterface $output The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 *
	 * @throws \Exception
	 * @since   7.5.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureEnv();
		$this->configureSymfonyIO($input, $output);

		$format = (string) $this->cliInput->getOption('format') ?? 'text';
		$format = in_array($format, ['text', 'json']) ? $format : 'text';

		/** @var WafexceptionModel $model */
		$model = $this->getMVCFactory()->createModel('Wafexception', 'Administrator');

		// Set up the new record data
		$data = [];

		$data['option'] = trim((string) $this->cliInput->getOption('component') ?? '');
		$data['view']   = trim((string) $this->cliInput->getOption('view') ?? '');
		$data['query']  = trim((string) $this->cliInput->getOption('query') ?? '');

		if (!$data['option'] && !$data['view'] && !$data['query'])
		{
			$this->ioStyle->error(Text::_('COM_ADMINTOOLS_CLI_COMMON_CREATE_ERR_MISSING'));

			return 2;
		}

		$table = $model->getTable();

		try
		{
			$result      = $table->save($data);
			/** @noinspection PhpDeprecationInspection qualified access will work when getError is removed */
			$errorString = $result ? '' : (method_exists($table, 'getError') ? $table->getError() : '');
		}
		catch (\Exception $e)
		{
			$result      = false;
			$errorString = $e->getMessage();
		}

		if ($result === false)
		{
			$this->ioStyle->error(Text::sprintf('COM_ADMINTOOLS_CLI_COMMON_CREATE_ERR_FAILED', $errorString));

			return 2;
		}

		if ($format == 'json')
		{
			echo json_encode($table->getId());

			return 0;
		}

		$this->ioStyle->success(Text::sprintf('COM_ADMINTOOLS_CLI_COMMON_CREATE_LBL_SUCCESS', $table->getId()));

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
		$this->addOption('component', null, InputOption::VALUE_REQUIRED, Text::_('COM_ADMINTOOLS_CLI_WAFEXCEPTIONS_ADD_OPT_COMPONENT'));
		$this->addOption('view', null, InputOption::VALUE_REQUIRED, Text::_('COM_ADMINTOOLS_CLI_WAFEXCEPTIONS_ADD_OPT_VIEW'));
		$this->addOption('query', null, InputOption::VALUE_REQUIRED, Text::_('COM_ADMINTOOLS_CLI_WAFEXCEPTIONS_ADD_OPT_QUERY'));
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_ADMINTOOLS_CLI_COMMON_CREATE_OPT_FORMAT'), 'text');

		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_WAFEXCEPTIONS_ADD_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_WAFEXCEPTIONS_ADD_HELP'));
	}
}
