<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form   $tmpl             The Empty form for template
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

// Adiciona JS e CSS para customizacao do subform
$app = Factory::getApplication();
$wa = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseScript('nobosssubform', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobosssubform.min.js");
$wa->registerAndUseStyle('nobosssubform', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobosssubform.min.css");

$sublayout = empty($groupByFieldset) ? 'section' : 'section-byfieldsets';
$htmlButtons = $dataAttributes['htmlButtons'];
$colsClass = $displayData['field']->cols_class;

// Alias do campo que tera valor exibido no collapse
$identifier = isset($dataAttributes['identifier']) ? $dataAttributes['identifier'] : '';

// Alias do campo que tera valor caminho de imagem exibido no collapse
$fieldimage = isset($dataAttributes['fieldimage']) ? $dataAttributes['fieldimage'] : '';
?>

<div class="subform-repeatable-wrapper subform-layout" data-subform-collapse-wrapper>
	<joomla-field-subform class="subform-repeatable" name="<?php echo $name; ?>"
        button-add=".group-add" 
        button-remove=".group-remove" 
        button-move="<?php echo empty($buttons['move']) ? '' : '.group-move' ?>"
        repeatable-element=".subform-repeatable-group" 
        minimum="<?php echo $min; ?>" 
        maximum="<?php echo $max; ?>">

        <?php
        if (!empty($buttons['add'])) :
        ?>
        <div class="btn-toolbar">
            <div class="btn-group">
                <a class="group-add btn button btn-success" aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>" tabindex="0">
                    <span class="icon-plus icon-white" aria-hidden="true"></span>
                </a>
                <?php echo $htmlButtons; ?>
            </div>
        </div>
        <?php
        endif; 

        $fieldIsEditor = false;
        foreach ($forms as $k => $form) :

            $formData = $form->getData();
            $formDataArray = $formData->toArray();

            // Campo para exibir no collapase nao definido e tb nao tem imagem definida: busca o primeiro field de texto do subform
            if(empty($identifier) && !$formData->exists($identifier) && empty($fieldimage)){
                // Obtem todos fields

                $fieldset = $form->getFieldset();
                
                // Filtra os campos que sao text, textarea, editor, NbCustomEditor
                $defaultField = array_filter($fieldset, function($field){
                    if(strtolower($field->type) == 'text' || strtolower($field->type) == 'textarea' || strtolower($field->type) == 'nobosseditor' || $field->type == 'Editor' || $field->type == 'NbCustomEditor' || $field->type == 'NbEditor'){
                        return $field;
                    }
                });

                // Pega o name do primeiro campo filtrado
                $identifier = reset($defaultField)->getAttribute('name');
            }
            
            // Identificador definido
            if(!empty($identifier)){
                // Verifica se eh do tipo 'editor' para informar que html deve ser limpo no JS
                $fieldIsEditor = (($form->getField($identifier)->type === 'nobosseditor') || ($form->getField($identifier)->type === 'NbEditor') || ($form->getField($identifier)->type === 'Editor'));
            }          
            
            echo $this->sublayout($sublayout, array('form' => $form, 'basegroup' => $fieldname, 'group' => $fieldname . $k, 'buttons' => $buttons, 'colsclass' => $colsClass));
        endforeach;
        
        if ($multiple) :
        ?>
            <template class="subform-repeatable-template-section">
                <?php
                echo trim($this->sublayout($sublayout, array('form' => $tmpl, 'basegroup' => $fieldname, 'group' => $fieldname . 'X', 'buttons' => $buttons, 'colsclass' => $colsClass)));
                ?>
            </template>
		<?php
        endif;
        ?>
	</joomla-field-subform>
    
    <span class='hidden' data-is-editor="<?php echo $fieldIsEditor?>" data-collapse-label='<?php echo $identifier;?>'  data-collapse-image='<?php echo $fieldimage;?>' data-collapse-default-value='<?php echo Text::_('LIB_NOBOSS_FIELD_NOBOSSSUBFORM_COLLAPSE_DEFAULT_VALUE_TEXT'); ?>'></span>
</div>
