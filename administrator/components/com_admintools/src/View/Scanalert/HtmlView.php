<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\View\Scanalert;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Model\ScanalertModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	/**
	 * File status
	 *
	 * @var  string
	 */
	public $fstatus = 'modified';

	/**
	 * Is this file suspicious?
	 *
	 * @var  bool
	 */
	public $suspiciousFile = false;

	/**
	 * The Form object
	 *
	 * @var    Form
	 * @since  1.5
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var    object
	 * @since  1.5
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var    object
	 * @since  1.5
	 */
	protected $state;

	/**
	 * Should I generate DIFFs for files?
	 *
	 * @var  bool
	 */
	protected $generateDiff;

	/**
	 * Size threshold for reading file contents. To calculate the score we have to read the whole file, with large ones
	 * (ie log files) we could run out of memory, causing a fatal error.
	 *
	 * @var int
	 */
	private $filesizeThreshold = 5242880;

	public function display($tpl = null): void
	{
		/** @var ScanalertModel $model */
		$model       = $this->getModel();
		$this->form  = $model->getForm();
		$this->item  = $model->getItem();
		$this->state = $model->getState();

		// Check for errors.
		if (method_exists($this->getModel(), 'getErrors'))
		{
			/** @noinspection PhpDeprecationInspection */
			$errors = $this->getModel()->getErrors();

			if (is_countable($errors) && count($errors))
			{
				throw new GenericDataException(implode("\n", $errors), 500);
			}
		}

		$this->item->newfile    = empty($this->item->diff);
		$this->item->suspicious = substr($this->item->diff, 0, 21) == '###SUSPICIOUS FILE###';

		$this->generateDiff = ComponentHelper::getParams('com_admintools')->get('scandiffs', false);

		// File status
		if ($this->item->newfile)
		{
			$this->fstatus = 'new';
		}
		elseif ($this->item->suspicious)
		{
			$this->fstatus = 'suspicious';
		}

		// Should I render a diff?
		if (!empty($this->item->diff))
		{
			$diffLines = explode("\n", $this->item->diff);
			$firstLine = array_shift($diffLines);

			if ($firstLine == '###SUSPICIOUS FILE###')
			{
				$this->suspiciousFile = true;
				$this->item->diff     = '';
			}
			elseif ($firstLine == '###MODIFIED FILE###')
			{
				$this->item->diff = '';
			}

			if ($this->suspiciousFile && (count($diffLines) > 4))
			{
				array_shift($diffLines);
				array_shift($diffLines);
				array_shift($diffLines);
				array_shift($diffLines);

				$this->item->diff = implode("\n", $diffLines);
			}

			unset($diffLines);
		}

		// Load highlight.js
		$this->getDocument()->getWebAssetManager()->registerAndUseScript(
			'com_admintools.dynamic.highlight_js',
			'//cdnjs.cloudflare.com/ajax/libs/highlight.js/10.5.0/highlight.min.js'
		);
		$this->getDocument()->getWebAssetManager()->registerAndUseStyle(
			'com_admintools.dynamic.highlight_js',
			'//cdnjs.cloudflare.com/ajax/libs/highlight.js/10.5.0/styles/default.min.css'
		);
		//$this->getDocument()->addScript('//cdnjs.cloudflare.com/ajax/libs/highlight.js/10.5.0/highlight.min.js');
		//$this->getDocument()->addStyleSheet('//cdnjs.cloudflare.com/ajax/libs/highlight.js/10.5.0/styles/default.min.css');
		//$this->getDocument()->addStyleSheet('//cdnjs.cloudflare.com/ajax/libs/highlight.js/10.5.0/styles/dracula.min.css', $this->container->mediaVersion, 'text/css', 'screen and (prefers-color-scheme: dark)');

		$js = <<< JS

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('pre.highlightCode').forEach(function (block) {
        hljs.highlightBlock(block);
    });
})

JS;

		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar(): void
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$isNew = empty($this->item->id);

		ToolbarHelper::title(Text::sprintf('COM_ADMINTOOLS_TITLE_SCANALERT_EDIT', $this->item->scan_id), 'icon-admintools');

		ToolbarHelper::apply('scanalert.apply');
		ToolbarHelper::save('scanalert.save');

		ToolbarHelper::cancel('scanalert.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');

		ToolbarHelper::help(null, false, 'https://www.akeeba.com/documentation/admin-tools-joomla/php-file-scanner-scan.html');
	}

	public function getFileSourceForDisplay($highlight = false)
	{
		$item = $this->item;

		if (!file_exists(JPATH_ROOT . '/' . $item->path))
		{
			return null;
		}

		$filepath = JPATH_ROOT . '/' . $item->path;
		$filesize = @filesize($filepath);

		// With very large files do not display the whole contents, but instead show a placeholder
		if ($filesize > $this->filesizeThreshold)
		{
			return Text::sprintf('COM_ADMINTOOLS_SCANS_ERR_FILE_TOO_LARGE', round($filesize / 1024 / 1024, 2));
		}

		$filedata = @file_get_contents($filepath);

		if (!$highlight)
		{
			return htmlentities($filedata);
		}

		$highlightPrefixSuspicious = "%*!*[[###  ";
		$highlightSuffixSuspicious = "  ###]]*!*%";
		$highlightPrefixKnownHack  = "%*{{!}}*[[###  ";
		$highlightSuffixKnownHack  = "  ###]]*{{!}}*%";

		/** @var string $encodedConfig Defined in the included file */
		require_once JPATH_COMPONENT_ADMINISTRATOR . '/src/Scanner/encodedconfig.php';

		$zipped = pack('H*', $encodedConfig);
		unset($encodedConfig);

		$json_encoded = gzinflate($zipped);
		unset($zipped);

		$new_list = json_decode($json_encoded, true);
		extract($new_list);

		unset($new_list);

		/** @var array $suspiciousWords Simple array of words that are suspicious */
		/** @var array $knownHackSignatures Known hack signatures, $signature => $weight */
		/** @var array $suspiciousRegEx Suspicious constructs' RegEx, $regex => $weight */

		foreach ($suspiciousWords as $word)
		{
			$replacement = $highlightPrefixSuspicious . $word . $highlightSuffixSuspicious;
			$filedata    = str_replace($word, $replacement, $filedata);
		}

		foreach ($knownHackSignatures as $signature => $sigscore)
		{
			$replacement = $highlightPrefixKnownHack . $signature . $highlightSuffixKnownHack;
			$filedata    = str_replace($signature, $replacement, $filedata);
		}

		$i = 0;

		foreach ($suspiciousRegEx as $pattern => $value)
		{
			$i++;
			$count = preg_match_all($pattern, $filedata, $matches);

			if (!$count)
			{
				continue;
			}

			$filedata = preg_replace_callback($pattern, function ($m) use ($highlightPrefixSuspicious, $highlightSuffixSuspicious, $i) {
				return $highlightPrefixSuspicious . $m[0] . $highlightSuffixSuspicious;
			}, $filedata);
		}

		$filedata = htmlentities($filedata);

		$filedata = str_replace([
			$highlightPrefixSuspicious,
			$highlightSuffixSuspicious,
		], [
			'<mark class="bg-warning text-black fw-bold px-2 rounded-pill">',
			'</mark>',
		], $filedata);

		$filedata = str_replace([
			$highlightPrefixKnownHack,
			$highlightSuffixKnownHack,
		], [
			'<mark class="bg-danger text-white fw-bold px-2 rounded-pill">',
			'</mark>',
		], $filedata);

		return $filedata;
	}

}
