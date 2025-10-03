<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss FAQ
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

class ModNobossfaqHelper
{
    
    /**
     * Método que pega parâmetros do módulo conforme seu grupo associado.
     *
     * @param 	int 	$idModule Id do módulo para buscar os parâmetros.
     *
     * @return 	mixed 	Retorna objeto com os parâmetros caso sucesso, retornará false caso
     * o módulo não possua grupo associado.
     */
    public static function getModuleParamsByFaqsGroup($idModule){

        // Pega o grupo associado ao módulo.
        $faqsGroup = self::getFaqsGroupByModule($idModule);

        // Verifica existe grupo de depoimentos associado ao modulo.
        if(empty($faqsGroup)){
            return false;
        }

        // Lista com parâmetros do módulo.
        $moduleParams = array();
        // Pega configurações para o módulo.
        $moduleParams = json_decode($faqsGroup->config_module_faqs_display);
        $moduleParams->groupState 	= $faqsGroup->state;
        $moduleParams->language 	= $faqsGroup->language;
        $moduleParams->id_faqs_group= $faqsGroup->id_faqs_group;

        $moduleParams->content_display_registered = $faqsGroup->content_display_registered;
        $moduleParams->content_display_articles = $faqsGroup->content_display_articles;
        $moduleParams->content_categories_articles = $faqsGroup->content_categories_articles;

        return $moduleParams;
    }

    /**
     * Método que busca dados de um grupo de um módulo.
     *
     * @param 	int $idModule 	Id do módulo para busca do grupo associado.
     *
     * @return object|null 		Retorna registro dos dados do grupo do módulo ou null caso o módulo não possua
     * grupo associado.
     */
    public static function getFaqsGroupByModule($idModule){

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query
            ->select("faqs_group.*")
            ->from("#__noboss_faq_group AS faqs_group")
            ->where('faqs_group.id_module_faqs_display = ' . (int) $idModule);

        $db->setQuery($query);
        $result = $db->loadObject();

        return $result;
    }

