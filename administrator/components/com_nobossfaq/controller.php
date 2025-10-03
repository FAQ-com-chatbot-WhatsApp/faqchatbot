<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	com_nobossfaq
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

/**
 *  Modelo default de controller principal compativel com J3 e J4
 *  @author  Johnny Salazar Reidel
 * 
 *  Orientacao: edite somente o nome da classe e o array '$viewsEdit' da funcao $display
 */
class NobossfaqController extends JControllerLegacy {

	/**
	 * Metodo para mostrar a view
	 *
	 * @param   boolean  $cachable   Se true, a view de saída será armazenada em cache.
	 * @param   array    $urlparams  Um array de parâmetros seguros de url e os seus tipos de variáveis, para valores válidos veja {@link JFilterInput::clean()}.
	 *
	 *  @return  JControllerLegacy  This object to support chaining.
	 */
    public function display($cachable = false, $urlparams = array()){
        /**
         * Crie uma posicao de array $viewsEdit para cada view de edicao que tiver no componente
         *      - O valor para o indice do array $viewsEdit eh o alias da view de edicao
         *      - O valor para 'recordIdAlias' eh o alias do campo de id da view de edicao
         *      - O valor para 'viewListAlias' eh o alias da view que lista os registros
         */
        $viewsEdit['question'] = array('recordIdAlias' => 'id_faq', 'viewListAlias' => 'questions');
        $viewsEdit['group'] = array('recordIdAlias' => 'id_faqs_group', 'viewListAlias' => 'groups');

        $componentAlias         = $this->input->get('option');
        $componentClassPrefix   = $this->input->get('componentClassPrefix');
        $defaultView            = $this->input->get('defaultView');
		$view                   = $this->input->get('view', $defaultView);
		$layout                 = $this->input->get('layout', 'default');

        // Seta a view para caso nao esteja definida e tenhamos pego valor default
        $this->input->set('view', $view);

        // Carrega arquivo helper (altere a chamada caso o nome do arquivo e classe nao sigam o padrao que eh ter o mesmo alias do componente)
		JLoader::register($componentClassPrefix.'Helper', JPATH_ADMINISTRATOR . '/components/' . $componentAlias . '/helpers/'.strtolower($componentClassPrefix).'.php');

        // Usuario acessou layout de edicao
        if($layout == 'edit'){
            $id = $this->input->getInt($viewsEdit[$view]['recordIdAlias']);
            $viewList = $this->input->getInt($viewsEdit[$view]['viewListAlias']);
            $viewList = (!empty($viewList)) ? $viewList : $defaultView;

            // Usuario nao tem permissao para editar o registro: exibe mensagem e redireciona para view de listagem dos registros
            if (!$this->checkEditId($componentAlias.'.edit.'.$view, $id)){
                // Joomla 3
                if(version_compare(JVERSION, '4', '<')){
                    $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
                    $this->setMessage($this->getError(), 'error');
                }
                // Joomla a partir do 4
                else{
                    if (!\count($this->app->getMessageQueue())){
                        $this->setMessage(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
                    }
                }
                $this->setRedirect(JRoute::_('index.php?option='.$componentAlias.'&view='.$viewList, false));

                return false;
            }
        }

		return parent::display();
	}
}
