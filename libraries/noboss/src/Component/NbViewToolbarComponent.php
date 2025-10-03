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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 *  Classe com metodos para exibir barra de navegacao nos componentes
 *  @author  Johnny Salazar Reidel
 * 
 *  Observacao: essa classe pode ser utilizado em qualquer componente, sem necessidade de seguir o modelo No Boss
 */

class NbViewToolbarComponent{
    /**
	 * Metodo que adiciona botoes e titulo na view de edicao
	 *
     * @param   String      $viewTitle              Titulo da view
     * @param   String      $viewSubtitle           Segunda parte do titulo da view
     * @param   String      $viewIcon               Icone para exibir junto com o titulo
	 * @param   String      $recordIdAlias          Alias do campo de id do componente
     * @param   String      $componentAlias         Alias do componente
     * @param   String      $viewAlias              Alias da view
     * @param   Object      $item                   Item com os dados
	 *
	 */
	public static function addToolbarEditView($viewTitle, $viewSubtitle, $viewIcon, $recordIdAlias, $componentAlias, $viewAlias, $item) {
		// Desabilita menu principal.
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user = Factory::getApplication()->getIdentity();
		$userId = $user->get('id');
		$isNew = isset($item->{$recordIdAlias}) && $item->{$recordIdAlias} != 0 ? 0 : 1;
		$checkedOut	= isset($item->checked_out) ? (!($item->checked_out == 0 || $item->checked_out == $userId)) : 0;

        // Obtem permissoes do componente
		$canDo = ContentHelper::getActions($componentAlias);

        // Instancia objeto toolbar
        $toolbar = Toolbar::getInstance('toolbar');

        $viewTitle = Text::_($viewTitle) . (!empty($viewSubtitle) ? ': ' . Text::_($viewSubtitle) : '');
		
		// Titulo da pagina com icone
		ToolbarHelper::title($viewTitle, $viewIcon);

        //$this->viewAlias = $viewAlias;

        $idUserRegister = '';

        // Definido id do usuario que criou o registro no formato de alias 'created_user_id'
        if(isset($item->created_user_id)){
            $idUserRegister = $item->created_user_id;
        }

        // Definido id do usuario que criou o registro no formato de alias 'created_by'
        if(isset($item->created_by)){
            $idUserRegister = $item->created_by;
        }

		// Novo registro e usuario tem permissao de criacao
		if ($isNew && $canDo->get('core.create')) {
			$toolbar->apply($viewAlias.'.apply');

            $saveGroup = $toolbar->dropdownButton('save-group');

            $saveGroup->configure(
                function (Toolbar $childBar) use ($viewAlias) {
                    $childBar->save($viewAlias.'.save');
                    $childBar->save2new($viewAlias.'.save2new');
                }
            );

            $toolbar->cancel($viewAlias.'.cancel', 'JTOOLBAR_CANCEL');
		}
        // Edicao de registro
		else {
            // Verifica a permissao de edicao geral e edicao de registros proprios
            if(!empty($idUserRegister)){
                $itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $idUserRegister == $userId);
            }
            // Verifica a permissao de edicao geral apenas
            else{
                $itemEditable = $canDo->get('core.edit');
            }

			// Registro nao esta bloqueado e usuario tem permissao de edicao
			if ((!$checkedOut) && ($itemEditable)){
                $toolbar->apply($viewAlias.'.apply');
			}

            $saveGroup = $toolbar->dropdownButton('save-group');

            $saveGroup->configure(
                function (Toolbar $childBar) use ($checkedOut, $itemEditable, $canDo, $viewAlias) {
                    // Registro nao esta com checkout e usuario pode editar
                    if (!$checkedOut && $itemEditable) {
                        $childBar->save($viewAlias.'.save');

                        // Verifica se tem permissao para criar registro
                        if ($canDo->get('core.create')) {
                            $childBar->save2new($viewAlias.'.save2new');
                        }
                    }

                    // Usuario tem permissao de criar registro: habilitar botao de criar copia
                    if ($canDo->get('core.create')) {
                        $childBar->save2copy($viewAlias.'.save2copy');
                    }
                }
            );

            $toolbar->cancel($viewAlias.'.cancel');

            // TODO: criar recurso generico ainda para botao de versionamento (qnd possuir)
            // if (ComponentHelper::isEnabled('com_contenthistory') && $state->params->get('save_history', 0) && $itemEditable){
			// 	$toolbar->versions('com_tags.tag', $this->item->id);
			// }
		}

