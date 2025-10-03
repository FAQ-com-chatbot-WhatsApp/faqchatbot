<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Component;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\Input\Input;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 *  Classe a ser estendida em componentes para controller de listagem de registros
 *  @author  Johnny Salazar Reidel
 * 
 *  Observacao: o funcionamento desta classe tem como requisito que o componente seja desenvolvido no modelo No Boss
 */
class NbControllerListComponent extends AdminController{
    /**
     * Metodo contrutor
     * 
     * Nota: estendo do Joomla para setar um prefixo default e carregar traducao da library
     *
     * @param   array                        $config   An optional associative array of configuration settings.
     *                                                 Recognized key values include 'name', 'default_task',
     *                                                 'model_path', and 'view_path' (this list is not meant to be
     *                                                 comprehensive).
     * @param   ?MVCFactoryInterface         $factory  The factory.
     * @param   ?CMSWebApplicationInterface  $app      The Application for the dispatcher
     * @param   ?Input                       $input    The Input object for the request
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSWebApplicationInterface $app = null, ?Input $input = null){
        // Se prefixo nao estiver definido, usa 'NOBOSS_COMPONENT' para utilizar constantes padroes da library
        if (empty($this->text_prefix)) {
            $this->text_prefix = 'NOBOSS_COMPONENT';
        }

        // Carrega arquivo traducao da library
        $assetsObject = new \Noboss\Library\Util\NbLoadextensionAssetsUtil('lib_noboss');
        $extensionPath = $assetsObject->getDirectoryExtension(false);
        Factory::getLanguage()->load("lib_noboss", $extensionPath);

        parent::__construct($config, $factory, $app, $input);
    }

    /**
     * Proxy para getModel
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  An optional associative array of configuration settings.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel  The model.
     */
     public function getModel($name = '', $prefix = 'Administrator', $config = ['ignore_request' => true]){
        if(empty($name)){
            $name = ucfirst($this->editView);
        }
        
        return parent::getModel($name, $prefix, $config);
    }
}
?>
