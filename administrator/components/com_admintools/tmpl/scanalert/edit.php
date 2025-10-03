<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Component\AdminTools\Administrator\View\Scanalert\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');

?>
<form action="<?php echo Route::_('index.php?option=com_admintools&view=scanalert&layout=edit&id=' . $this->item->id); ?>"
      aria-label="<?= Text::_('COM_ADMINTOOLS_TITLE_BADWORD_EDIT', true) ?>"
      class="form-validate" id="tempsuperuser-form" method="post" name="adminForm">

	<div class="card card-body mb-2">
		<?php foreach ($this->form->getFieldset('details') as $field): ?>
			<?= $field->renderField(); ?>
		<?php endforeach; ?>
	</div>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

<div class="card card-body mb-2">
	<?= HTMLHelper::_('uitab.startTabSet', 'com_admintools_scanalert') ?>

	<?php if($this->generateDiff && ($this->fstatus == 'modified')): ?>
	<?= HTMLHelper::_('uitab.addTab', 'com_admintools_scanalert', 'diff', Text::_('COM_ADMINTOOLS_SCANALERTS_LBL_DIFF')) ?>
	<pre class="highlightCode <?= $this->item->suspicious ? 'php' : 'diff'; ?>"><?= $this->item->diff ?></pre>
	<?= HTMLHelper::_('uitab.endTab') ?>
	<?php endif; ?>

	<?= HTMLHelper::_('uitab.addTab', 'com_admintools_scanalert', 'source', Text::_('COM_ADMINTOOLS_SCANALERTS_LBL_SOURCE')) ?>

	<?php if(!@file_exists(JPATH_SITE . '/' . $this->item->path) && !$this->generateDiff): ?>
		<div class="alert alert-danger">
			<?=Text::sprintf('COM_ADMINTOOLS_SCANALERTS_LBL_FILENOTFOUND', $this->item->path); ?>
		</div>
	<?php else: ?>
		<div class="alert alert-warning">
			<?=Text::_('COM_ADMINTOOLS_SCANALERTS_LBL_SOURCE_NOTE'); ?>
		</div>

		<div class="row mb-3 alert alert-info">
			<label class="col-md-3">
				<?=Text::_('COM_ADMINTOOLS_SCANALERTS_LBL_MD5'); ?>
			</label>

			<div class="col-md-9">
				<?= @hash_file('md5', JPATH_SITE . '/' . $this->item->path) ?>
			</div>
		</div>

		<pre class="highlightCode language-php"><?=$this->getFileSourceForDisplay(true); ?></pre>
	<?php endif; ?>


	<?= HTMLHelper::_('uitab.endTab') ?>

	<?= HTMLHelper::_('uitab.endTabSet') ?>
</div>