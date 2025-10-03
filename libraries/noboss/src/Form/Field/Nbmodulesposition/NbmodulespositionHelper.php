<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field\Nbmodulesposition;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Noboss\Library\Util\NbComponentExampleDataUtil;

defined('_JEXEC') or die;

/**
 * Classe com funcoes de apoio para componentes que geram modulos automaticamente (Ex: 'no bossfaq' e 'no boss testimonials)
 */
class NbmodulespositionHelper {
    /**
     * Funcao que salva o registro do modulo
     *
     * @param       string          $moduleName         Nome do modulo (Ex: 'mod_nobossfaq')
     * @param       Integer         $idModule           Id do modulo (caso ja exista)
     * @param       string          $moduleTitle        Nome do titulo do modulo.
     * @param       string          $positionModule     Posicao para o modulo.
     * @param                       $assignment
     * @param                       $assigned
     * @param       Integer         $status             Status do modulo
     * @param       String          $language           Idioma
     * @param       array           $exampleModals      Dados das modais que seram salvas nos params do modulo
     * @param       array           $exampleDataTabs    Dados dos campos principais que serao salvos nos params do modulo
     * @param       array           $ignoreFields       Fields a ignorar
     * 
     * @return      mixed           Id do modulo ou false
     */
    public static function saveModule($moduleName, $idModule, $moduleTitle, $positionModule, $assignment, $assigned, $status,  $language, $exampleModals, $exampleDataTabs, $ignoreFields){       
        // Instancia tabela de modulo
        $tableModule = Table::getInstance('module');
        
        // Carrega o modulo (mesmo se id nao existir)
        $tableModule->load($idModule);
        
        // Configura titulo do modulo.
        $tableModule->title = $moduleTitle;
        // Configura posicoes do modulo.
        $tableModule->position = $positionModule;
        // Configura o modulo associado ao registro.
        $tableModule->module = $moduleName;
        // Configura linguagem.
        $tableModule->language = $language;
        // Define status do modulo 
        $tableModule->published = $status;
        
        $tableModule->access = '1';
        $tableModule->showtitle = '0';
        $tableModule->content   = '';
        $tableModule->client_id = 0;

        // chama funcao que formata todos os dados de exemplo em um json
        $generationData = NbComponentExampleDataUtil::prepareData($exampleModals, $exampleDataTabs, $ignoreFields);

        // echo '<pre>';
        // var_dump($generationData);
        // exit;
        
        // salva na coluna params um unico json contendo as configs de todas modais
        $tableModule->params = $generationData;
        
        // Tenta salvar o modulo.
        $isSavedModule = $tableModule->store();

        // Modulo foi salvo com sucesso
        if($isSavedModule){
            // Deleta todos os registros de menus associados ao modulo.
            self::deleteModuleMenusAssignment($tableModule->id);

            // Tenta pegar menus de associacao do modulo se nao existir configura valor padrao 0.
            $assignment = isset($assignment) ? $assignment : 0;

            // Verifica se a variavel $assigment eh numerica.
            if (is_numeric($assignment)) {
                $assignment = (int) $assignment;

                // Verifica se a opcao de $assignment eh "Todas as paginas exceto as selecionadas" e se nao foi selecionado nenhum menu para $assigned.
                if ($assignment == -1 && empty($assigned)) {
                    // "Converte" $assignment para opcao de exibir em "todas as paginas".
                    $assignment = 0;
                }

                // Verificacao necessaria para evitar "associacao" do modulo com todas as paginas mais de uma vez, resultando em exibicao duplicada.
                if ($assignment === 0) {
                    // Associa modulo a todos os menus.
                    $isAssignedMenus = self::setModuleMenuAssignment($tableModule->id);

                }
                // Verifica se os lista de menus associados nao eh vazia.
                elseif (!empty($assigned)) {
                    // Associa modulo aos menus selecionados.
                    $isAssignedMenus = self::setModuleMenuAssignment($tableModule->id, $assigned, $assignment);
                }

                // Verifica se ocorreu erro ao salvar a associacao de menus para o modulo.
                if(!$isAssignedMenus){
                    Factory::getApplication()->enqueueMessage(Text::_("Error saving module record. Error occurred saving module menu associations."), 'error');
                    return false;
                }
            }
        }else{
            Factory::getApplication()->enqueueMessage(Text::_("Error saving module record."), 'error');
            return false;
        }

        // O registro foi salvo com sucesso: retorna o id
        return $tableModule->id;
    }

     /**
     * Funcao que associa modulo aos menus/paginas.
     *
     * @param   int     $idModule       Id do modulo para associar os menus.
     * @param   array   $listAssigned   Lista com os ids dos menus a serem associados com o modulo, se a lista for vazia o modulo sera atribuido a todos os menus/paginas.
     * @param   int     $assigned opcao de associacaoo.
     * @return  boolean  true ou false
     */
    public static function setModuleMenuAssignment($idModule, array $listAssigned = null, $assignment = false){
        $db = Factory::getDBO();
        $query = $db->getQuery(true);

        // Consulta que associa o modulo com os menus/paginas.
        $query
            ->insert('#__modules_menu')
            ->columns(array($db->quoteName('moduleid'), $db->quoteName('menuid')));

        // Verifica se $assignment eh diferente de false e se $assignment foi informado.
        if($assignment != false && isset($assignment)){

            // Pega o sinal da opcao de $assignment.
            $sign = $assignment < 0 ? -1 : +1;

            // Percorre todos os menus selecionados.
            foreach ($listAssigned as &$pk) {
                // Seta valor da query para a query.
                $query->values( (int) $idModule . ',' . (int) $pk * $sign );
            }
        }else{
            // Seta para que o modulo tenha todos os menus associados.
            $query->values((int) $idModule . ', 0');
        }

        $db->setQuery((string) $query);

        try {
            $db->execute();
        }
        catch (\RuntimeException $e)  {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }

        return true;
    }

    

