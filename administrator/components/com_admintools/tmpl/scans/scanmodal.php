<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var \Akeeba\Component\AdminTools\Administrator\View\Scans\HtmlView $this */

HTMLHelper::_('bootstrap.modal', '.admintoolsModal', [
	'backdrop' => 'static',
	'keyboard' => true,
	'focus'    => true,
]);

?>
<div id="scanModal"
     class="modal"
     role="dialog"
     tabindex="-1"
     aria-labelledby="akeeba-admintools-scan-title"
     aria-hidden="true"
>
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="akeeba-admintools-scan-title">
					<?= Text::_('COM_ADMINTOOLS_SCAN_LBL_MSG_PLEASEWAIT') ?>
				</h3>
			</div>
			<div class="modal-body p-5">
				<p>
					<?=Text::_('COM_ADMINTOOLS_SCAN_LBL_MSG_SCANINPROGRESS'); ?>
				</p>
				<p>
					<span id="admintools-lastupdate-text" class="lastupdate"></span>
				</p>
			</div>
		</div>
	</div>
</div>
