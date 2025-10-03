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

/** @var $this \Akeeba\Component\AdminTools\Administrator\View\Configurewaf\HtmlView */

$viewName = $this->getName();
?>

<form action="<?= Route::_('index.php?option=com_admintools&view=' . $viewName) ?>" id="adminForm" method="post" name="adminForm">

	<?= HTMLHelper::_('uitab.startTabSet', 'com_admintools_configurewaf', ['active' => 'details']); ?>
	<?php foreach ($this->form->getFieldsets() as $fieldset): ?>
		<?= HTMLHelper::_('uitab.addTab', 'com_admintools_configurewaf', $fieldset->name, Text::_($fieldset->label)); ?>
		<div class="card card-body mb-3">
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
		<?= HTMLHelper::_('uitab.endTab'); ?>
	<?php endforeach; ?>

	<?= HTMLHelper::_('uitab.endTabSet'); ?>

	<input type="hidden" name="task" value="save" />
	<?= HTMLHelper::_('form.token') ?>
</form>
