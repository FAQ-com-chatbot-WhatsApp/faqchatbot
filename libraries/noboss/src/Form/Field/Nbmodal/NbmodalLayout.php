<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$doc = Factory::getApplication()->getDocument();

$modalLabel = $form->getAttribute('label');
$buttonConfirm = $form->getAttribute('buttonconfirm');
$buttonCancel = $form->getAttribute('buttoncancel');
$buttonReset = $form->getAttribute('buttonreset');
$description = $form->getAttribute('description');
$class = $form->getAttribute('class');

$buttonConfirmModal = empty($buttonConfirm) ? Text::_("LIB_NOBOSS_FIELD_NOBOSSMODAL_BUTTON_CONFIRM_DEFAULT_LABEL") : Text::_($buttonConfirm);
$buttonCancelModal = empty($buttonCancel) ? Text::_("LIB_NOBOSS_FIELD_NOBOSSMODAL_BUTTON_CANCEL_DEFAULT_LABEL") : Text::_($buttonCancel);
$buttonResetModal = empty($buttonReset) ? Text::_("LIB_NOBOSS_FIELD_NOBOSSMODAL_BUTTON_RESET_DEFAULT_LABEL") : Text::_($buttonReset);
$firstFieldset = reset($fieldsets);

?>

<div data-id="modal" class="noboss-modal noboss-modal--tabs modal-wrapper nb-fade in nb-hidden" tabindex="-1" role="dialog" data-modal-name="<?php echo $form->getName(); ?>" >
	<div class="nb-modal-dialog">
		<div class="nb-modal-content">
			<?php if(!empty($modalLabel)){ ?>
				<div class="nb-modal-header">
					<h2><?php echo Text::_($modalLabel); ?></h2>
					<a href="#" data-id="button-cancel" class="btn btn-close buttons">×</a>
					<?php if(!empty($description)) { ?>
						<p><?php echo Text::_($description); ?></p>
					<?php } ?>
				</div>
            <?php } ?>
            <?php if(!empty($blockModal)){ ?>
                <div class='alert' style='margin-bottom: 10px;'><span class='icon-minus-circle'> </span>
                    <?php
                    echo Text::_("LIB_NOBOSS_BLOCK_FIELD_MODAL_COMPLETE_HEADER");
                    ?>
                </div>
            <?php } ?>
            <?php 
            // Somente exibe abas se tiver mais de uma definda
            if(count($fieldsets) > 1) {?>
                <joomla-tab id="myTab" orientation="horizontal" recall="" view="tabs">                    
                    <div role="tablist" data-id="modal-nav-tabs">
                        <?php 
                        // Percorre as abas a exibir
                        foreach ($fieldsets as $fieldKey => $fieldsetItem) { 
                        ?>
                            <button aria-controls="<?php echo $fieldKey; ?>" role="tab" type="button" data-id="modal-tab" <?php echo $fieldsetItem->label == $firstFieldset->label ? 'aria-expanded="true"' : ''; ?>>
                                <?php echo empty($fieldsetItem->label) ? Text::_('LIB_NOBOSS_FIELD_NOBOSSMODAL_UNDEFINED_TAB_LABEL') : Text::_($fieldsetItem->label); ?>
                            </button>
                        <?php
                        }
                        ?>
                    </div>
                    <?php
                    // Adiciona 'sections' para cada fieldset para nao dar conflito de JS no arquivo de tabs do Joomla 4
                    foreach ($fieldsets as $fieldKey => $fieldsetItem){
                        ?>
                        <section id="<?php echo $fieldKey;?>" aria-labelledby="tab-<?php echo $fieldKey;?>" role="tabpanel" active="" style="display:none;"></section>
                        <?php
                    }
                    ?>
                </joomla-tab>
            <?php 
            } ?>
			<div class="nb-modal-body" style="overflow-y: scroll;">
				<form name="nb-modal-form" data-id="nb-modal-form" >
                    <?php 
                    // Percorre os fieldsets
                    foreach ($fieldsets as $fieldTabKey => $fieldsetTapPane){ ?>
                        <div 
                            data-tab-id="<?php echo $fieldTabKey; ?>" 
                            class="fieldset modal-tab-pane form-grid <?php echo $fieldsetTapPane->label == $firstFieldset->label ? 'active' : ''; ?> <?php echo (!empty($fieldsetTapPane->cols_class)) ? $fieldsetTapPane->cols_class : ''; ?>"
                            <?php echo isset($fieldsetTapPane->nbshowon) ? "data-nbshowon='{$fieldsetTapPane->nbshowon}'" : ""; ?> >
                                <?php 
                                // Percorre os campos do fieldset
                                foreach ($form->getFieldset($fieldTabKey) as $field) {
                                    // Parentclass nao definido e field nao esta na lista de tipos a ignorar
                                    if((empty($field->getAttribute('parentclass')) && (!in_array($field->getAttribute('type'), array('note', 'spacer', 'nobossapiconnection'))))){
                                        // Seta um parent default
                                        $form->setFieldAttribute($field->getAttribute('name'), "parentclass", "stack span-3-inline reduce-br");
                                        // Renderiza field pelo nome
                                        echo $form->renderField($field->getAttribute('name'));
                                        continue;
                                    }

                                    // Renderiza field normalmente
                                    echo $field->renderField();
                                }
                                ?>
						</div>
                    <?php 
                    } ?>
				</form>
            </div>
			<div class="nb-modal-footer">
                <?php if(empty($form->getAttribute('bntcancelhidden'))){ ?>
                    <a href="#" data-id="button-cancel" class="btn btn-nb btn-primary"><?php echo $buttonCancelModal; ?></a>
                <?php } ?>
                <?php if(empty($form->getAttribute('bntconfirmhidden'))){ ?>
				    <a href="#" data-id="button-confirm" <?php echo ($blockModal) ? 'disabled' : ''; ?> class="btn btn-nb btn-primary"><?php echo $buttonConfirmModal; ?></a>
                <?php } ?>
                <?php if(empty($form->getAttribute('bntresethidden'))){ ?>
                    <a href="#" data-id="button-reset" <?php echo ($blockModal) ? 'disabled' : ''; ?> class="btn btn-reset btn-nb btn-primary"><?php echo $buttonResetModal; ?></a>
                <?php } ?>
			</div>
		</div>
	</div>
