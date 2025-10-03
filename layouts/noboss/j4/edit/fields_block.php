<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/* Layout para exibir os campos de um fiedset por blocos
    - Para utilizaresse layout, voce precisa chamar o codigo abaixo dentro do foreach que percorre os fieldsets a exibir
        // Fieldset esta setado para ser exibido por blocos
        if((!empty($fieldSet->breakblock)) && ($fieldSet->breakblock == 1)){
            echo LayoutHelper::render('noboss.j4.edit.fields_block', array('form' => $this->form, 'fieldset' => $fieldSet, 'viewName' => $displayData->viewName));
            continue;
        }
    - Para funcionar corretamente a exibicao por blocos, alem de chamar o codigo acima, eh necessario:
        * Adicionar o atributo breakblock="1" na declaracao do fieldset no xml
        * Colocar no xml um campo note antes do primeiro campo de cada bloco (o label do note sera a legenda do bloco)
        * Colocar no xml um campo 'spacer' sempre que desejar fechar um bloco e iniciar outro
*/

// Inicia aba
echo HTMLHelper::_('uitab.addTab', $displayData['viewName'], $displayData['fieldset']->name, Text::_($displayData['fieldset']->label), true);

// Carrega description da aba (caso tenha)
echo LayoutHelper::render('noboss.edit.component_description', $displayData['fieldset']->description);

?>
<div class="row fields_block">
    <div class="col-12 col-lg-6">
        <fieldset class="options-form">
            <?php
            // Percorre os fields
            foreach ($displayData['form']->getFieldSet($displayData['fieldset']->name) as $field){
                // Field note: coloca o label como legenda do bloco
                if($field->getAttribute("type") == 'note'){
                    ?>
                    <legend>
                        <?php echo Text::_($field->getAttribute("label")); ?>
                    </legend>
                    <?php
                }
                // Field spacer: fecha bloco atual e abre novo
                else if($field->getAttribute("type") == 'spacer'){
                    ?>
                    </div>
                    </fieldset>
                    <div class="col-12 col-lg-6">
                    <fieldset class="options-form">
                    <?php
                }
                // Field normal: exibe o field
                else{
                    echo $field->renderField();
                }
            }
            ?>                           
        </fieldset>
    </div>
</div>
<?php

// Encerra aba
echo HTMLHelper::_('uitab.endTab');
