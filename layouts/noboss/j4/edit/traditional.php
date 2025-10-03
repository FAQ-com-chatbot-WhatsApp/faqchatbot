<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$doc = Factory::getDocument();

// Obtem o formulario
$form = $displayData->getForm();

// Scripts de validacao do form
$wa = $doc->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');
?>

<form name="<?php echo "form-" . $displayData->viewName; ?>" id="<?php echo "form-" . $displayData->viewName; ?>" class="form-validate" method="post" action="<?php echo Route::_($displayData->actionForm); ?>" enctype="multipart/form-data">

    <?php
    // Exibe campos de titulo e alias
    echo LayoutHelper::render('noboss.edit.title_alias', $displayData);
    ?>

    <div class="main-card">
		<?php
        echo HTMLHelper::_('uitab.startTabSet', $displayData->viewName, ['recall' => true, 'breakpoint' => 768]);
        
        // FIXME: verificar se precisamos ainda disso com novo modelo de ignore_fieldsets
        // Renderiza os campos que nao devem ser exibidos e que estao no fieldset 'hidden'
        echo '<div style="display: none;">'.$form->renderFieldset('hidden').'</div>';

        // Percorre todos fieldsets
        foreach ($form->getFieldsets() as $fieldSet):
            // FIXME: verificar como joomla agora elimita o que ta em ignore_fieldsets
            // Fieldset esta entre os a serem ignorados na exibicao automatica
            if(in_array($fieldSet->name, $displayData->ignore_fieldsets)){
                continue;
            }

            // Fieldset esta setado para ser exibido por blocos
            if((!empty($fieldSet->breakblock)) && ($fieldSet->breakblock == 1)){
                //FIXME: estudar o que muda aqui
                echo LayoutHelper::render('noboss.j4.edit.fields_block', array('form' => $form, 'fieldset' => $fieldSet, 'viewName' => $displayData->viewName));
                continue;
            }

            // Inicia aba
            echo HTMLHelper::_('uitab.addTab', $displayData->viewName, $fieldSet->name, Text::_($fieldSet->label), true);
            ?>
            <div class="row">
                <?php // Se for a aba default, declara classe col-lg-9 p/ poder exibir coluna de detalhes na direita. Demais casos declara como col-lg-12 que ocupa toda largura ?>
                <div class="col-lg-<?php echo ((!empty($displayData->defaultFieldSets) && in_array($fieldSet->name, $displayData->defaultFieldSets)) && (!empty($form->getFieldSet('details')))) ? '9' : '12'; ?>">
		            <fieldset class="form-grid">
                        <?php
                        // Carrega description da aba (caso tenha)
                        //FIXME: testar se funciona ainda no novo modelo
                        echo LayoutHelper::render('noboss.edit.component_description', $fieldSet->description);
                        
                        // Percorre cada field
                        foreach ($form->getFieldSet($fieldSet->name) as $field){

                            // Eh field de 'Atribuicao de menus' de modulo
                            if(!empty($field->getAttribute("module"))){
                                // Field de selecao de menus
                                if($field->getAttribute("module") == 'assigment'){
                                    // Obtem o nome do campo p/ usar em seguida no proximo field
                                    $assignmentName = $field->getAttribute("name");
                                    continue;
                                }
                                // Field que guarda os menus selecionados
                                elseif($field->getAttribute("module") == 'assigned'){
                                    // Obtem o nome do campo
                                    $assignedName = $field->getAttribute("name");
                                    $item = $displayData->get('Item');
                                    // Carrega arquivo que ira exibir o campo
                                    require JPATH_LIBRARIES."/noboss/src/Form/Field/Nbmodulesposition/assignment.php";
                                    continue;
                                }
                            }

                            // Carrega o campo
                            echo $field->renderField();
                        }
                        ?>
                    </fieldset>
	            </div>
                <?php
                // Aba default: carrega coluna na direita com campos de detalhes
                if ((!empty($displayData->defaultFieldSets) && in_array($fieldSet->name, $displayData->defaultFieldSets)) && (!empty($form->getFieldSet('details')))){
                    ?>
                    <div class="col-lg-3">
                        <fieldset class="form-vertical">
                            <?php
                            // Carrega campos do fieldset details
                            echo $form->renderFieldset('details');
                            ?>
                        </fieldset>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php
            // Encerra aba
            echo HTMLHelper::_('uitab.endTab');

        endforeach;
             
        echo HTMLHelper::_('uitab.endTabSet'); 
        ?>

        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>
