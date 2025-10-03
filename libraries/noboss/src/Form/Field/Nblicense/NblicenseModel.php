<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field\Nblicense;

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

/**
 * Classe de model para o campo nobosslicense
 */
class NblicenseModel {

    public static function updateUserLocalPlan($updateSiteId, $extraQuery) {
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        // Monta a string de query que será salva no banco
        $extraQueryString = htmlentities(http_build_query($extraQuery));
        
        $query->update("#__update_sites AS a")
		->set("a.extra_query = '{$extraQueryString}'")
		->where("a.update_site_id = '{$updateSiteId}'");
        
		try{
            $db->setQuery($query);
            $result = $db->execute();
		}catch(\Exception $e){
			return false;
        }
        return $result;
    }

    /**
     * Busca token e id do plano
     * 
     * depreciated na versao 1.8.15 (17/04/24), mas tivemos que manter pq eh chamada ainda com esse nome em arquivos 'script.extension.php' de extensoes
     * 
     * @return String Token da licença
     */
    public static function getLicenseTokenAndPlan($extensionName){
        return self::getlocalLicenseData($extensionName);
    }

    /**
     * Busca dados da licenca / extensao na base local do site
     * 
     * OBS: essa funcao tb eh utilizada em arquivos 'script.extension.php' de extensoes
     * 
     * @return String Token da licença
     */
    public static function getlocalLicenseData($extensionName){
        $db = Factory::getDBO();
        $query = $db->getQuery(true);

        // Busca pela linha da extensão no banco para saber se não está em nenhum pacote
        $query->select("a.package_id")
        ->from('#__extensions as a')
        ->where("a.element = '{$extensionName}'")
        ->setLimit(1);

        $db->setQuery($query);
        $packageId = $db->loadResult();

        // Busca pelas indormações da licença no banco através do token
        $query = $db->getQuery(true);
        $query->select("c.extra_query, c.update_site_id, c.enabled, a.manifest_cache, d.version AS last_version")
        ->from('#__extensions as a')
        ->join('INNER', '#__update_sites_extensions AS b ON b.extension_id = a.extension_id')
        ->join('INNER', '#__update_sites AS c ON c.update_site_id = b.update_site_id')
        ->join('LEFT', '#__updates AS d ON d.update_site_id = b.update_site_id AND d.extension_id = a.extension_id')

        ->setLimit(1);
        
        // Caso esteja em um pacote, passa a usar o id do pacote do pacote para buscar o token
        if(!empty($packageId)){
            // Muda a busca pelo id do pacote do pacote
            $query->where("a.extension_id = '{$packageId}'");
        } else {
            $query->where("a.element = '{$extensionName}'");
        }

        $query->where("c.extra_query IS NOT NULL");
        $query->where("c.extra_query <> ''");
        
        $query->order("c.enabled DESC");

        try{
            $db->setQuery($query);
            $result = $db->loadObject();
            
            // Nenhum resultado encontrado
            if(empty($result)){
                return array();
            }

            // Registro esta marcado com status 0 no banco (joomla tem mudado em alguns casos): realiza update no banco mudando p/ 1
            if($result->enabled == 0){
                
                $query = $db->getQuery(true);
                $query->update("#__update_sites AS a")
                ->set("a.enabled = '1'")
                ->where("a.update_site_id = '{$result->update_site_id}'");
                try{
                    $db->setQuery($query);
                    $db->execute();
                }catch(\Exception $e){
                }
            }

        }catch(\Exception $e){
            return array();
        }

        $arrayReturn = array();

        // echo '<pre>';
        // var_dump($result);
        // exit;

        // Extrai o valor do token e armazena em array de retorno
        $result->extra_query = html_entity_decode($result->extra_query);
        parse_str($result->extra_query, $arrayReturn);

        // Armazena ID de update site para retorno
        $arrayReturn['update_site_id'] = $result->update_site_id;
        
        // Extrai o valor da versao instalada no site e armazena no array de retorno
        $arrayReturn['installed_version'] = "";
        if(!empty($result->manifest_cache)){
            $temp = json_decode($result->manifest_cache);
            $arrayReturn['installed_version'] = (isset($temp->version) ? $temp->version : "");
        }

        // Armazena ultima versao disponivel em array de retorno
        $arrayReturn['last_version'] = $result->last_version;

        return $arrayReturn;
    }
}
