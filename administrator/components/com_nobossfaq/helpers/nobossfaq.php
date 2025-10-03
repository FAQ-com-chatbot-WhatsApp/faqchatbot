<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	com_nobossfaq
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

/**
 *  Classe Helper principal do componente que segue 'Metodo No Boss de desenvolvimento'
 *  @author  Johnny Salazar Reidel
 * 
 *  Orientacao: 
 *      - Edite o nome da classe
 *      - Edite a funcao 'addSubmenu' sinalizando os submenus a exibir na lateral (executado somente para Joomla 3)
 *      - Nesta classe voce pode adicionar varias funcoes que sirvam para mais de uma view, controller ou model do componente
 *      - Dependendo da complexibidade do componente, podem ser criados mais arquivos helpers
 *      - Nos locais que precisar utilizar o helper, eh necessario declarar ele conforme o exemplo abaixo:
 *          JLoader::register($componentClassPrefix.'Helper', JPATH_ADMINISTRATOR . '/components/' . $componentAlias . '/helpers/'.strtolower($componentClassPrefix).'.php');
 */

class NobossfaqHelper extends JHelperContent{

	/**
	 * Metodo que define os submenus do componente a exibir na lateral (utilizado somente no J3)
     *      - Caso nao queira exibir nenhum submenu, apenas nao declare nenhuma posicao de array para o $submenus
	 *
	 * @param   String  $vName              O nome da view ativa
	 *
	 * @return  Void
	 */
	public static function addSubmenu($vName) {
        $input = JFactory::getApplication()->input;
        
        // Obtem o alias do componente que esta sendo navegado
        $componentAlias = $input->get('option');
        
        // Usuario esta no componente de categorias: pega o alias do nosso componente pelo atributo 'extension'
        if($componentAlias == 'com_categories'){
            $componentAlias = $input->get('extension');
        }       
        
        $submenus = array();

        // Submenus a exibir (declarar para cada submenu 'titulo', 'link' e 'alias da view')
        $submenus[] = array('title' => JText::_('COM_NOBOSSFAQ_GROUPS'), 'link' => "index.php?option={$componentAlias}&view=groups", 'aliasView' => 'groups');
        $submenus[] = array('title' => JText::_('COM_NOBOSSFAQ_SUBJECTS'), 'link' => "index.php?option=com_categories&extension={$componentAlias}", 'aliasView' => 'categories');
        $submenus[] = array('title' => JText::_('COM_NOBOSSFAQ_QUESTIONS'), 'link' => "index.php?option={$componentAlias}&view=questions", 'aliasView' => 'questions');

        foreach ($submenus as $submenu) {
            JHtmlSidebar::addEntry($submenu['title'], $submenu['link'], $vName == $submenu['aliasView']);
        }
    }
    
    /**
	 * Funcao executada antes de qualquer acao de update do joomla
     * 
	 * @param   JUpdate       $update  An update definition
     * @param   JTableUpdate  $table   The update instance from the database
	 */
    public static function prepareUpdate($update, $table){
        jimport('noboss.util.installscript');

        // Token da licenca
        $token = str_replace('token=', '', $update->get('extra_query'));

        if (method_exists('NoBossUtilInstallscript','updateLicenseIsValid')){
            // Busca no servidor da No Boss se extensao possui permissao para update
            $return = NoBossUtilInstallscript::updateLicenseIsValid($token);
            
            // Token nao localizado
            if ($return == 'INVALID_TOKEN'){
                $msg = "<b>There are problems with the extension license:</b> <br /><br /> &bull; Your extension token was not found on the No Boss Extensions platform. <br /><br />";
                JFactory::getApplication()->enqueueMessage($msg, 'error');
                return false;
            }
            // Extensao nao possui suporte valido
            else if($return == 'INVALID'){
                $msg = "<b>License with expired update period:</b> <br /><br /> &bull; To update the extension, it is necessary to renew the license support period. <br /> &bull; You can renew the support time by accessing the tab called 'License' available within the edition of any extension registration. <br /><br />";
                JFactory::getApplication()->enqueueMessage($msg, 'error');
                return false;
            }
        }
    }
}
