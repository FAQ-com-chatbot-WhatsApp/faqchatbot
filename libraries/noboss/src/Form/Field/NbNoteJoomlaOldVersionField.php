<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\NoteField;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbNoteJoomlaOldVersionField extends NoteField {
	
	protected function getLabel() {
		// Se versao do Joomla for maior que a do requisito, retorna sem exibir o note
		if (JVERSION >=  $this->element['required_version']) {
			return;
		}

		return parent::getLabel();
	}
}
