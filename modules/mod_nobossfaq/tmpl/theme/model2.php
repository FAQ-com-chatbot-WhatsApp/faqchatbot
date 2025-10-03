<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss FAQ
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2018 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined("_JEXEC") or die;

?>
<section 
    class='<?php echo "{$module->name} {$module->name}--{$theme}"; ?> <?php echo !$externalArea->content_display_mode ? 'nb-full-width' : ''?>'
    <?php echo "module-id={$module->name}_{$module->id}"; ?>
    style="<?php if(!empty($faqBackground)){ echo $faqBackground; }?>  <?php echo $sectionStyle; ?>"
>
    <?php if($externalArea->content_display_mode) { ?>
        <div class="nb-container">
            <div class="<?php echo $itemColumns; ?>">
    <?php } ?>
    <?php // Exibe cabeçalho da FAQ ?>
    <?php require JModuleHelper::getLayoutPath($extensionName, 'common/external_area');?>
    
    <div class="faq-container">
        <?php if($moduleParams->faqs_display_search_form) { ?>
            <?php // Exibe formulario de busca ?>
            <?php require JModuleHelper::getLayoutPath($extensionName, 'common/search_form');?>
        <?php } ?>
        <?php // Exibe html para resultados da faq  ?>
        <?php require JModuleHelper::getLayoutPath($extensionName, 'common/results');?>
        <?php // Input hidden com cor do loader ?>
        <input type="hidden" name="loadColor" value="<?php echo $externalArea->loader_color; ?>">
    </div>
    <?php if($externalArea->content_display_mode) { ?>
            </div>
        </div>
    <?php } ?>
</section>