    /**
     * Obtem as categorias a serem exibidas para o modulo de FAQ
     * 
     * @param 	int 		$idFaqsGroup        Id do grupo das FAQSs
     * @param 	string 		$displayOrder       Ordenacao dos resultados
     * @param   array       $optionsContent     Definicoes de origens dos conteudos
     *
     * @return false|Array 	Retorna lista com categorias do componente ou false caso falhe.
     */
    public static function getCategories($idFaqsGroup, $displayOrder, $optionsContent) {
        $db = JFactory::getDBO();
        
        // Armazena o idioma de navegação
        $navigationLanguage = JFactory::getLanguage()->getTag();

        // Carregamento cadastro do componente
        if((int)$optionsContent['display_registered'] === 1){
            // Obtem perguntas ativas vinculadas com a FAQ
            $query = $db->getQuery(true);
            $query
                ->select('GROUP_CONCAT(DISTINCT id_category)')
                ->from($db->quoteName('#__noboss_faq'))
                ->where($db->quoteName('state') . ' = 1')
                ->where($db->quoteName('id_faqs_group') . ' = ' . (int) $idFaqsGroup);

            $db->setQuery($query);
            $idsCategories = $db->loadResult();
        }

        // Carregamento categorias de artigos
        if(($optionsContent['display_articles'] === 1) && (!empty($optionsContent['categories_articles']))){           
            $idsCategories .= (!empty($idsCategories)) ? ','.$optionsContent['categories_articles'] : $optionsContent['categories_articles'];
        }

        //echo $idsCategories; exit;
        
        if(empty($idsCategories)){
            return false;
        }

        // Obtem as categorias vinculadas com as perguntas ativas da faq
        $query = $db->getQuery(true);
        $query
            ->select('a.id, a.title')
            ->from("#__categories as a")
            ->where("a.published = 1")

            // Filtra categorias pai e filhas que possuem perguntas da FAQ vinculadas
            ->where("((a.id IN ({$idsCategories})) 
                        OR (EXISTS (select * from #__categories AS c where c.parent_id = a.id AND c.id IN ({$idsCategories}) AND c.published = 1))
                    )");

        // Busca nome da categoria pai, caso tenha uma vinculada
        $query->select("IF((a.parent_id <> '0'), (SELECT b.title FROM #__categories AS b WHERE b.id = a.parent_id and b.extension = 'com_nobossfaq'), '') as name_category_parent");
        
        // Filtra pelo idioma de navegação
        $query->where(
            '(' . $db->quoteName('a.language') . ' = ' . $db->quote($navigationLanguage) .
            ' OR ' . $db->quoteName('a.language') . " = '*')"
        );
        
        // Ordenacao definicao como manual
        if ($displayOrder == 'manual'){
            $query->order('lft ASC');
        }
        // Ordenacao definida como alfabetica
        else if($displayOrder == 'alphabetical'){
            $query->order('title ASC');
        }

        $query->where("a.extension IN ('com_nobossfaq', 'com_content')");

        $db->setQuery($query);

        //echo '<pre>'.str_replace('#__', 'ext_', $query); exit;
        
        try {
            // Armazena lista de objetos das categorias
            $categories = $db->loadObjectList();
            
        } catch (\Exception $e) {
            // Retorna false caso occora um erro na execução da query 
            return false;
        }

        // Retorna a lista de categorias
        return $categories;
    }

    /**
     * Obtem as perguntas a serem exibidas para o modulo de FAQ
     *
     * @param 	int 		$idFaqsGroup        Id do grupo das FAQSs
     * @param 	string 		$orderingQuestions  Formato de ordenacao dos resultados
     * @param 	int 		$groupByCategory    Informa se deve agrupar por categoria os resultados
     * @param 	string 		$orderingQuestions  Formato de ordenacao das categorias
     * @param   array       $optionsContent     Definicoes de origens dos conteudos
     *
     * @return 	false|array Retorna lista com as FAQs ou false caso algum erro ocorra.
     */
    public static function getQuestions($idFaqsGroup, $orderingQuestions, $groupByCategory, $displayOrderCategories, $optionsContent) {

        $db = JFactory::getDBO();
        $query = $db->getQuery(true);

        // Filtros para consulta.
        $navigationLanguage = JFactory::getLanguage()->getTag();

        $order = "";
        $queryComponent = "";
        $queryArticle = "";

        // Resultados devem ser agrupados por categoria
        if($groupByCategory == '1'){
            // Ordenacao de categorias manual
            if($displayOrderCategories == 'manual'){
                $order = "lft_category ASC";
            }
            // Ordenacao de categorias alfabetica
            else{
                $order = "title_category ASC";
            }
        }

        // Ordenacao
        switch ($orderingQuestions) {
            // Alfabetico sem considerar categoria
            case 'alphabetical':
                $order .= ", TRIM(question) ASC";
                break;

            // Manualmente
            case 'manually':
                $order .= ", ordering DESC";
                break;
        }

        if(!empty($order)){
            $order = "ORDER BY {$order}";
        }

        // echo '<pre>';
        // var_dump($optionsContent);
        // exit;

        // Carregamento cadastro do componente
        if((int)$optionsContent['display_registered'] === 1){
            $queryComponent = "(SELECT faqs.id_faq AS id, faqs.question, faqs.answer, faqs.id_category,
                                    categories.parent_id as id_category_parent, faqs.ordering, categories.lft as lft_category,
                                    categories.title as title_category
                                
                                    FROM #__noboss_faq AS faqs
                                    
                                    INNER JOIN #__noboss_faq_group AS faqs_group ON faqs_group.id_faqs_group = faqs.id_faqs_group
                                    
                                    INNER JOIN #__categories AS categories ON categories.id = faqs.id_category
                                    
                                    WHERE faqs.state = '1'
                                        AND faqs.id_faqs_group = '{$idFaqsGroup}'
                                        AND (faqs_group.language = {$db->quote($navigationLanguage)} OR faqs_group.language = '*')
                                        AND (categories.language = {$db->quote($navigationLanguage)} OR categories.language = '*'))";
        }

        // Carregamento categorias de artigos
        if((int)($optionsContent['display_articles'] === 1) && (!empty($optionsContent['categories_articles']))){           
            $queryArticle = "(SELECT CONCAT('article_', article.id) as id, article.title AS question, article.introtext AS answer,
                                article.catid AS id_category, categories.parent_id as id_category_parent,
                                article.ordering, categories.lft as lft_category, categories.title as title_category
                                
                                FROM #__content AS article
                                
                                INNER JOIN #__noboss_faq_group AS faqs_group ON faqs_group.id_faqs_group = '{$idFaqsGroup}'
                                
                                INNER JOIN #__categories AS categories ON categories.id = article.catid
                                
                                WHERE article.state = 1
                                    AND article.catid IN ({$optionsContent['categories_articles']})
                                    AND (article.language = {$db->quote($navigationLanguage)} OR article.language = '*')
                                    AND (faqs_group.language = {$db->quote($navigationLanguage)} OR faqs_group.language = '*')
                                    AND (categories.language = {$db->quote($navigationLanguage)} OR categories.language = '*'))";
        }

        if(!empty($queryComponent) && !empty($queryArticle)){
            $query = "{$queryComponent}
                        UNION
                    {$queryArticle}

                    {$order}";
        }
        else if(!empty($queryComponent)){
            $query = "{$queryComponent}
                    {$order}";
        }
        else if(!empty($queryArticle)){
            $query = "{$queryArticle}
                    {$order}";
        }

        $db->setQuery($query);

        //echo '<pre>'.str_replace('#__', 'ext_', $query); exit;

        try {
            $result = $db->loadObjectList();
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
	 * Verifica se No Boss Library esta instalada no site e caso nao esteja, forca uma atualizacao e recarrega a pagina
     * 
     * OBS: essa acao eh necessaria para contornar problema em atualizacoes da library no processo do Joomla que as vezes mantem ela removida
	 *
	 * @return  void
	 */
    public static function checkLibraryInstallation(){
        // Library da No Boss nao localizada: inicia instalacao forcada da library
        if(!JFolder::exists(JPATH_LIBRARIES.'/noboss')) {
            try {
                // Adiciona diretorio de models do componente installer do Joomla
                JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models');
                // Instancia model de extensoes
                $modelInstaller = JModelLegacy::getInstance('install', 'InstallerModel');
                $input = JFactory::getApplication()->input;
                
                // Versao macro do Joomla (ex: 5)
                $joomlaVersion = substr(JVERSION, 0, 1);

                $urlXml = "https://www.nobossextensions.com/repository/extras/nobosslibrary/xml.xml";
                $urlInstallation = "https://www.nobossextensions.com/en/installation/nobosslibrary/";

                // Objeto com conteudo do arquivo XML que controla as atualizacoes da extensao
                $xmlUpdates = simplexml_load_file($urlXml);

                // Percorre todas versoes de update do xml
                for($i=0; $i < count($xmlUpdates->update); $i++){
                    // Possui atributo que identifica a versao da extensao
                    if(isset($xmlUpdates->update[$i]->attributes()['nbjoomla'])){
                        // Obtem qual eh a versao do Joomla (ex: 'current', '3', '4') no item de update do XML
                        $xmlUpdateJoomlaVersion = $xmlUpdates->update[$i]->attributes()['nbjoomla'];

                        // Item eh da mesma versao que estamos da extensao: adiciona versao do joomla na url de instalacao
                        if($xmlUpdateJoomlaVersion == $joomlaVersion){
                            $urlInstallation .= "joomla{$joomlaVersion}/";
                        }
                    }
                }
                
                // Seta para utilizar metodo de instalacao via url
                $input->set('installtype', 'url');
                // Seta a url de instalacao da library
                $input->set('install_url', $urlInstallation);

                // Executa metodo de instalacao
                $modelInstaller->install();
            } catch (\Exception $e) {
                // Nao tem tratamento de erro pq o Joomla joga diversas vezes a falta de permissoes como erro sendo que ocorreu tudo certo
            }

            // Library foi instalada com sucesso
            if(JFolder::exists(JPATH_LIBRARIES.'/noboss')) {
                // Recarrega a pagina
                header("Refresh:0");
                exit;
            }
            else{
                return false;
            }
        }
        return true;
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
