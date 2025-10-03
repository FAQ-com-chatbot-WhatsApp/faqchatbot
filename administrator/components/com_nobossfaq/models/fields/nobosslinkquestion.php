<?php
/**
 * @package			No Boss Extensions
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined("_JEXEC") or die;

JFormHelper::loadFieldClass('note');

// Exibe informacoes do link para a pergunta ou categoria

class JFormFieldNobosslinkquestion extends JFormFieldNote
{

	protected $type = "nobosslinkquestion";

    function getInput()	{       
        // Obtem o id da pergunta na url
        $idFaq = JFactory::getApplication()->input->get('id_faq');

        // ID da faq ainda nao definido
        if(empty($idFaq)){
            $this->element['description'] = JText::_('COM_NOBOSSFAQ_FILTER_LINK_NOT_SAVE');
        }
        else{
            // Obtem o id da categoria salva no banco (se usuario mudar antes de salvar, o valor eh trocado via JS)
            $idCategory = $this->form->getData()->get('id_category');
    
            // Monta texto que eh exibido com orientacoes
            $this->element['description'] = "        
                <b>".JText::_('COM_NOBOSSFAQ_FILTER_LINK_FIRT_STEP_INTRO')."</b><br>".JText::_('COM_NOBOSSFAQ_FILTER_LINK_FIRT_STEP_DESC')."
                <span class='badge bg-secondary'>https://www.mywebsite.com/faq/</span>
                <br><br>
    
                <b>".JText::_('COM_NOBOSSFAQ_FILTER_LINK_QUESTION_INTRO')."</b><br>".JText::sprintf('COM_NOBOSSFAQ_FILTER_LINK_QUESTION_DESC', "<span class='badge-info bg-info badge'>?faq-question={$idFaq}</span>", $idFaq, "<span class='badge-info bg-info badge'>?faq-question=article_XXX</span>", "<span class='badge bg-secondary'>https://www.mywebsite.com/faq/?faq-question={$idFaq}</span>")."
                
                <br><br>
                
                <b>".JText::_('COM_NOBOSSFAQ_FILTER_LINK_CATEGORY_INTRO')."</b><br>".JText::sprintf('COM_NOBOSSFAQ_FILTER_LINK_CATEGORY_DESC', "<span class='badge-info bg-info badge' filter-category-short>?faq-category={$idCategory}</span>", $idCategory, "<span class='badge bg-secondary' filter-category-complete>https://www.mywebsite.com/faq/?faq-category={$idCategory}</span>");
        }


		return parent::getInput();
	}
}
