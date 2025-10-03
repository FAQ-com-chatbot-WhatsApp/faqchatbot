<?php
/**
 * @package			No Boss Extensions
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2020 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('note');

// Classe utilizada para poder fazer replace de link no texto exibido como note

class JFormFieldNobossNote extends JFormFieldNote
{
 function getLabel()
	{
        $idModule = JFactory::getApplication()->input->get('id');
        
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('id_faqs_group')
        ->from("#__noboss_faq_group")
        ->where('id_module_faqs_display = ' . (int) $idModule);
        
        //echo str_replace('#__', 'ext_', $query); exit;

        try{
            $db->setQuery($query);
            $idGroup = $db->loadResult();
        }catch(\Exception $e){
            return false;
        }
        
        $this->element['label'] = JText::sprintf($this->element['label'], $idGroup);

		return parent::getLabel();
	}
}
