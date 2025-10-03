<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss FAQ
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined("_JEXEC") or die;
?>

<div id="faq-result-<?php echo $module->id; ?>" data-open-questions="<?php echo $itemsCustomization->open_questions; ?>" class="faq-results">
    <div class="faq-results__content" style="">
        <?php
         // Se existir alguma pergunta
         if(count($questions) > 0) {
            // Armazena ultima categoria visitada
            $lastCategory = $questions[0]->id_category;

            // Percorre array de perguntas
            foreach($questions as $key => $question) :
                // Armazena a ultima categia
                $lastCategory = $questions[0]->id_category;

                // Esta sendo exibido form de pesquisa e esta setado para NAO exibir todos resultados no carregamento
                if($itemsCustomization->show_question_upload == 0){
                    $itemsStyle .= ' display: none;'; 
                }

                // Modelo 2 - Abre div principal da resposta e exibe pergunta
                if($theme == 'model2'){ 
                    ?>
                    <div class="faq-item <?php echo $separateItems ? 'faq-item--separated' : ''; ?>" data-id-category="<?php echo $question->id_category; ?>" data-id-category-parent="<?php echo $question->id_category_parent; ?>" data-id-faq="<?php echo $question->id; ?>" style="<?php echo $itemsStyle; ?>">
                        <div class="faq-item__question" style='<?php echo $questionStyle; ?>'>
                            <?php echo "<{$questionTagHtml} class='faq-question' style='{$questionSize}'>{$question->question}<span class='fa {$questionIcon} faq-item__icon' data-faq-icon='{$questionIcon}' data-faq-icon-active='{$questionIconActive}' style='{$questionIconStyle}'></span></{$questionTagHtml}>"; ?>
                        </div>
                <?php
                } 
                
                // Demais modelos (1 e 3) - Abre div principal da resposta e exibe pergunta
                else {
                    ?> 
                    <div class="faq-item" data-id-category="<?php echo $question->id_category; ?>" data-id-category-parent="<?php echo $question->id_category_parent; ?>" data-id-faq="<?php echo $question->id; ?>" style="<?php echo $itemsStyle; ?> ">
                        <div class="faq-item__question" style='<?php echo !empty($itemsSpace) ? $itemsSpace : ''; ?> <?php echo $questionStyle; ?>'>
                            <?php echo "<span class='fa {$questionIcon} faq-item__icon' data-faq-icon='{$questionIcon}' data-faq-icon-active='{$questionIconActive}' style='{$questionIconStyle}'></span>"; ?>
                            <?php echo "<{$questionTagHtml} class='faq-question' style='{$questionSize}'>{$question->question}</{$questionTagHtml}>"; ?>
                        </div>
                <?php 
                } 
                
                        // Exibe a resposta da pergunta 
                        ?>
                        <div class="faq-item__answer" style="display: none;">
                            <div class="faq-answer" style='<?php echo $answerStyle; ?>'>
                                <?php
                                echo JHTML::_('content.prepare', $question->answer);
                                ?>
                            </div>                           
                        </div>
                    </div>
        <?php $lastCategory = $question->id_category; ?>
        <?php endforeach;
        }?>
        <?php // Mensagem de alerta informando que nenhuma pergunta foi encontrada ?>
        <div class="alert alert-warning" style="display:none;">
            <?php echo JText::_("MOD_NOBOSSFAQ_ALERT_MESSAGE_RESULTS_NOT_FOUND"); ?>
        </div>
    </div>
</div>   
