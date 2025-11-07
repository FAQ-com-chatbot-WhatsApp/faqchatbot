<?php
/**
 * @package     Noboss.ChatbotMessages
 * @subpackage  com_chatbotmessages
 *
 * @copyright   Copyright (C) 2025 Noboss. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

/**
 * Modelo para Hook
 */
class ChatbotHooksModelHook extends AdminModel
{
	/**
	 * Tipo de tabela
	 */
	protected $table = '#__hooks';

	/**
	 * Nome da chave primária
	 */
	protected $_id = 'id';

	/**
	 * Método para obter o formulário
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_chatbothooks.hook', 'hook', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Método para carregar dados no formulário
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_chatbothooks.edit.hook.data', array());

		if (empty($data)) {
			$data = $this->getItem();
		}

		return $data;
	}
}