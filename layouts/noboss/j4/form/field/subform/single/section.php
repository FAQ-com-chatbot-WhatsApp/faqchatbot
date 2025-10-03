<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

?>

<div class="noboss-subform-single form-grid" data-base-name="<?php echo $basegroup; ?>" data-group="<?php echo $group; ?>">
<?php foreach ($form->getGroup('') as $field) : ?>
	<?php echo $field->renderField(); ?>
<?php endforeach; ?>
</div>

