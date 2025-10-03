<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

use Joomla\CMS\Factory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   JForm   $tmpl             The Empty form for template
 * @var   array   $forms            Array of JForm instances for render the rows
 * @var   bool    $multiple         The multiple state for the form field
 * @var   int     $min              Count of minimum repeating in multiple mode
 * @var   int     $max              Count of maximum repeating in multiple mode
 * @var   string  $name             Name of the input field.
 * @var   string  $fieldname        The field name
 * @var   string  $control          The forms control
 * @var   string  $label            The field label
 * @var   string  $description      The field description
 * @var   array   $buttons          Array of the buttons that will be rendered
 * @var   bool    $groupByFieldset  Whether group the subform fields by it`s fieldset
 */

// Add script
if ($multiple)
{
	Factory::getDocument()->getWebAssetManager()
		->useScript('webcomponent.field-subform');
}

$sublayout = 'section';

?>

<div class="subform-repeatable-wrapper subform-layout" data-subform-single-wrapper>
    <joomla-field-subform class="subform-repeatable" name="<?php echo $name; ?>">
        <?php
        foreach ($forms as $k => $form) :
			echo $this->sublayout($sublayout, array('form' => $form, 'basegroup' => $fieldname, 'group' => $fieldname . $k, 'buttons' => $buttons)); 
        endforeach;
		?>
    </joomla-field-subform>
</div>
