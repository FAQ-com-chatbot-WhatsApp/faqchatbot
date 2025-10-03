<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss FAQ
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2019 No Boss Technology. All rights reserved.
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
    
    <div class="faq-container <?php echo !empty($position) && $position ? 'categories--' . $position : ''; ?>">
        <?php // Verifica se a exibicao de categorias esta habilitada e se existe registros vinculados  ?>
        <?php if($moduleParams->faqs_display_categories && !empty($categories)){ ?>
            <div class="nb-categories" id="frequent-questions-subjects-<?php echo $module->id; ?>" name="frequent-questions-subjects" style="<?php echo $marginTop; ?> <?php echo $manualAreaSize; ?>">
                <ul class='category__list' role="tablist">
                    <?php
                    // Percorre categorias a exibir
                    foreach($categories as $key => $category) : ?>
                        <li class='category__item' role="presentation">
                            <a class="category__link" href='#category-<?php echo $category->id; ?>' data-id="<?php echo $category->id; ?>" style='<?php echo $categoriesStyle; ?> <?php echo $textStyle; ?>'>
                                <?php 
                                // Possui categoria pai: exibe o nome antes
                                if (!empty($category->name_category_parent)){
                                    echo $category->name_category_parent.' - ';
                                }
                                // Exibe nome da categoria
                                echo $category->title;
                                ?>
                            </a>
                        </li>
                    <?php 
                    endforeach; 
                    ?>
                </ul>
            </div>
        <?php } ?>
        <?php if($categories){
            // Caso tenha registros, exibe os mesmos
            require JModuleHelper::getLayoutPath($extensionName, 'common/results');
        }?>
        <?php // Input hidden com cor do loader ?>
        <input type="hidden" name="loadColor" value="<?php echo $externalArea->loader_color; ?>">
    </div>
    <?php if($externalArea->content_display_mode) { ?>
            </div>
        </div>
    <?php } ?>
</section>
