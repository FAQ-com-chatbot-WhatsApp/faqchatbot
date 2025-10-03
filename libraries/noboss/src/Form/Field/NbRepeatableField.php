<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbRepeatableField extends FormField {

	protected function getInput() {

		$input = Factory::getApplication()->input;
		$id = $input->get->get("id");

		$load = Text::_('COM_NBFORMMAIL_REPEATABLE_CARREGAR_CAMPOS');
		$add = Text::_('COM_NBFORMMAIL_REPEATABLE_ADICIONAR_CAMPO');

		if($id != null) {
			$html = <<<HTML
			<div class="btn-field-geren">
				<input class="btn btn-nb btn-primary btn-success" type="button" name="noboss_loader" id="noboss_loader" value="$load">
			</div>
			<div class="fields"></div>
HTML;
		}
		else {
			$html = <<<HTML
			<div class="btn-field-geren">
				<input class="btn btn-nb btn-primary btn-success" type="button" name="noboss_repeater" id="noboss_repeater" value="$add">
			</div>
			<div class="fields"></div>
HTML;
		}

		return $html;
	}
}
