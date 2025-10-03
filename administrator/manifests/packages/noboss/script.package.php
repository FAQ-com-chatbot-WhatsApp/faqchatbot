<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

use Joomla\Filesystem\Folder;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Installer\Installer;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Script de instalação dos pacotes (usado na library e todos pacotes mais externos)
 * - Esse script normalmente é executado após o script de instalacao do pacote
 * 
 * - Formatos para retornar mensagens de erro / warning:
 *     // Mensagem de erro (fundo vermelho), sem interromper a instalacao
 *     Factory::getApplication()->enqueueMessage(Text::_("Aqui mensagem"), 'Error');
 *           
 *     // Mensagem de warning (fundo amarelo), sem interromper a instalacao
 *     Factory::getApplication()->enqueueMessage(Text::_("Aqui mensagem"), 'Warning');
 *           
 *     // Mensagem de warning (fundo amarelo), interrompendo a instalacao (Joomla tb exibe mensagem de erro da instalacao)
 *     throw new RuntimeException(Text::_("Aqui mensagem"));
 *     return false;
 * 
 * 
 * - Exemplos de usos do $parent que é recebido como parametro nas funções:
 *     // Obter nova versao recem instalada
 *     $parent->get('manifest')->version;
 * 
 *     // Obter alias da extensao (Ex: 'mod_nobosscalendar)
 *     $parent->manifest->name;
 *       
 *     // Redirecionar usuario apos a instalacao (interessante usar no metodo install na instalacao de componentes nossos para redirecionar o usuario para a pagina principal do componente apos a instalacao)
 *     $parent->getParent()->setRedirectURL('index.php?option=com_helloworld');*
 *
 * 
 * - Sobre uso de constantes de tradução
 *    - Se você deseja que as KEYs desses idiomas sejam usadas na primeira instalação do componente, o arquivo de idioma .sys.ini deve ser armazenado na pasta do componente (admin/language/en-GB/en-GB.com_helloworld.sys.ini)
 *    - Quando ja temos a library instalada, podemos forcar o carregamento do idioma da library e usar as constantes dela
 */
class pkg_NobossInstallerScript{

    /**
     * Evento executado antes de qualquer outro no processo de instalacao / update
     *  - Esse é o momento que a instalacao / update pode ser cancelado
     *  - Essa funcao eh executada antes da preflight do script de instalacao de cada extensao do pacote
     *
     * @param   string     $type        Tipo de intalações (install, update, discover_install)
     * @param   Installer $parent      Classe que chama esse metodo (pode acessar funcoes dela)
     *
     * @return  boolean  true caso deva ser instalada, false para cancelar a instalação
     */
    function preflight($type, $parent){
        if($type == 'uninstall'){
            return;
        }

        // Verifica permissoes dos diretorios necessarios para instalacao
        if(!$this->checkDirectoryPermissions($parent)){ 
            return false;
        }

        // Instala cada extensao localizada no diretorio 'packages' do zip
        $this->installPackageExtras($parent);

        return true;
    }

    /**
     * Metodo executado após o término da instalação / update
     * 
     * @param   string     $type        Tipo de intalações (install, update, discover_install)
     * @param   Installer $parent      Classe que chama esse metodo (pode acessar funcoes dela)
     */
    function postflight($type, $parent){
        if($type == 'uninstall'){
            return;
        }
        
        $db = Factory::getDbo();

        $newPackageId = 0;
        
        $zipFiles = Folder::files(__DIR__, '.*\.zip$');
        
        // Pega o arquivo 
        $zipFile = reset($zipFiles);

        // Verifica se foi registrado o pacote no banco de dados
        if(substr($zipFile, 0, 3) == 'pkg'){
            $element = explode('.', $zipFile);
            $element = $element[0];
            $query = $db->getQuery(true);
            $query->select("a.extension_id")
                    ->from("#__extensions as a")
                    ->where("a.element = '{$element}'");
            $db->setQuery($query);
            $newPackageId = $db->loadResult();
        }

        // Atualiza as extensões para não estarem relacionadas ao pacote
        $query = $db->getQuery(true);
        $query->update("#__extensions as a, (SELECT b.extension_id FROM #__extensions as b WHERE b.element = 'pkg_noboss') as b")
            ->set("a.package_id = '{$newPackageId}'")
            ->where("a.package_id = b.extension_id");
        $db->setQuery($query);
        $result = $db->execute();

        // Deleta a linha do pacote no banco
        $query = $db->getQuery(true);
        $query->delete('#__extensions')
            ->where("element = 'pkg_noboss'");
        $db->setQuery($query);
        $result = $db->execute();

        // TODO: colocado aqui em 27/03/24 pq nao estava mais executando no script de instalacao da library
        $this->installLayoutsFolder();       
    }

