<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form   $form       The form instance for render the section
 * @var   string  $basegroup  The base group name
 * @var   string  $group      Current group name
 * @var   array   $buttons    Array of the buttons that will be rendered
 */
?>

<div
	class="subform-repeatable-group shrink grow"
	data-base-name="<?php echo $basegroup; ?>"
	data-group="<?php echo $group; ?>"
>
    <div class="noboss-collapse" data-noboss-collapse>
        <div class="noboss-collapse--wrapper">
			<i class="noboss-collapse__icon rotate-icon fa-solid fa-angle-down"></i>
            <p class="noboss-collapse__title"></p>
        </div>
    </div>
    <?php if (!empty($buttons)) : ?>
	<div class="btn-toolbar text-right">
		<div class="btn-group">
			<?php if (!empty($buttons['remove'])) : ?>
				<a data-bt="remove-item" class="group-remove btn btn-sm button btn-danger" aria-label="<?php echo Text::_('JGLOBAL_FIELD_REMOVE'); ?>" tabindex="0">
					<span class="icon-minus icon-white" aria-hidden="true"></span> 
				</a>
			<?php endif; ?>

			<?php if (!empty($buttons['add'])) : ?>
				<a class="group-add btn btn-sm button btn-success" aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>" tabindex="0">
					<span class="icon-plus icon-white" aria-hidden="true"></span> 
				</a>
			<?php endif; ?>
			<?php if (!empty($buttons['move'])) : ?>
				<a class="group-move btn btn-sm button btn-primary" aria-label="<?php echo Text::_('JGLOBAL_FIELD_MOVE'); ?>">
				<span class="icon-arrows-alt icon-white" aria-hidden="true"></span> 
				</a>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>
	<div class="row">
		<?php foreach ($form->getFieldsets() as $fieldset) : ?>
		<fieldset class="<?php if (!empty($fieldset->class)){ echo $fieldset->class; } ?>">
			<?php if (!empty($fieldset->label)) : ?>
				<legend><?php echo Text::_($fieldset->label); ?></legend>
			<?php endif; ?>
			<?php foreach ($form->getFieldset($fieldset->name) as $field) : ?>
				<?php echo $field->renderField(); ?>
			<?php endforeach; ?>
		</fieldset>
		<?php endforeach; ?>
	</div>
</div>
