<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Util\NbUrlUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

// Pega uma listagem de temas
$themes = $this->getThemes();
?>

<div data-id="theme-modal"   class="noboss-modal noboss-modal--theme modal-wrapper fade in hidden" tabindex="-1" role="dialog">
	<div class="nb-modal-dialog">
		<div class="nb-modal-content">
            <div class="nb-modal-header">
                <h2><?php echo Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_MODAL_TITLE'); ?></h2>
                <a href="#" data-id="theme-modal-button-cancel" class="btn btn-close buttons">×</a>
            </div>
			<div class="nb-modal-body" style="overflow-y: scroll;">
            <div class="notification hidden alert alert-info" data-id="notification">
            </div>
                <div class="wrapper">
                    <div class="nbsidebar-wrapper show">
                        <div class="nbsidebar" data-id="theme-list">
                            <?php 
                            // Percorre todos modelos de temas a exibir
                            foreach ($themes as $key => $theme) {
                            ?>
                                <div class="<?php echo $theme->class; ?> theme-option" data-id="theme-option" data-plan="<?php echo (!empty($theme->plan)) ? implode(',', $theme->plan) : ''; ?>" data-value="<?php echo $theme->value; ?>"> 
                                    <div class="theme-name grow" data-target="<?php echo '.sample-list--'.$theme->value ?>">
                                        <p><?php echo $theme->text; ?></p>

                                        <i class="fas fa fa-angle-down"></i>

                                    </div>
                                    <ul data-id="sample-list" class="<?php echo 'sample-list--'.$theme->value?> sample-list sample-list--<?php echo $theme->columns == 1 ? "column":"grid"; ?>" style="">
                                        <?php 
                                        if(($theme->value == $jsonValue->theme) && ($jsonValue->sample->id != $theme->samples[0]->id)){
                                            $newSample = new \stdClass();
                                            $newSample->id = $jsonValue->sample->id;
                                            array_push($theme->samples, $newSample);
                                        }

                                        // Percorre todos exemplos a listar do tema atual
                                        foreach ($theme->samples as $sample) {
                                            $selected = $jsonValue->sample->id == $sample->id;?>
                                            <li data-id="sample-option" class="sample-option <?php echo $selected ? 'selected' : ''; ?>" data-value="<?php echo $sample->id; ?>" >
                                                <div class="image-wrapper">
                                                    <span class="theme-name--mobile"><?php echo $theme->text; ?></span>
                                                    <img class="sample-img" src="<?php echo $selected ? "{$jsonValue->sample->img}" : Text::sprintf('LIB_NOBOSS_FIELD_NOBOSSTHEME_MODAL_DEFAULT_IMAGE_SRC', Uri::root()); ?>" />
                                                    <i class="fas fa fa-lock"></i>
                                                </div>
                                            </li>
                                        <?php
                                        }
                                        ?>
                                    </ul>
                                </div>
                            <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="selected-theme" data-id="selected-theme">
                        <div class="image-wrapper">
                            <?php // Tag para exibicao da imagem grande selecionada (inserida via JS) ?>
                            <img data-id="selected-theme-img" data-theme="<?php echo $jsonValue->theme; ?>" data-sample="<?php echo $jsonValue->sample->id; ?>" src="<?php echo $jsonValue->sample->img; ?>" />
                            
                            <?php // Tag para exibicao de legenda inserida via JS ?>
                            <legend></legend>
                        </div>
                    </div>
                </div>
			</div>
			<div class="nb-modal-footer">
                <div class="nb-modal-footer__msg">
                    <?php
                    // Definido alias de name da extensao: exibe mensagem com link da pagina de demo
                    if(!empty($this->rawExtensionName)){
                        $urlDemo = NbUrlUtil::getUrlNbExtensions()."/".$this->rawExtensionName."/demo/";
                        echo Text::sprintf('LIB_NOBOSS_FIELD_NOBOSSTHEME_MSG_PAGE_DEMO', $urlDemo);
                    }
                    ?>
                </div>

                <div class="nb-modal-footer__btns">
                    <a href="#" data-id="theme-modal-button-cancel" class="btn btn-nb btn-primary"><?php echo Text::_("LIB_NOBOSS_FIELD_NOBOSSTHEME_MODAL_CANCEL_BUTTON"); ?></a>
                    <a href="#" data-id="theme-modal-button-confirm" class="btn btn-nb btn-primary buttons"><?php echo Text::_("LIB_NOBOSS_FIELD_NOBOSSTHEME_MODAL_CONFIRM_BUTTON"); ?></a>
                </div>
			</div>
		</div>
	</div>
</div>