    /**
     * Metodo executado apos a instalacao
     *  - Aqui podemos exibir textos fora de notices / warnings utilizanco 'echo' ou html direto
     * 
     * @param   Installer $parent      Classe que chama esse metodo (pode acessar funcoes dela)
     */
	function install($parent) {
        // Exibe mensagem personalizada de sucesso com dicas e propagandas da No Boss
        echo '<br>'.Text::_('SCRIPT_INSTALLATION_SUCCESS').'<br><br>';
	}
    
    /**
     * Metodo executado apos a atualizacao
     * - OBS: aqui nao pega warning ou 'echo'
     * 
     * @param   Installer $parent      Classe que chama esse metodo (pode acessar funcoes dela)
     */
	function update($parent) {
    }

    /**
     * Metodo que verifica permissoes de escrita nos diretorios necessarios
     * 
     * @param   Installer $parent      Classe que chama esse metodo (pode acessar funcoes dela)
     */
    function checkDirectoryPermissions($parent){
        // Eh instalacao somente da library
        //if (in_array('noboss.zip', (array) $parent->get('manifest')->files)){

        // Diretorios que precisam permissao de escrita / remocao
        $writingDirs = array();
        $writingDirs[] = '/libraries/';
        $writingDirs[] = '/layouts/';
        $writingDirs[] = '/layouts/noboss/';
        $writingDirs[] = '/modules/';
        $writingDirs[] = '/components/';
        $writingDirs[] = '/administrator/components/';
        $writingDirs[] = '/plugins/system/';
 
        // Array que ira armazenar diretorios que faltam permissao
        $errorDirs = array();

        // Percorre cada diretorio
        foreach ($writingDirs as $dir){
             // Diretorio existe e nao possui permissoes suficientes (conforme regras do Joomla)
            if ((is_dir(JPATH_SITE.$dir)) && (!Joomla\Filesystem\Path::canChmod(JPATH_SITE.$dir))){
                // Adiciona diretorio no array dos que possuem erro de permissao
                $errorDirs[] = $dir;
            }
        }

        
        // Pelo menos um diretorio nao possui permissao de escrita
        if(!empty($errorDirs)){
            // Mensagem a ser exibida para o usuario, informando junto os diretorios sem permissao
            $message = Text::sprintf('SCRIPT_INSTALLATION_LACK_PERMISSION_REST_CONTENT', "'".implode("', '", $errorDirs)."'");
            
            // Verifica se algum dos diretorios com erro sao de 'layouts' ou 'libraries'
            // $result =  array_filter($errorDirs, function($el) {
            //     return ((strpos($el, 'layouts') || (strpos($el, 'libraries'))) !== false);
            // });

            // Seta mensagem de aviso sem impedir que instalacao prossiga (alguns casos eh alarme falso)
            Factory::getApplication()->enqueueMessage($message, 'Warning');
        }

        return true;
    }

