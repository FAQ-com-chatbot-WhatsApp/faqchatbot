<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Component;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\BaseController;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 *  Classe a ser estendida em componentes para controller principal (display)
 *  @author  Johnny Salazar Reidel
 * 
 *  Observacao: o funcionamento desta classe tem como requisito que o componente seja desenvolvido no modelo No Boss
 */
class NbControllerDisplayComponent extends BaseController{
    /**
     * View default (nao precisa preencher)
     *
     * @var    string
     */
    protected $default_view = '';

    /**
     * Metodo para exibir uma view.
     *
     * @param   boolean  $cachable   Se verdadeiro, a saída da visualizacao sera armazenada em cache
     * @param   array    $urlparams  Uma serie de parametros de URL seguros e seus tipos de variaveis.
     *                   @see        \Joomla\CMS\Filter\InputFilter::clean() para valores validos.
     *
     * @return  static|boolean   Este objeto suporta encadeamento ou false em caso de falha.

     */
    public function display($cachable = false, $urlparams = false){
        // View corrente
        $view   = $this->input->get('view', $this->input->get('defaultView'));
        
        // Layout corrente
        $layout = $this->input->get('layout', 'default');

        $this->default_view = $this->input->get('defaultView');

        // Usuario acessou layout de edicao
        if(($layout == 'edit') && (!empty($this->viewsEdit[$view]))){
            $id = $this->input->getInt($this->viewsEdit[$view]['recordIdAlias']);
            $viewList = $this->viewsEdit[$view]['viewListAlias'];

            // Usuario nao tem permissao para editar o registro: exibe mensagem e redireciona para view de listagem dos registros
            if (!$this->checkEditId($this->input->get('option').'.edit.'.$view, $id)) {
                if (!\count($this->app->getMessageQueue())) {
                    $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
                }

                $this->setRedirect(Route::_('index.php?option='.$this->input->get('option').'&view='.$viewList, false));

                return false;
            }
        }

        parent::display();

        return $this;
    }
}
?>