</div>

<?php

// Abaixo inicia a organizacao das urls de CSS e JS que devem ser carregados na pagina

// Obtem dados do head
$dataHead = $doc->getHeadData();

$stylesUrls = array();
// Existem url de scripts (formato J3, mas que ainda funciona no J4)
if(!empty($dataHead['styleSheets'])){
    foreach ($dataHead['styleSheets'] as $key => $value) {
        $script = new \stdClass();
        $script->url = $key;
        $stylesUrls[] = $script;
    }
}

// Existem scripts definidos no formato novo do J4 (inline e url)
if (!empty($dataHead['assetManager']['assets']['style'])){
    // Percorre todos styles
    foreach ($dataHead['assetManager']['assets']['style'] as $asset){
        $options = $asset->getOptions();
        // Eh inline
        if(!empty($options['inline']) && $options['inline']){
        }
        // Eh url
        else{
            $script = new \stdClass();
            $script->url = $asset->getUri();
            if(!empty($version = $asset->getVersion())){
                $script->url .= "?{$version}";
            }
            $stylesUrls[] = $script;
        }
    }
}

$scriptsUrls = array();

// Existem url de scripts (formato J3, mas que ainda funciona no J4)
if(!empty($dataHead['scripts'])){
    foreach ($dataHead['scripts'] as $key => $value) {
        $script = new \stdClass();
        $script->url = $key;
        $script->module = '';
        $script->defer = '';
        $scriptsUrls[] = $script;
    }
}

// Existem scripts definidos no formato novo do J4 (inline e url)
if (!empty($dataHead['assetManager']['assets']['script'])){
    // Percorre todos scripts
    foreach ($dataHead['assetManager']['assets']['script'] as $asset){
        $options = $asset->getOptions();
        
        // Eh inline
        if(!empty($options['inline']) && $options['inline']){
        }
        // Eh url
        else{
            $attributes = $asset->getattributes();
            $script = new \stdClass();
            $script->url = $asset->getUri();
            if(!empty($version = $asset->getVersion())){
                $script->url .= "?{$version}";
            }
            
            // Eh do type 'module'
            if ((!empty($attributes['type'])) && ($attributes['type'] == 'module')){
                $script->module = 'type="module"';
            }
            else{
                $script->module = '';
            }

            // Eh defer
            if (!empty($attributes['defer'])){
                $script->defer = 'defer';
            }
            else{
                $script->defer = '';
            }

            $scriptsUrls[] = $script;
        }
    }
}

// Carrega na pagina os options que poderao ser acessados posteriormente pelo Joomla usando a funcao Joomla.getOptions
$scriptOptions = $doc->getScriptOptions();
if ($scriptOptions){
    $prettyPrint = (JDEBUG && \defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : false);
    $jsonOptions = json_encode($scriptOptions, $prettyPrint);
    $jsonOptions = $jsonOptions ? $jsonOptions : '{}';
    echo '<script type="application/json" class="joomla-script-options new">'.$jsonOptions.'</script>';
}

?>
<script>
    <?php // Faz com que o Joomla renderize os options inseridos na pagina ?>
    Joomla.loadOptions();
    <?php // Enfileira os arquivos a serem carregados depois pelo nobossmodal.js ?>
	nobossmodal.loadJs = <?php echo json_encode($scriptsUrls); ?>;
    nobossmodal.loadCss = <?php echo json_encode($stylesUrls); ?>;
</script>
