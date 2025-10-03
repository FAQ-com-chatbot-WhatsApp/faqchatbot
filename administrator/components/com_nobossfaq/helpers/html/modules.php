<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	com_nobossfaq
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2018 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

/**
 * Class JHtml para helper de módulos.
 */
abstract class JHtmlModules
{
	/**
	 * Exibe select com as opções de posições de módulos.
	 *
	 * @param   integer  $clientId          O Id do cliente 0 para área do "site" 1 para
	 * área de "administrador".
	 * @param   integer  $state O estado(state) do módulo (ativo = 1, desativado = 0 e na lixeira = -1).
	 * @param   string   $selectedPosition  A posição atualmente selecionada para o módulo.
	 * @return  string   As "options" necessárias para o select de posições de módulo.
	 */
	public static function positions($clientId, $state = 1, $selectedPosition = '')
	{
		// Registra class TemplatesHelper usando o componente de templates.
		JLoader::register('TemplatesHelper', JPATH_ADMINISTRATOR . '/components/com_templates/helpers/templates.php');

		// Pega os templates instalados.
		$templates = array_keys(NobossfaqHelper::getTemplates($clientId, $state));

		// Grupos de templates.
		$templateGroups = array();

		// Cria um option vazio para o select.
		$option = NobossfaqHelper::createOption();

		// Cria "agrupador" passando option vazio.
		$templateGroups[''] = NobossfaqHelper::createOptionGroup('', array($option));

		// Flag para saber se é template de posições.
		$isTemplatePosition = false;

		// Percorre templates.
		foreach ($templates as $template)
		{
			// Cria array vazio para opções do select.
			$options = array();

			// Pega posições de módulo do template.
			$positions = TemplatesHelper::getPositions($clientId, $template);

			// Verifica se $positions é um array.
			if (is_array($positions))
			{
				// Percorre todas as posições de módulo.
				foreach ($positions as $position)
				{
					// Pega nome da posição de módulo e a posição.
					$text = NobossfaqHelper::getTranslatedModulePosition($clientId, $template, $position) . ' [' . $position . ']';

					/* Cria um option para posição e adiciona a array de $options. */
					$options[] = NobossfaqHelper::createOption($position, $text);

					/* Verifica se não é uma posição de template e se posição selecionada é igual a
					posição corrente. */
					if (!$isTemplatePosition && $selectedPosition === $position)
					{
						// Marca flag é um posição de template como true.
						$isTemplatePosition = true;
					}
				}

				// Organiza options em ordem alfabética pelo conteúdo de cada option.
				$options = JArrayHelper::sortObjects($options, 'text');
			}

			/* Cria um option "agrupador" a partir do options do template e adiciona a liste de grupos
			de templates. */
			$templateGroups[$template] = NobossfaqHelper::createOptionGroup(ucfirst($template), $options);

		}

		// Constante para descrição de option com grupos customizados.
		$customGroupText = JText::_('COM_NOBOSSFAQ_TEMPLATES_CUSTOM_POSITION');

		// Flag para posições que são customizadas.
		$editPositions = true;

		// Pega posições customizadas.
		$customPositions = NobossfaqHelper::getPositions($clientId, $editPositions);

		/* Cria um option "agrupador" para com options de posições de módulo customizadas.*/
		$templateGroups[$customGroupText] = NobossfaqHelper::createOptionGroup($customGroupText, $customPositions);

		return $templateGroups;
	}
}
