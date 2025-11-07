<?php
/**
 * @package     Noboss.ChatbotHooks
 * @subpackage  com_chatbothooks
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

class ChatbotHooksViewHooks extends JViewLegacy
{
    protected $items;
    protected $pagination;
    protected $state;

    public function display($tpl = null)
    {
        $this->items       = $this->get('Items');
        $this->pagination  = $this->get('Pagination');
        $this->state       = $this->get('State');
        $this->filterForm  = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar()
    {
        ToolbarHelper::title(Text::_('COM_CHATBOTHOOKS_HOOKS_TITLE'), 'link');
        ToolbarHelper::addNew('hook.add');
        ToolbarHelper::editList('hook.edit');
        ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'hooks.delete');
        ToolbarHelper::preferences('com_chatbothooks');
    }

    protected function getSortFields()
    {
        return [
            'h.created_at'   => Text::_('COM_CHATBOTHOOKS_HEADING_CREATED'),
            'h.description'  => Text::_('COM_CHATBOTHOOKS_HEADING_DESCRIPTION'),
            'h.updated_at'   => Text::_('COM_CHATBOTHOOKS_HEADING_UPDATED'),
        ];
    }
}
