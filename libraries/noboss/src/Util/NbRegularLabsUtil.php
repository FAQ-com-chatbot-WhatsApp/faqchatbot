<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Util;

use Joomla\CMS\Factory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbRegularLabsUtil {

	/**
	* Verificar se plugin 'System - Regular Labs - Advanced Module Manager' e componente 'advancedmodules' existem e estao habilitados
	*
	* @return 	boolean     true ou false
    *
	**/
	public static function checksAdvancedModulePluginEnabled() {
        // Componente admin advancedmodules nao esta instalado
        if(!\file_exists(JPATH_ADMINISTRATOR . '/components/com_advancedmodules/advancedmodules.xml')){
            return false;
        }

        // Plugin advancedmodules nao esta instalado
        if(!\file_exists(JPATH_SITE . '/plugins/system/advancedmodules/advancedmodules.xml')){
            return false;
        }

        if(!self::getStatusPluginAdvancedModule()){
            return false;
        }

        return true;    
    }   

    /**
	* Verificar se plugin 'System - Regular Labs - Advanced Module Manager' está habilitado no banco
	*
	* @return 	boolean     true ou false
    *
	**/
    private static function getStatusPluginAdvancedModule(){
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        //Monta a query para buscar na tabela extension
        $query->select('*')
            ->from('#__extensions')
            ->where("element = 'advancedmodules'")
            ->where("enabled = '1'")
            ->where("folder = 'system'");

        $db->setQuery($query);
        $db->execute();

        // Nao existem registros
        if(!$db->getNumRows()){
            return false;
        }

        return true;
    }
}
