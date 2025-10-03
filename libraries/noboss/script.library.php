<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

use Joomla\Filesystem\Folder;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Script de instalação da library utilizada por todas extensoes da No Boss
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
 *     throw new \RuntimeException(Text::_("Aqui mensagem"));
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

// Necessario para nao dar erro 500 de redeclaracao da classe p/ qnd eh update
if (!class_exists('NobossLibraryInstallerScript')){
    class NobossLibraryInstallerScript{

        /**
         * Metodo executado antes de qualquer outro no processo de instalacao / update
         *  - Esse é o momento que a instalacao / update pode ser cancelado
         *  - Antes dessa funcao, apenas o preflight do script de instalacao do pacote é executado
         *
         * SOBRE PROBLEMA DE REMOÇÃO DA LIBRARY:
         *  - Quando a library já existe no site, o Joomla remove ela antes de executar qualquer função deste arquivo. 
         *  - Isso ocorre somente com extensões do tipo Library, com os demais tipos de extensao não ocorre.
         *  - O problema disso é que se não tiver permissões suficientes no site, o Joomla devolve erro na atualização sem explicar o motivo.
         *  - Em maio de 2021 contornamos esses problemas colocando a instalação da library em um pacote. Desta forma, conseguimos validar as permissões antes do Joomla jogar o erro. Neste caso, exibimos uma mensagem bem explicativa sobre o problema e como resolver.
         * 
         * @param   string     $type        Tipo de intalações (install, update, discover_install)
         * @param   JInstaller $parent      Classe que chama esse metodo (pode acessar funcoes dela)
         *
         * @return  boolean  true caso deva ser instalada, false para cancelar a instalação
         */
        function preflight($type, $parent){
            if($type == 'uninstall'){
                return;
            }

        }

        /**
         * Metodo executado após o término da instalação / update
         * 
         * @param   string     $type        Tipo de intalações (install, update, discover_install)
         * @param   JInstaller $parent      Classe que chama esse metodo (pode acessar funcoes dela)
         */
        // FIXME: nao esta caindo mais nessa funcao e por isso colocamos ela no script de instalacao do pacote
        public function postflight($type, $parent){
            if($type == 'uninstall'){
                return;
            }           
            // $dirLayout = JPATH_SITE.'/layouts/noboss/';
            // if (!@is_dir($dirLayout)){
            //     if (!@mkdir($dirLayout, 0775))
            //     {
            //         Folder::create($dirLayout, 0775);
            //     }
            // }
            
            try{
                // Move pasta 'layouts/noboss' para local correto
                $this->installLayoutsFolder();
            } catch (\Exception $e){
                // Criado apenas para evitar que o Joomla jogue erro 500 que deixa a library removida caso tenha algum erro nao tratado
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'Warning');
            }

            //Factory::getCache()->clean('_system');
        }

        /**
         * Metodo executado apos a instalacao
         *  - Aqui podemos exibir textos fora de notices / warnings utilizanco 'echo' ou html direto
         * 
         * @param   JInstaller $parent      Classe que chama esse metodo (pode acessar funcoes dela)
         */
        function install($parent) {
        }
        
        /**
         * Metodo executado apos a atualizacao
         * - Aqui podemos exibir textos fora de notices / warnings utilizanco 'echo' ou html direto
         * - Utilizar tambem para colocar atualizacoes especificas de versoes (Ex: migrar dado no banco de estrutura antiga para nova)
         * - Para qual versao foi instalada (usar em alguma condicao), utilize $parent->get('manifest')->version
         * 
         * @param   JInstaller $parent      Classe que chama esse metodo (pode acessar funcoes dela)
         */
        function update($parent) {
        }

        /**
         * Metodo executado apos a desinstalacao
         * - Aqui podemos exibir textos fora de notices / warnings utilizanco 'echo' ou html direto
         * 
         * @param   JInstaller $parent      Classe que chama esse metodo (pode acessar funcoes dela)
         */
        public function uninstall($parent){
            try{
                // Remove a pasta layouts pertencente a library
                if(file_exists(JPATH_SITE.'/layouts/noboss')){
                    Folder::delete(JPATH_SITE.'/layouts/noboss');
                }
        } catch (\Exception $e){
            // Criado apenas para evitar que o Joomla jogue erro 500 que deixa a library removida caso tenha algum erro nao tratado
        }
        }
        
        /**
        * Metodo que verifica se library deve ser instalada 
        *   - Retorna true se nao existe instalada ou se versao esta desatualizada
        *
        * @return  Booelean True para fazer a instalação, false para não fazer
        */
        private function isUpdate($parent){
            // Diretorio da library nao existe no site
            if (!is_dir(JPATH_LIBRARIES . '/noboss')){
                return true;
            }
            
            // Obtem versao instalada da library no banco de dados
            $oldRelease = $this->geManifestCacheDb('version');
            
            // Library nao foi localizada no banco
            if(empty($oldRelease)){
                return true;
            }

            // Retorna true se versao existente esta desatualizada
            return version_compare( $parent->manifest->version, $oldRelease, '>' );
        }

        /**
        * Metodo que obtem no banco dados em cache da extensao
        *
        * @param   string   $name       Nome da variável a ler do cache
        * @param   string   $element    Representa a extensao que vamos verificar
        *
        * @return  mixed 	O valor da variavel requerida
        */
        function geManifestCacheDb($name, $element='noboss') {
            $db = Factory::getDbo();
            $query = $db->getQuery(true);

            $query->select("a.manifest_cache")
                ->from("#__extensions as a")
                ->where("element = '{$element}'");

            $db->setQuery($query);
            // Decoda o json
            $manifest = json_decode( $db->loadResult(), true );

            // Retorna a variável buscada
            return $manifest[$name];
        }

        /**
         * Metodo que move a pasta 'layouts/noboss' para o local correto no Joomla (na instalacao vem dentro da library)
         */
        public function installLayoutsFolder(){
            $dirLayoutLibrary = JPATH_ROOT.'/libraries/noboss/layouts/';
            $dirLayout = JPATH_ROOT.'/layouts/noboss/';

            // Confirma se a pasta layouts esta criada dentro da library
            if(is_dir($dirLayoutLibrary)){
                // Altera permissao da pasta de layouts dentro da library antes de copiar
                @chmod($dirLayoutLibrary, 0775);

                try{
                    // Copia pasta layouts da library da no boss para a pasta layouts correta
                    Folder::copy($dirLayoutLibrary, $dirLayout, '', true, true);
                    // Altera permissao da pasta layouts inserida no site do usuario
                    @chmod($dirLayout, 0775);
                    if(is_dir($dirLayoutLibrary)){
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
}