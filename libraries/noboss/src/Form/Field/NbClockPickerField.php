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
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbClockPickerField extends FormField {

    protected $placement;
    protected $align;
    protected $autoclose;
    protected $donetext;

  	protected function getInput() {
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();
        
        // AAtributos que podem ser definidos no xml
		$this->hint      = $this->getAttribute('hint', '00:00');
		$this->class     = $this->getAttribute('class', 'input-mini');
		$this->placement = $this->getAttribute('placement', 'top');
		$this->align     = $this->getAttribute('align', 'left');
		$this->autoclose = $this->getAttribute('autoclose', 'true');
		$this->default   = $this->getAttribute('default', 'now');
		$this->donetext  = $this->getAttribute('donetext', 'Apply');

		// Adicionar css e js do plugin jquery
        $wa->useScript('jquery');

        $wa->registerAndUseScript('clockpicker-0-0-7', "https://cdnjs.cloudflare.com/ajax/libs/clockpicker/0.0.7/bootstrap-clockpicker.min.js");
        $wa->registerAndUseScript('jquery.mask-1.14.15', "https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js");
        $wa->registerAndUseStyle('nobossclockpicker', Uri::root()."libraries/noboss/assets/plugins/stylesheets/css/jquery-clockpicker.min.css");

        // Gera id unico para o campo
        $uniqId = "time_".uniqid();

        // Ativa clockpicker e marcara no campo
        $wa->addInlineScript("
            jQuery(function($) {
                $('[data-id-clockpicker=\"{$uniqId}\"]').clockpicker();
                $('[data-id-clockpicker=\"{$uniqId}\"] input').mask('{$this->hint}');
            });
        ");

        // Joomla esta na versao 4
        if (version_compare(JVERSION, '4.0', 'ge')){
            $wa->addInlineStyle('
                .clockpicker-popover {
                    font-size: .93rem;
                }
            ');	
        }
        // Joomla esta na versao abaixo de 4
        else{
            $wa->addInlineStyle('
                .clockpicker-align-left.popover > .arrow {
                    left: 25px;
                }
            ');
        }

		return '
			<div class="input-group input-append clockpicker" data-id-clockpicker="'.$uniqId.'" data-donetext="' . $this->donetext . '" data-default="' . $this->default . '" data-placement="' . $this->placement . '" data-align="' . $this->align . '" data-autoclose="' . $this->autoclose . '">
				<input class="' . $this->class . ' form-control" placeholder="' . $this->hint . '" name="' . $this->name . '" type="text" class="form-control" value="' . $this->value . '">
				
				<span class="input-group-addon input-group-append">
					<span class="btn btn-secondary">
						<span class="icon-clock"></span>
					</span>
				</span>
			</div>';
  	}
}
