<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined("JPATH_PLATFORM") or die;

require JPATH_LIBRARIES . '/noboss/forms/fields/nobosseditor.php'; 

class JFormFieldNobosseditorfaq extends JFormFieldNobosseditor{

    public $type = "nobosseditorfaq";

    protected function getInput(){
        $html = parent::getInput();

        // Exibe note abaixo do editor com link para as configuracoes globais
        $html .= '<div style="width: 100%; display: inline-block;">'.JText::sprintf('COM_NOBOSSFAQ_EDIT_ANSWER_NOTE', 'index.php?option=com_config&view=component&component=com_nobossfaq').'</div>';

        return $html;
    }

}
