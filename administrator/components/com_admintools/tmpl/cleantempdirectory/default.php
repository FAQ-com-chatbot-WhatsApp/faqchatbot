<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;

/** @var $this \Akeeba\Component\AdminTools\Administrator\View\Cleantempdirectory\HtmlView */

?>
<div class="card">
	<h3 class="card-header <?= $this->more ? 'bg-primary' : 'bg-success' ?> text-white">
		<?php if ($this->more): ?>
			<?= Text::_('COM_ADMINTOOLS_CLEANTEMPDIRECTORY_LBL_CLEANTMPINPROGRESS') ?>
		<?php else: ?>
			<?= Text::_('COM_ADMINTOOLS_CLEANTEMPDIRECTORY_LBL_CLEANTMPDONE') ?>
		<?php endif ?>
	</h3>
	<div class="card-body">
		<?php if ($this->more): ?>
			<p>
				<?= Text::_('COM_ADMINTOOLS_CLEANTEMPDIRECTORY_LBL_CLEANTMPINPROGRESS_WAIT') ?>
			</p>
		<?php else: ?>
			<div class="alert alert-info mb-3" id="admintools-cleantmp-autoclose">
				<p><?= Text::_('COM_ADMINTOOLS_COMMON_LBL_AUTOCLOSEIN3S') ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>