        $toolbar->divider();
	}

    /**
	 * Metodo que adiciona botoes e titulo na view de listagem
	 *
     * @param   String      $viewTitle              Titulo da view
     * @param   String      $viewIcon               Icone para exibir junto com o titulo
     * @param   String      $componentAlias         Alias do componente
     * @param   String      $viewAlias              Alias da view
     * @param   String      $createViewAlias        Alias da view de criacao de novos registros
     * @param   Object      $state                  Objeto com informacoes de status da view
     * @param   Boolean     $isEmptyState           Informa se esta sendo exibida tmpl emptystate
     * @param   Array       $btnActions             Botoes que devem ser exibidos no agrupador 'actions'
	 *
	 */
	public static function addToolbarListView($viewTitle, $viewIcon, $componentAlias, $viewAlias, $createViewAlias, $state, $isEmptyState, $btnActions) {
		$user  = Factory::getApplication()->getIdentity();

        // Carrega arquivo traducao da library
        $assetsObject = new \Noboss\Library\Util\NbLoadextensionAssetsUtil('lib_noboss');
        $extensionPath = $assetsObject->getDirectoryExtension(false);
        Factory::getLanguage()->load("lib_noboss", $extensionPath);

        // Obtem permissoes do componente
		$canDo = ContentHelper::getActions($componentAlias);

        // Instancia objeto toolbar
        $toolbar = Toolbar::getInstance('toolbar');
		
		// Titulo da pagina com icone
		ToolbarHelper::title(Text::_($viewTitle), $viewIcon);

		// Usuario tem permissao de criacao
		if ($canDo->get('core.create')) {
			ToolbarHelper::addNew($createViewAlias.'.add');
        }

        // Nao esta setado isEmptyState e usuario tem permissao de alterar status OU usuario tem permissao admin
        if (!$isEmptyState && ($canDo->get('core.edit.state') || $user->authorise('core.admin'))){
            // Cria botao 'actions' que ira agrupar os demais botoes de acao
            $dropdown = $toolbar->dropdownButton('status-group', 'JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            // Usuario tem permissao de alterar status
            if ($canDo->get('core.edit.state')){
                if(in_array("publish", $btnActions)){
                    $childBar->publish($viewAlias.'.publish')->listCheck(true);
                }
                if(in_array("unpublish", $btnActions)){
                    $childBar->unpublish($viewAlias.'.unpublish')->listCheck(true);
                }
                if(in_array("archive", $btnActions)){
                    $childBar->archive($viewAlias.'.archive')->listCheck(true);
                }
            }

            // Usuario tem permissao de admin: botao para desbloquear itens
            if ($user->authorise('core.admin') && in_array("checkin", $btnActions)){
                $childBar->checkin($viewAlias.'.checkin')->listCheck(true);
            }

            // Usuario tem permissao para editar status e filtro nao esta selecionado para lixeira: botao de lixeira
            if ($canDo->get('core.edit.state') && $state->get('filter.state') != -2 && in_array("trash", $btnActions)){
                $childBar->trash($viewAlias.'.trash')->listCheck(true);
            }

            // Botao de execucao em lote
            if ($canDo->get('core.create') && $canDo->get('core.edit') && $canDo->get('core.edit.state') && in_array("batch", $btnActions)) {
                // Joomla 5 ou superior
                if(version_compare(JVERSION, '5', '>=')){
                    $childBar->popupButton('batch', 'JTOOLBAR_BATCH')
                    ->popupType('inline')
                    ->textHeader(Text::_('NOBOSS_COMPONENT_BATCH_OPTIONS'))
                    ->url('#joomla-dialog-batch')
                    ->modalWidth('800px')
                    ->modalHeight('fit-content')
                    ->listCheck(true);
                }
                // Joomla 4
                else{
                    $childBar->popupButton('batch', 'JTOOLBAR_BATCH')
                    ->selector('collapseModal')
                    ->listCheck(true);
                }
            }
        }

        // Nao esta setado isEmptyState, usuario esta com filtro de status despublicado ou ativou delete para todas paginas
        if (!$isEmptyState && $canDo->get('core.delete') && (($state->get('filter.published') == -2) || (in_array("delete", $btnActions)))) {
            $toolbar->delete($viewAlias.'.delete', 'NOBOSS_COMPONENT_DELETE_FROM_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        // Botao de configuracoes globais do componente
        if ($canDo->get('core.admin') || $canDo->get('core.options')){
			ToolbarHelper::preferences($componentAlias);
		}
	}
}
