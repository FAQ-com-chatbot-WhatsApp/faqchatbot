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
use Joomla\Input\Input;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Versioning\VersionableControllerTrait;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Form\FormFactoryInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 *  Classe a ser estendida em componentes para controller de edicao de registros
 *  @author  Johnny Salazar Reidel
 * 
 *  Observacao: o funcionamento desta classe tem como requisito que o componente seja desenvolvido no modelo No Boss
 */
class NbControllerEditComponent extends FormController{
    use VersionableControllerTrait;  

    /**
     * Metodo contrutor
     * 
     * Nota: estendo do Joomla para setar um prefixo default e carregar traducao da library
     *
     * @param   array                        $config       An optional associative array of configuration settings.
     *                                                     Recognized key values include 'name', 'default_task',
     *                                                     'model_path', and 'view_path' (this list is not meant to be
     *                                                     comprehensive).
     * @param   ?MVCFactoryInterface         $factory      The factory.
     * @param   ?CMSWebApplicationInterface  $app          The Application for the dispatcher
     * @param   ?Input                       $input        Input
     * @param   ?FormFactoryInterface        $formFactory  The form factory.
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSWebApplicationInterface $app = null, ?Input $input = null, ?FormFactoryInterface $formFactory = null) {
        // Carrega arquivo traducao da library
        $assetsObject = new \Noboss\Library\Util\NbLoadextensionAssetsUtil('lib_noboss');
        $extensionPath = $assetsObject->getDirectoryExtension(false);
        Factory::getLanguage()->load("lib_noboss", $extensionPath);

        parent::__construct($config, $factory, $app, $input, $formFactory);
    }
    
    /**
     * Metodo para verificar se voce pode adicionar um novo registro.
     *
     * @param   array  $data  Uma matriz de dados de entrada.
     *
     * @return  boolean
     */
    protected function allowAdd($data = []){
        return $this->app->getIdentity()->authorise('core.create', $this->input->get('option'));
    }

    /**
     * Metodo para executar operacoes em lote.
     *
     * @param   object  $model  The model.
     *
     * @return  boolean  Verdadeiro se for bem-sucedido, falso caso contrario e se o erro interno for definido.
     */
    public function batch($model = null){
        $this->checkToken();

        // Set the model
        $model = $this->getModel($this->editView);

        // Preset the redirect
        $this->setRedirect('index.php?option='.$this->input->get('option').'&view='.$this->listView);

        return parent::batch($model);
    }
}
?>
