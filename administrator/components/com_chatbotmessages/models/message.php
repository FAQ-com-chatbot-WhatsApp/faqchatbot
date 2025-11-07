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
 * Modelo para Message
 */
class ChatbotMessagesModelMessage extends AdminModel
{
	/**
	 * Tipo de tabela
	 */
	protected $table = '#__chatbot_messages';

	/**
	 * Nome da chave primária
	 */
	protected $_id = 'id';

	/**
	 * Método para obter o formulário
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_chatbotmessages.message', 'message', array('control' => 'jform', 'load_data' => $loadData));

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
		$data = Factory::getApplication()->getUserState('com_chatbotmessages.edit.message.data', array());

		if (empty($data)) {
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Método para salvar dados
	 */
	public function save($data)
	{
		// Processar dados do campo wait
		if (isset($data['wait'])) {
			$wait = $data['wait'];
			if (is_array($wait) && isset($wait['start']) && isset($wait['end'])) {
				$start = (int)$wait['start'];
				$end = (int)$wait['end'];

				if ($start >= 0 && $end >= $start) {
					$data['wait'] = json_encode(['start' => $start, 'end' => $end]);
				} else {
					$this->setError('Intervalo de espera inválido');
					return false;
				}
			}
		}

		return parent::save($data);
	}
}