    /**
     * Metodo que instala as extensoes localizadas na pasta 'packages'
     *
     * @param   Installer $parent      Classe que chama esse metodo (pode acessar funcoes dela)
     */
    private function installPackageExtras($parent){
        if(!is_dir(__DIR__ . '/packages')){
            return;
        }

        // Obtem extensoes
        $packages = Folder::folders(__DIR__ . '/packages');

        // Percorre cada extensao do pacote
        foreach ($packages as $extension){
            $tmpInstaller = new Installer;
            try{
                $tmpInstaller->setPath('source', __DIR__ . '/packages/' . $extension);
                $tmpInstaller->findManifest();
                $manifest = $tmpInstaller->getPath('manifest');

                $manifestPath = Joomla\Filesystem\Path::clean($manifest);
                
                $element = preg_replace('/\.xml/', '', basename($manifestPath));
                
                $manifest = $tmpInstaller->getManifest();

                // Ajusta o element de acordo com o tipo de extensão
                if($manifest['type'] == 'component'){
                    if (strpos($element, 'com_') !== 0){
                        $element = 'com_' . $element;
                    }
                } else if ($manifest['type'] == 'plugin'){
                    // if (strpos($element, 'plg_') !== 0){
                    //     $element = 'plg_' . $element;
                    // }
                } else if ($manifest['type'] == 'package'){
                    if (strpos($element, 'pkg_') !== 0){
                        $element = 'pkg_' . $element;
                    }
                } else if ($manifest['type'] == 'module'){
                    if (strpos($element, 'mod_') !== 0){
                        $element = 'mod_' . $element;
                    }
                }
                else if ($manifest['type'] == 'library'){

                }

                // Obtem dados da extensao no banco (caso ela ja exista instalada)
                $extensionDataBase = $this->getExtensionByElement($element, $manifest['type']);

                // Extensao ja existe instalada (update)
                if(!empty($extensionDataBase)){
                    $extensionDataBase = json_decode($extensionDataBase->manifest_cache);

                    $installResult = false;

                    if(!empty($manifest)){
                        // Versao do zip eh maior que versao instalada: realiza nova instalacao (update)
                        if(version_compare($manifest->version, $extensionDataBase->version, '>')){
                            // Executa a instalação da extensao
                            $installResult = $tmpInstaller->install(__DIR__ . '/packages/' . $extension);
                        }
                    }
                } 
                // Extensao ainda nao existe (nova instalacao)
                else {
                    // Executa a instalação da extensao
                    $installResult = $tmpInstaller->install(__DIR__ . '/packages/' . $extension);
                    // Erro ao instalar
                    if(!$installResult){
                        // Seta Warning para exibir na tela
                        Factory::getApplication()->enqueueMessage(Text::sprintf('SCRIPT_INSTALLATION_EXTRA_ERROR_NO_INSTALLED', $extension), 'Warning');
                    }
                }
            } catch (\Exception $e){
                $error = $e->getMessage();
                // Seta Warning para exibir na tela
                Factory::getApplication()->enqueueMessage(Text::sprintf('SCRIPT_INSTALLATION_EXTRA_ERROR_EXCEPTION', $extension), 'Warning');
            } 

        }
    } 

    /**
     * Metodo que obtem dados da extensao no banco (caso ela ja exista instalada)
     *
     * @param   Installer $parent      Classe que chama esse metodo (pode acessar funcoes dela)
     */
    function getExtensionByElement($element, $type){
        $db = Factory::getDbo();

        $query = $db->getQuery(true);
        $query->select('*')
            ->from('#__extensions')
            ->where("element = '{$element}'");

        if(!empty($type)){
            $query->where("type = '{$type}'");
        }

        $db->setQuery($query, 0, 1);

        $result = $db->loadObject();
        return $result;
    }

    /**
     * Metodo que move a pasta 'layouts/noboss' para o local correto no Joomla (na instalacao vem dentro da library)
     */
    public function installLayoutsFolder(){
        $dirLayoutLibrary = JPATH_ROOT.'/libraries/noboss/layouts/';
        $dirLayout = JPATH_ROOT.'/layouts/noboss/';

        // Confirma se a pasta layouts esta criada dentro da library
        if(file_exists($dirLayoutLibrary)){
            // Altera permissao da pasta de layouts dentro da library antes de copiar
            @chmod($dirLayoutLibrary, 0775);

            try{
                // Copia pasta layouts da library da no boss para a pasta layouts correta
                Folder::copy($dirLayoutLibrary, $dirLayout, '', true, true);
                // Altera permissao da pasta layouts inserida no site do usuario
                @chmod($dirLayout, 0775);
                
                if(file_exists($dirLayoutLibrary)){
                    // Remove a pasta layouts da library
                    Folder::delete($dirLayoutLibrary);
                }
            } catch (\Exception $e){
                // Seta Warning para exibir na tela
                Factory::getApplication()->enqueueMessage('Installing or updating the extension In the Boss Library could not create the directory "layouts/noboss"', 'Warning');
            }
        }
    }

}
