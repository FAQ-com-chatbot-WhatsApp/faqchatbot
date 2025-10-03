<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss FAQ
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

?>

<?php
   if($categories){?>
        <?php // Caso tenha registros vinculados, exibe o form de busca ?>
        <form class="faq-form" method="POST">
        <div class="faq-form__item faq-form__item--select control-group">
            <div class="controls">
                <select id="frequent-questions-subjects-<?php echo $module->id; ?>" style='<?php echo $searchForm->inputStyle; ?> <?php echo $searchForm->borderStyle; ?>' name="frequent-questions-subjects">
                    <option style="color:gray" value=""><?php echo JText::_("MOD_NOBOSSFAQ_SELECT"); ?></option>
                    <?php
                        // Itera array de categorias
                        foreach($categories as $category) : ?>
                            <option style="color:gray" value="<?php echo $category->id; ?>">
                                <?php 
                                // Possui categoria pai: exibe o nome antes
                                if (!empty($category->name_category_parent)){
                                    echo $category->name_category_parent.' - ';
                                }
                                // Exibe nome da categoria
                                echo $category->title;
                                ?>
                            </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="faq-form__item control-group">
            <div class="controls">
                <input id="faq-key-word-<?php echo $module->id ?>" title="<?php echo JText::_("MOD_NOBOSSFAQ_SEARCH"); ?>" style='<?php echo $searchForm->inputStyle; ?> <?php echo $searchForm->borderStyle; ?>' name="search" type="text" placeholder = "<?php echo JText::_("MOD_NOBOSSFAQ_SEARCH_KEY_WORD"); ?>" />
            </div>
        </div>
        <div class="faq-form__footer control-group">
            <button name="search" class="faq-submit faq-form__search" type="submit" style='<?php echo $searchForm->buttonStyle; ?>'>
                <?php echo (!empty($moduleParams->faqs_display_submit_button_text)) ? $moduleParams->faqs_display_submit_button_text :  JText::_("MOD_NOBOSSFAQ_SEARCH_BUTTON"); ?>
            </button>
        </div>
    </form>
    <?php 
    } 
?>
