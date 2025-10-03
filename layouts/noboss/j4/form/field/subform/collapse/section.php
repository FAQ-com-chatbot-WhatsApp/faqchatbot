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
 * @var	  string  $colsclass  Classes para definicao das colunas de exibicao
 */
?>

<div
	class="subform-repeatable-group"
    data-collapse='grow'
    data-base-name="<?php echo $basegroup; ?>"
	data-group="<?php echo $group; ?>"
>
    <div class="noboss-collapse" data-noboss-collapse>
        <div class="noboss-collapse--wrapper">
			<i class="noboss-collapse__icon fa-solid fa-angle-down"></i>			
            <p class="noboss-collapse__title"></p>
        </div>
		<div class="btn-toolbar text-right">
			<?php if (!empty($buttons)) : ?>
				<div class="btn-group">
					<?php if (!empty($buttons['remove'])) : ?>
						<a data-bt="remove-item" class="group-remove btn btn-sm button btn-danger" aria-label="<?php echo Text::_('JGLOBAL_FIELD_REMOVE'); ?>" tabindex="0">
							<span class="icon-minus" aria-hidden="true"></span> 
						</a>
					<?php endif; ?>
					<?php if (!empty($buttons['add'])) : ?>
						<a class="group-add btn btn-sm button btn-success" aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>" tabindex="0">
							<span class="icon-plus" aria-hidden="true"></span> 
						</a>
					<?php endif; ?>
					<?php if (!empty($buttons['duplicate'])) : ?>
						<a data-bt="duplicate-item" class="btn btn-mini btn-duplicate">
							<span class="icon-copy" aria-hidden="true"></span>
						</a>
					<?php endif; ?>
					<?php if (!empty($buttons['move'])) : ?>
						<a class="group-move btn btn-sm button btn-move" aria-label="<?php echo Text::_('JGLOBAL_FIELD_MOVE'); ?>">
						<span class="icon-arrows-alt" aria-hidden="true"></span> 
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
    </div>	

	<div class="nb-suform__fields form-grid <?php echo $colsclass; ?>">
		<?php foreach ($form->getGroup('') as $field) : ?>
			<?php echo $field->renderField(); ?>
		<?php endforeach; ?>
	</div>
</div>
