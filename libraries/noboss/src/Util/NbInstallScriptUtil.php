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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Log\Log;
use Noboss\Library\Util\NbCurlUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbInstallScriptUtil{

    /**
     * Recebe o token do usuário e manda a url do site atual para o servidor da noboss
     *
     * @param String $token token da licença do usuário
     * @return void
     */
    public static function saveAuthorizedUrl($token){
        // Inicializa a curl
        $ch = curl_init();
        $url = self::getUrlNbExtensions(). '/index.php?option=com_nbextensoes&task=externallicenses.changeAuthorizedUrl&format=raw';

        // Dados a enviar na requisicao
        $dataPost = array(
            'token' => $token,
            'newUrl' => (str_replace(array('https://www.', 'http://www.', 'https://', 'http://'), '', Uri::root())),
            'overwrite' => false,
            'jversion' => JVERSION
        );
        NbCurlUtil::request('POST', $url, $dataPost, null, 10, null, null);
    }

    /**
     * Verifica se extensao pode ser atualizada a partir de um token
     *
     * @param   String      $token      Token da extensao
     * @return 	Mixed       Boolean 1 se update estiver em dia, 0 se nao estiver em dia ou 'INVALID_TOKEN' se nao localizado
     */
    public static function updateLicenseIsValid($token){
        // Inicializa a curl
        $url = self::getUrlNbExtensions(). 'index.php?option=com_nbextensoes&task=externallicenses.updateLicenseIsValid&format=raw';
        $dataPost = array(
            'token' => $token
        );

        // Realiza requisicao curl
        $result = NbCurlUtil::request('POST', $url, $dataPost, null, 20, null, null, true);

        return $result->data->body;
   }

   /** 
     * Remove registro da url do site na base da No Boss
     *
     * @param   String      $token      Token da extensao
     * @return 	Boolean      True ou false
     */
    public static function removeUrlSite($token){
        // Inicializa a curl
        $url = self::getUrlNbExtensions(). 'index.php?option=com_nbextensoes&task=externallicenses.removeUrlSite&format=raw';

        $dataPost = array(
            'token' => $token,
            'url' => base64_encode(str_replace(array('https://www.', 'http://www.', 'https://', 'http://'), '', Uri::root()))
        ); 

        // Realiza requisicao curl
        $result = NbCurlUtil::request('POST', $url, $dataPost, null, 20, null, null, true);

        return $result->data->body;
   }

    /**
     * Recebe o nome de extensão e o valor da coluna extra_query e atualiza
     *
     * @param String $extensionName Name da extensão
     * @param String $extraQuery valor para a coluna extra_query
     */
    public static function updateExtraQuery($parent, $extraQuery){
        // Pega o nome e os servidores de update da extensão
        $extName = $parent->getName();
        $servers = (array) $parent->getManifest()->updateservers;

        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        // Para cada servidor de update
        foreach ($servers as $server) {
            // Busca o id od servidor de update
            $query = $db->getQuery(true);
            $query
            ->select('update_site_id')
            ->from('#__update_sites')
            ->where("location = '{$server}' AND enabled = '1'");
            $db->setQuery($query);
            $id = $db->loadResult();

            // Caso tenha encontrado, apenas faz update do extra_query
            if(!empty($id)){
                $query = $db->getQuery(true);
                $query->update("#__update_sites")
                    ->set("extra_query = '{$extraQuery}'")
                    ->where("update_site_id = '{$id}'");
                $db->setQuery($query);
                $db->execute();
            // Caso não exista no banco, insere um novo registro
            } else {
                $query = $db->getQuery(true);
                $query->insert("#__update_sites")
                    ->columns('name, type, location, enabled, extra_query')
                    ->values("'{$extName}', 'extension', '{$server}', 1, '{$extraQuery}'");
                $db->setQuery($query);
                $db->execute();
            }
        }
    }

    /**
    * Retorna o numero de extensões instaladas no site, não incluindo os extra.
    *
    *  @param		String		Library name of XML
    *
    *  @return		Array		Um array com as extensões dependentes da library
    */
    public static function getDependencies($extraExtensions){
        // Faz uma busca no  banco, na tabela extensions
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        // Array para as buscas
        $likeArray = array('com_noboss', 'plg_noboss', 'mod_noboss', 'tpl_noboss');
        //Monta a query para buscar na tabella extension
        $query->select("COUNT(1)")
        ->from("#__extensions")
        ->where("element NOT IN ('".implode("', '", $extraExtensions)."')");
        $val = '(';
        foreach($likeArray as $key => $like){
            if($key != 0){
                $val .= ' OR ';
            }
            $val .= "element LIKE '{$like}%'";
        }
        $val .= ')';
        $query->where($val);

        $db->setQuery($query);
        $result = $db->loadResult();

        return $result;
    }

    public static function getLibraryUpdateId($parent){
        // Pega o alias da library
        $libElement = $parent->getElement();

        // Faz uma busca no  banco, na tabela extensions
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        //Monta a query para buscar na tabella extension
        $query->select("b.update_id")
                ->from("#__extensions as a")
                ->join('INNER',"#__updates as b ON a.extension_id = b.extension_id")
                ->where("a.element = 'noboss'");
        $db->setQuery($query);
        $result = $db->loadResult();

        return (int) $result;
    }

    /**
     * Busca por todos os extras e desinstala um por um
     *
     * @return void
     */
    public static function uninstallExtras($extraExtensions){
        $extras = self::getExtrasList($extraExtensions);
        // Para cada extra, executa o processo de desinstalção
        foreach ($extras as $extra) {
            $tmpInstaller = new Installer;
            // Tenta fazer a desinstalação
            try{
                $result = $tmpInstaller->uninstall($extra->type, $extra->extension_id);
                if(!$result){
                    throw new \RuntimeException();
                }
            } catch (\Exception $e){
                // Verifica se existe a constante, caso não exista, monta um texto fixo
                if($msg = Text::sprintf('SCRIPT_EXTRAS_UNINSTALL_ERROR', $extra->element) == 'SCRIPT_EXTRAS_UNINSTALL_ERROR'){
                    $msg = "<p>Houve um erro durante a desinstalação do pacote: {$extra->element}</p>";
                }
                Log::add($msg, Log::WARNING, 'jerror');
            }
        }
    }

    /**
     * Busca no banco por todos os extras
     *
     * @return Array Retorna um array com informações dos extras
     */
    public static function getExtrasList($extraExtensions){
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        //Monta a query para buscar na tabela extension
        $query->select('extension_id, element, type')
        ->from($db->quoteName('#__extensions'))
        ->where("element IN('".implode("', '", $extraExtensions)."')");

        $db->setQuery($query);
        $result = $db->loadObjectList();
        return $result;
    }

    /**
     * Busca por todas as extensões que estão no mesmo pacote que o parametro
     *
     * @param string $element alias do pacote que estamos removendo (Ex: 'pkg_nobossembed')
     * @return array Array com os id das extensçoes que estão no mesmo pacote
     */
    public static function getPackageExtensions($elementPack) {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        //Monta a query para buscar na tabela extension
        $query->select('a.extension_id, a.type')
        ->from('#__extensions AS a')
        ->where("a.package_id = (SELECT b.package_id FROM #__extensions AS b WHERE b.element='{$elementPack}') AND a.package_id != 0 AND a.extension_id != '{$extensionId}'");

        $db->setQuery($query);
        $result = $db->loadObjectList();
        return $result;
    }

    /**
     * Desistala cada extensão passada no array
     *
     * @return void
     */
    public static function uninstallPackageExtensions($extensions){
        $tmpInstaller = new Installer;
        foreach ($extensions as $extension) {
            // Caso não seja o pacote em si
            if($extension->type != 'package'){
                // Tenta fazer a desinstalação
                $tmpInstaller->uninstall($extension->type, $extension->extension_id);
            } else {
                // Caso seja o pacote, deleta do banco
                $db = Factory::getDbo();
                $query = $db->getQuery(true);
                //Monta a query para buscar na tabela extension
                $query
                    ->delete('#__extensions')
                    ->where("extension_id = {$extension->extension_id}");
        
                $db->setQuery($query);
                $db->execute();
            }
        }
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

        /* TODO: modificado para pegar sef do idioma direto no idioma corrente cortando estring (ex: extraimos 'pt' de 'pt-BR')
                - Anteriormente buscavamos o sef dos idiomas de conteúdo instalados, mas isso poderia dar problema pq eles podem estar desabilitados sem que o acesso pelo idioma esteja desabilitado
        */
        // $languages = LanguageHelper::getLanguages('lang_code');
        // $langSef = $languages[$currentLanguage];
        // $langSef = $langSef->sef;
        $langSef = substr($currentLanguage, 0, 2);

        // TODO: qnd colocarmos o idioma ingles como default, eh necessario invester a ordem para setar 'pt' somente se usuario estiver em pt

        // Idioma que esta sendo navegado nao eh portugues brasil: forca para colocar idioma ingles na navegacao
        if($langSef != 'pt'){
            $langSef = '/en';
        }
        // Deixa sem tag de idioma para pegar portugues
        else{
            $langSef = '/';
        }

		// Obtem a url definida no config (caso exista)
		$urlNbExtensions = $config->get('url_nb_extensions');

        // Url refinida no config: retorna ela mesmo
		if (isset($urlNbExtensions) && !empty($urlNbExtensions)){
			return $urlNbExtensions.$langSef;
        }

		// Retorna url do ambiente de producao
        return 'https://www.nobossextensions.com'.$langSef;
	}

    /**
     * Funcao que verifica se pasta 'noboss' esta criada dentro de 'layouts' e realiza acoes qnd necessario
     * 
     * Utilizado na area admin dos nossos componentes
     */
    public static function checkLibraryLayoutFolder(){
        // Diretorio 'noboss' nao existe na pasta 'layouts' (provavelmente nao criou na instalacao da library)
        if(!is_dir(JPATH_SITE.'/layouts/noboss')) {
            $urlLibrary = 'https://www.nobossextensions.com/installation/nobosslibrary';

            // TODO: nao podemos tentar instalar a library por conta pq em muitos casos o problema durante a instalacao gera pagina em branco no site
            // // Inicia instalacao forcada da library
            // try {
            //     // Adiciona diretorio de models do componente installer do Joomla
            //     JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models');
            //     // Instancia model de extensoes
            //     $modelInstaller = JModelLegacy::getInstance('install', 'InstallerModel');
            //     $input = Factory::getApplication()->input;
            //     // Seta para utilizar metodo de instalacao via url
            //     $input->set('installtype', 'url');

            //     // Seta a url de instalacao da library
            //     $input->set('install_url', $urlLibrary);
            //     // Executa metodo de instalacao
            //     $modelInstaller->install();
            // } catch (\Exception $e) {
            //     // Nao tem tratamento de erro pq o Joomla joga diversas vezes a falta de permissoes como erro sendo que ocorreu tudo certo
            // }

            // // Mesmo apos tentar instalar library novamente, diretorio 'noboss' ainda nao existe na pasta 'layouts'
            // if(!is_dir(JPATH_SITE.'/layouts/noboss')) {
                $urlDocPermission = 'https://docs.nobosstechnology.com/extensoes-joomla/ajustando-permissoes-de-diretorios-e-arquivos-no-joomla';
                
                // Seta mensagem a ser exibida orientando sobre o problema
                $message = "<br>
                We have identified that the 'No Boss Library' that is required for this extension to work is not installed correctly on the site.<br><br>
                The most likely problem is the lack of permissions on the site directory 'layouts/noboss'. <br>
                When the library is installed or updated, it tries to make changes to this directory. <br><br>               
                To resolve the issue, follow the two steps below:<br>
                1. Correct the permissions of the 'layouts/noboss' directory. If you have questions about how to do it, visit our <a href='{$urlDocPermission}' target='_blank'>documentation on setting Joomla file and directory permissions</a>.' <br>
                2. Install the 'No Boss Library' library using url <a href='{$urlLibrary}' target='_blank'>{$urlLibrary}</a>.<br><br>
                If the problem persists, contact the support team. <br><br>";

                // Exibe mensagem como warning (fundo amarelo)
                Factory::getApplication()->enqueueMessage($message, 'Warning');

                return false;
            // }
            // // Agora esta ok
            // else{
            //     // Recarrega a pagina com a library ja instalada
            //     header("Refresh:5");
            //     exit;
            // }
        }

        return true;
    }
}
