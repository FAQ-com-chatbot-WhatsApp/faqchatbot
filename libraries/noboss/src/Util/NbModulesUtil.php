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

// Classe com metodos uteis gerais para uso em modulos
class NbModulesUtil {

	/**
	 * Obtem os params de um registro de modulo
	 *
	 * @param   Int       $idModule       Id do modulo que deseja obter os dados
     * @param   Boolean   $onlyParams     Flag que informa se devem ser obtidos somente os parametros do modulo
	 *
	 * @return  Objeto contendo os dados da coluna params do modulo
	 */
	public static function getDataModule($idModule, $onlyParams = false) {
		$dbo = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query
			->select('*')
			->from('#__modules')
			->where('id = ' . (int) $idModule);

		$dbo->setQuery($query);
		$dataModule = $dbo->loadObject();

        $dataModule->params = json_decode($dataModule->params);
        
        if ($onlyParams){
            return $dataModule->params;
        }

		return $dataModule;
    }

    /**
	 * Atualiza dados dos params de um registro de modulo
	 *
	 * @param   Int       $idModule       Id do modulo que deseja atualizar os dados
     * @param   Object    $dataParams     Dados a serem atualizados
     * @param   Boolean   $updateAll      Flag que diz se todos dados da coluna params devem ser sobreescritos ou apenas os dados enviados em $dataParams
	 *
	 * @return  Boolean   true ou false.
	 */
	public static function setDataModule($idModule, $dataParams, $updateAll = false) {
        $db = Factory::getDBO();

        // Setado para atualizar apenas os dados recebidos
        if(!$updateAll){
            // Obtem dados que estao no banco atualmente
            $dataParamsUpdate = self::getDataModule($idModule, true);

            if(empty($dataParamsUpdate)){
                return false;
            }

            // Percorre todos os parametros recebidos
            foreach ($dataParams as $attr => $value) {
                // Atualiza valor no objeto corrente
                $dataParamsUpdate->{$attr} = $value;
            }
        }
        // Setado para sobreescrever todos parametros conforme os recebidos
        else{
            $dataParamsUpdate = $dataParams;
        }

        
        // Prepara dados para salvar na coluna params
        $dataParamsUpdate = json_encode($dataParamsUpdate);
        
        // echo '<pre>';
        // var_dump($dataParamsUpdate);
        // exit;

        if(empty($dataParamsUpdate)){
            return false;
        }

        $query = $db->getQuery(true);
        
        // Atualiza campo no banco
        $query->update("#__modules")
                ->set($db->quoteName('params') . ' = ' . $db->quote($dataParamsUpdate))
                ->where('id = ' . (int) $idModule);

        //echo str_replace('#__', 'ext_', $query); exit;

        $db->setQuery($query);

        try{
            $db->execute();
            return true;
        }catch(\Exception $e){
            //echo $e->getMessage(); exit;
            return false;
        }
	}
}
