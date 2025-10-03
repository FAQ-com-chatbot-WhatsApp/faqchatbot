<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var $this \Akeeba\Component\AdminTools\Administrator\View\Webconfigmaker\HtmlView */

$viewName = $this->getName();
?>
<div class="alert alert-info mb-3">
	<h3 class="alert-heading">
		<?=Text::_('COM_ADMINTOOLS_' . $viewName . '_LBL_WILLTHISWORK'); ?>
	</h3>
	<p>
		<?php if($this->isSupported == 0): ?>
			<?=Text::_('COM_ADMINTOOLS_' . $viewName . '_LBL_WILLTHISWORK_NO'); ?>
		<?php elseif($this->isSupported == 1): ?>
			<?=Text::_('COM_ADMINTOOLS_' . $viewName . '_LBL_WILLTHISWORK_YES'); ?>
		<?php else: ?>
			<?=Text::_('COM_ADMINTOOLS_' . $viewName . '_LBL_WILLTHISWORK_MAYBE'); ?>
		<?php endif; ?>
	</p>
</div>

<div class="alert alert-warning text-dark mb-3">
	<h3 class="alert-heading">
		<?=Text::_('COM_ADMINTOOLS_' . $viewName . '_LBL_WARNING'); ?>
	</h3>

	<p><?=Text::_('COM_ADMINTOOLS_' . $viewName . '_LBL_WARNTEXT'); ?></p>

	<p><?=Text::_('COM_ADMINTOOLS_' . $viewName . '_LBL_TUNETEXT'); ?></p>
</div>

<form action="<?= Route::_('index.php?option=com_admintools&view=' . $viewName) ?>" id="adminForm" method="post" name="adminForm">

	<?php foreach ($this->form->getFieldsets() as $fieldset): ?>
	<div class="card mb-3">
		<h3 class="card-header bg-primary text-white">
			<?= Text::_($fieldset->label) ?>
		</h3>

		<div class="card-body">
			<?php if ($fieldset->description): ?>
			<div class="alert alert-info">
				<?= Text::_($fieldset->description) ?>
			</div>
			<?php endif; ?>

			<?php foreach ($this->form->getFieldset($fieldset->name) as $field) {
				echo $field->renderField();
			}
			?>
		</div>
	</div>
	<?php endforeach; ?>

	<input type="hidden" name="task" value="save" />
	<?= HTMLHelper::_('form.token') ?>
</form>
