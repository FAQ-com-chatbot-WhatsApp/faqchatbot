<?php
/**
 * @package     Noboss.ChatbotMessages
 * @subpackage  com_chatbotmessages
 *
 * @copyright   Copyright (C) 2025 Noboss. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View para listar Messages
 */
class ChatbotHooksViewHooks extends JViewLegacy
{
	/**
	 * Lista de items
	 */
	protected $items;

	/**
	 * Objeto de paginação
	 */
	protected $pagination;

	/**
	 * Estado do filtro
	 */
	protected $state;

	/**
	 * Método para exibir a view
	 */
	public function display($tpl = null)
	{
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Verificar erros
		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Método para adicionar toolbar
	 */
	protected function addToolbar()
	{
		ToolbarHelper::title(Text::_('COM_CHATBOTHOOKS_HOOKS_TITLE'), 'link');

		ToolbarHelper::addNew('hook.add');
		ToolbarHelper::editList('hook.edit');
		ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'hooks.delete');

		ToolbarHelper::preferences('com_chatbothooks');
	}

	/**
	 * Método para obter o campo de ordenação
	 */
	protected function getSortFields()
	{
		return array(
			'h.created_at' => Text::_('COM_CHATBOTHOOKS_HEADING_CREATED'),
			'h.description' => Text::_('COM_CHATBOTHOOKS_HEADING_DESCRIPTION'),
			'h.updated_at' => Text::_('COM_CHATBOTHOOKS_HEADING_UPDATED'),
		);
	}
}