    /**
     * Funcao que deleta todas as associacoes do modulo com tabela de menus
     *
     * @param   int         $idModule     Id do modulo
     * 
     * @return  boolean     true ou false
     */
    public static function deleteModuleMenusAssignment($idModule) {
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        
        $query
            ->delete()
            ->from('#__modules_menu')
            ->where('moduleid = ' . (int) $idModule);

        $db->setQuery($query);

        try {
            $db->execute();
        }
        catch (\RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }

        return true;
    }

    /**
     * Metodo que altera status de um modulo
     *
     * @param   int     $moduleId   Id do modulo a ser despublicado.
     * @param   int     $value      Valor para a cpluna published.
     *
     * @return boolean  true ou false
     */
    public static function publishModule($moduleId, $value) {
        $db = Factory::getDBO();
        $query = $db->getQuery(true);

        $query
            ->update("#__modules")
            ->set( "published = " . (int) $value )
            ->where("id = " . (int) $moduleId);
        $db->setQuery($query);

        return $db->execute();
    }

    /**
     * Metodo que pega dados do modulo.
     *
     * @param int $idModule Id do modulo para carregar os dados.
     * @return array Retorna uma lista de dados do modulo.
     */
    public static function getDataModule($idModule) {
        // Verifica se foi informado o par�metro $idModule.
        if(empty($idModule)){
            Factory::getApplication()->enqueueMessage(Text::_("Error loading module data."), 'error');
            return false;
        }

        $db = Factory::getDBO();
        $query = $db->getQuery(true);

        // Query para pegar dados do modulo.
        $query
               ->select("modules.*, modules_menu.*")
               ->from($db->quoteName("#__modules", "modules"))
               ->join('LEFT', $db->quoteName("#__modules_menu", "modules_menu") . " ON modules.id = modules_menu.moduleid")
            ->where("modules.id =" . (int) $idModule);

        $db->setQuery($query);

        // Pega o registros do modulo.
        $dataModule = $db->loadObjectList();

        if(empty($dataModule)){
            return;
        }

        $module = $dataModule[0];
        // Pega id de menu do modulo.
        $moduleMenuId = $module->menuid;
        // Pega titulo do modulo
        $moduleMenus['title'] = $module->title;
        // Pega id de cliente do modulo.
        $moduleMenus['client_id'] = $module->client_id;
        // Pega posicao do modulo.
        $moduleMenus['position'] = $module->position;
        // Pega estado de publicacao do modulo.
        $moduleMenus['published'] = $module->published;

        // Variavel que contem a lista de menus associados ao modulo.
        $moduleMenus['assigned'] = array();

        // Verifica se o id do menu foi configurado e se eh 0.
        if( isset($moduleMenuId) && $moduleMenuId == 0 ){
            // Configura $asssignment como 0, que significa "todos os menus/paginas".
            $moduleMenus['assignment'] = 0;
        }
        // Verifica se o id do menu eh maior que 0.
        elseif( $moduleMenuId > 0 ){
            // Configura asssignment como 1, que significa "nos menus/paginas selecionados".
            $moduleMenus['assignment'] = 1;
            // Percorre todos os menus encontrados para o modulo.
            foreach( $dataModule as $item ){
                // Adiciona ao array valor do item.
                $moduleMenus['assigned'][] = $item->menuid;
            }
        }
        // Verifica se o id do menu eh menor que 0.
        elseif($moduleMenuId < 0){
            // Configura asssignment como -1, que significa "todos exceto os menus/paginas selecionados".
            $moduleMenus['assignment'] = -1;
            // Percorre todos os menus encontrados para o modulo.
            foreach( $dataModule as $item ){
                // Adiciona ao array valor do item.
                $moduleMenus['assigned'][] = $item->menuid;
            }
        }
        else{
            // Configura asssignment como -, que significa "nao exibir em nenhum local".
            $moduleMenus['assignment'] = "-";
        }

        // Retorna dados do modulo.
        return $moduleMenus;
    }

    /**
     * Metodo que deleta modulo associado ao grupo.
     *
     * @param int $idModule Id do modulo a ser deletado.
     * @return boolean Retorna true se deletado com sucesso ou false caso algum erro
     * tenha ocorrido.
     */
    public static function deleteModuleGroup($idModule){
        // Instancia da tabela de modulos.
        $tableModule = Table::getInstance('module');

        // Tenta carregar registro da tabela de modulo.
        if($tableModule->load($idModule)){
            // Delete modulo.
            $tableModule->delete();
            // Deleta menus associados ao modulo.
            self::deleteModuleMenusAssignment($idModule);
        }else{
            return false;
        }

        return true;
    }
}
