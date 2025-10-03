<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use DirectoryIterator;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseModel;

#[\AllowDynamicProperties]
class CleantempdirectoryModel extends BaseModel
{
	/**
	 * Minimum age (in seconds) of files and folders to delete.
	 *
	 * @var   int
	 * @since 6.1.0
	 */
	private $minAge = 60;

	/**
	 * Maximum amount of time to spend deleting files (seconds).
	 *
	 * @var   float
	 * @since 7.7.0
	 */
	private float $timeoutThreshold = 5.0;

	/**
	 * Timestamp we started recursively deleting the temp directory contents.
	 *
	 * @var   float|int
	 * @since 7.7.0
	 */
	private float $startTime;

	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->restartTimer();
	}

	/**
	 * Process the temp directory
	 *
	 * @return  bool  True when we are done processing.
	 * @since   7.7.0
	 * @throws  \Exception
	 */
	public function process(): bool
	{
		$this->restartTimer();

		$tmpDir = Factory::getApplication()->get('tmp_path');

		// Refuse to process a non-existent directory.
		if (!@is_dir($tmpDir))
		{
			return true;
		}

		// Refuse to process a directory which does not exist.
		$realTmpDir = @realpath($tmpDir);

		// Refuse to process a directory which coincides with a core Joomla! directory
		if (
			empty($realTmpDir)
			|| $realTmpDir === realpath(JPATH_ROOT)
			|| $realTmpDir === realpath(JPATH_SITE)
			|| $realTmpDir === realpath(JPATH_ADMINISTRATOR)
			|| $realTmpDir === realpath(JPATH_API)
			|| $realTmpDir === realpath(JPATH_CLI)
			|| $realTmpDir === realpath(JPATH_CACHE)
			|| $realTmpDir === realpath(JPATH_CONFIGURATION)
			|| $realTmpDir === realpath(JPATH_LIBRARIES)
			|| $realTmpDir === realpath(JPATH_MANIFESTS)
			|| $realTmpDir === realpath(JPATH_PLUGINS)
			|| $realTmpDir === realpath(JPATH_THEMES)
		)
		{
			return true;
		}

		// Refuse to process a directory which coincides with the Joomla! public folder.
		if (defined('JPATH_PUBLIC') && $realTmpDir === realpath(constant('JPATH_PUBLIC')))
		{
			return true;
		}

		try
		{
			$this->recursiveDelete(
				$realTmpDir,
				[
					'index.html',
					'index.htm',
					'.htaccess',
					'web.config',
				]
			);
		}
		catch (\Exception $e)
		{
			if ($e->getMessage() === 'Timeout reached')
			{
				return false;
			}

			throw $e;
		}

		return true;
	}

	/**
	 * Set the minimum age of files to delete (seconds).
	 *
	 * @param   int  $minAge  The minimum age in seconds.
	 *
	 * @return  void
	 * @since   7.7.0
	 */
	public function setMinAge(int $minAge): void
	{
		$this->minAge = max(0, $minAge);
	}

	/**
	 * Sets the timeout threshold. The value cannot be less than 1.0.
	 *
	 * @param   float  $timeoutThreshold  The desired timeout threshold in seconds.
	 *
	 * @return  void
	 * @since   7.7.0
	 */
	public function setTimeoutThreshold(float $timeoutThreshold): void
	{
		$this->timeoutThreshold = max(1.0, $timeoutThreshold);
	}

	/**
	 * Recursively delete the contents of a folder.
	 *
	 * Note that this does NOT delete the folder itself.
	 *
	 * @param   string  $folder       Absolute path to the folder whose contents I will delete.
	 * @param   array   $doNotDelete  List of files and folders to not delete (NOT used for the recursion).
	 *
	 * @return  void
	 * @since   7.7.0
	 */
	private function recursiveDelete(string $folder, array $doNotDelete = []): void
	{
		$cutoffTime = time() - $this->minAge;
		//return (@filemtime($file) ?: 0) < $cutoffTime;

		/** @var DirectoryIterator $item */
		foreach (new DirectoryIterator($folder) as $item)
		{
			// Pseudo-elements are always skipped over.
			if ($item->isDot())
			{
				continue;
			}

			// Check for timeout
			if ($this->isTimeout())
			{
				throw new \RuntimeException('Timeout reached');
			}

			// Only delete files older than the specific cutoff time.
			if ($item->getMTime() >= $cutoffTime)
			{
				continue;
			}

			// Do not delete certain files or folders
			if (!empty($doNotDelete) && in_array($item->getFilename(), $doNotDelete))
			{
				continue;
			}

			// Symlinks need special handling
			if ($item->isLink())
			{
				@unlink($item->getPathname()) || @rmdir($item->getPathname());

				continue;
			}

			// Recursively delete subdirectories
			if ($item->isDir())
			{
				$this->recursiveDelete($item->getPathname());

				@rmdir($item->getPathname());

				continue;
			}

			// It's a file. Simple unlink.
			@unlink($item->getPathname());
		}
	}

	/**
	 * Restart the timer
	 *
	 * @return  void
	 * @since   7.7.0
	 */
	private function restartTimer(): void
	{
		$this->startTime = microtime(true);
	}

	/**
	 * Tells us we have reached the timeout.
	 *
	 * @return  bool
	 * @since   7.7.0
	 */
	private function isTimeout(): bool
	{
		return microtime(true) - ($this->startTime ?? 0) > ($this->timeoutThreshold ?? 5);
	}
}