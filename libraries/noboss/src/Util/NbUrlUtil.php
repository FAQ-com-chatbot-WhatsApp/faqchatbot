<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Util;

use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbUrlUtil {
	/**
	*	Função que recebe um item de menu e retorna a URL para ele (ou para um associado) mantendo consistências de idioma
	* analisando qual idioma padrão/idioma atual e idioma do item de menu.
	*
	* @param 	int 		$idMenu 				ID do item de menu para o qual se deseja a URL
	* @param 	boolean		$fullUrl (optional) 	Boolean informando se deve ser retornada a url completa ou apenas o route do item de menu
	* @param 	string		$tagLanguage 			tag de idioma que deve ser informado na url (quando usuario quiser fixar uma)
	*
	* @return 	mixed 		URL para o item de menu (ou associado) passado via parâmetro ou false caso não encontrado
	**/
	public static function getUrlItemMenu($idMenu, $fullUrl = true, $tagLanguage = '') {
        // Objeto com dados do config
		$config = Factory::getConfig();
		//Pega parâmetros do componente "com_language"
		$paramsLanguage = ComponentHelper::getParams("com_languages");
		//Pega aplicação geral
		$app = Factory::getApplication();
		//Pega menu
		$menu = $app->getMenu('site');
		//Pega o item de menu referente ao ID passado por parâmetro
		$menuItem = $menu->getItem($idMenu);
		//Se não conseguir pegar o item de menu, retorna falso
		if(!$menuItem) {
			return false;
        }

        // Declara variavel que ira armazenar a url
        $URL = '';
        
        // Obtem status do plugin de filtro de idioma
        $pluginEnabled = PluginHelper::isEnabled('system', 'languagefilter');

        // Filtro de idioma na url ativo no site
        if($pluginEnabled){
            //Armazena idioma default do front-end.
            $defaultLanguage = $paramsLanguage->get("site");

            // Informado para a funcao uma tag de linguagem a ser utilizada
            if ($tagLanguage != ''){
                // Foca como languagem corrente a tag informada
                $currentLanguage = $tagLanguage;
            }
            // Utilizar idioma da navegacao no site
            else{
                // Armazena tag do idioma atual
                $currentLanguage = Factory::getLanguage()->getTag();
            }

            //Pega linguagem do item de menu
            $menuItemLanguage = $menuItem->language;

            // Idioma atual for igual o idioma do item de menu
            if($currentLanguage == $menuItemLanguage) {
                // Item de menu nao eh home: armazena alias do menu como url
                if (!$menuItem->home){
                    $URL = $menuItem->route;
                }
            } else {
                //Pega itens de menu associados
                $associatedMenuItens = Associations::getAssociations('com_menus', '#__menu', 'com_menus.item', $idMenu, 'id', 'alias', null);
                //Se existir um item de menu associado ao idioma entra no if
                if(isset($associatedMenuItens[$currentLanguage])) {
                    //Armazena o objeto que contém o idioma do item de menu associado ao idioma + string "id:alias"
                    $currentLanguageMenuItemEquivalent = $associatedMenuItens[$currentLanguage];
                    //Armazena o ID do item de menu associado ao idioma quebrando a string "id:alias"
                    $currentLanguageMenuItemEquivalentID = explode(":", $currentLanguageMenuItemEquivalent->id);
                    $currentLanguageMenuItemEquivalentID = $currentLanguageMenuItemEquivalentID[0];
                    //Armazena na variável agora o objeto completo do item de menu associado ao idioma
                    $currentLanguageMenuItemEquivalent = $menu->getItem($currentLanguageMenuItemEquivalentID);
                    // Item de menu nao eh home: armazena alias do menu como url
                    if (!$currentLanguageMenuItemEquivalent->home){
                        //Armazena o route do item de menu associado ao idioma 
                        $URL = $currentLanguageMenuItemEquivalent->route;
                    }
                    //Sobrescreve o idioma do menu usando o language do equivalente
                    $menuItemLanguage = $currentLanguageMenuItemEquivalent->language;
                } 
                // Nao existe item de menu associado
                else {
                    // Item de menu nao eh home: armazena alias do menu como url
                    if (!$menuItem->home){
                        $URL = $menuItem->route;
                    }
                }
            }

            //Verifica se o idioma default é diferente do idioma do menu que está pegando URL para inserir o sef na URL
            if($defaultLanguage != $menuItemLanguage) {

                // Item de menu esta habilitado para todos idiomas: pega idioma atual para colocar na url
                if ($menuItemLanguage == "*"){
                    $menuItemLanguage = $currentLanguage;
                }

                //Pega idiomas do site
                $languages = LanguageHelper::getLanguages('lang_code');

                // Ja esta em formato sef (Ex: 'en')
                if (strlen($menuItemLanguage) == 2){
                    $SEF = $menuItemLanguage;
                }

                // Nao esta vazio (estará no formato 'en-GB') e existe no array de idiomas
                else if(!empty($menuItemLanguage) && !empty($languages[$menuItemLanguage])){
                    $SEF = $languages[$menuItemLanguage]->sef;
                }

                //E depois concatena com a URL montada anteriormente através do route
                $URL = $SEF . "/" . $URL;
            }
        }

        // Filtro de idioma no site NAO esta ativo: obtem url sem SEF de idioma
        else{
            // Item de menu nao eh home: armazena alias do menu como url
            if (!$menuItem->home){
                $URL = $menuItem->route;
            }
        }
		
		// Setado para retornar url completa
		if($fullUrl) {
			// Retira barra do inicio da url armazenada ateh entao, caso possua
			if(!empty($URL) && ($URL[0] == "/")){
				$URL = substr($URL, 1);
			}

            // URL Rewriting esta habilitado: monta url sem colocar '/index.php/'
            if ($config->get('sef_rewrite') == 1){
                $URL =  Uri::root() . $URL;
            }
            else{
                $URL =  Uri::root() . "index.php/" . $URL;
            }
		}

		// Se a url terminar com uma barra no final, retira
		if (substr($URL, -1) == '/'){
			$URL = substr($URL, 0, -1);
		}

		return $URL;
	}

	/**
	*	Função que retorna a url base da plataforma No Boss Extensions para a realizacao de requisicoes
	*
	* 	@return 	String 		Url base da plataforma
	**/
	public static function getUrlNbExtensions(){
		// Objeto com dados do config
		$config = Factory::getConfig();

        // Obtem a tag do idioma que esta sendo navegado
        $currentLanguage = Factory::getLanguage()->getTag();
		$languages = LanguageHelper::getLanguages('lang_code');
		
        $langSef = '';

        // Language definido para navegacao no site: obtem sef do idioma
        if(!empty($languages[$currentLanguage])){
            $langSef = $languages[$currentLanguage]->sef;
        }
        // Pega o sef do idioma default
        else{
            //Pega parametros do componente "com_language"
            $paramsLanguage = ComponentHelper::getParams("com_languages");
            //Armazena idioma default do front-end.
            $defaultLanguage = $paramsLanguage->get("site");
            
            if(isset($languages[$defaultLanguage]->sef)){
                $langSef = $languages[$defaultLanguage]->sef;
            }
        }

        // Se idioma nao for pt ou de, forca que fique em ingles
        // TODO: qnd colocaramos um novo idioma no site, precisamos acrescentar ele aqui no in_array
        if(!in_array($langSef, array('pt', 'de'))){
            $langSef = '';
        }
		
		// Obtem a url definida no config (caso exista)
		$urlNbExtensions = $config->get('url_nb_extensions');

        // Url refinida no config: retorna ela mesmo
		if (isset($urlNbExtensions) && !empty($urlNbExtensions)){
			return $urlNbExtensions."/".$langSef;
        }

		// Retorna url do ambiente de producao
        return 'https://www.nobossextensions.com/'.$langSef;
	}
}
