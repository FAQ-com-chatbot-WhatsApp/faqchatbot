<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\CalendarField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
// use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Campo de calendário da No Boss.
 */
class NbCalendarField extends CalendarField {

  protected $uniqId;

  public function setup(\SimpleXMLElement $element, $value, $group = null){
    $app = Factory::getApplication();
    $wa = $app->getDocument()->getWebAssetManager();

    $return = parent::setup($element, $value, $group);

    // Permite recebe uma constante de tradução para o parâmetro "format".
    $this->format = Text::_($this->format);

    // Gera id unico para o campo
    $this->uniqId = "time_".uniqid();

    // Setado markara
    if (!empty($this->format)){
        // Garante que jquery seja chamado antes
        $wa->useScript('jquery');
        
        $wa->registerAndUseScript('nobosscalendarfield', "https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js");

        // Formato para mascara do campo
        $formatMask = str_replace('%Y', '0000', str_replace(array('%m', '%d', '%H', '%M', '%S'), '00', $this->format));

        // Ativa marcara no campo
        $wa->addInlineScript("
            jQuery(function($) {
                $('[data-id=\"{$this->uniqId}\"] input').mask('{$formatMask}');
            });
        ");
    }

    return $return;
  }

  protected function getInput() {
    $html = parent::getInput();

    // Adiciona div externa com id
    $html = "<div data-id='{$this->uniqId}'>{$html}</div>";

    return $html;
  }
}
