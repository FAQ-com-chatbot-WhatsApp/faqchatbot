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
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbInstagramLoginField extends FormField {

    protected function getInput(){
        // Verifica qual botão deve aparecer e qual deve ficar escondido
        $logoffBtn = 'style="display:none;"';
        $loginBtn = '';
        if (!empty($this->value)) {
            $logoffBtn = '';
            $loginBtn = 'style="display:none;"';
        }
        
        $html = "<a data-id='instagram-logoff-btn' class='btn btn-nb btn-primary btn-instagram' ".$logoffBtn.">
                    <span class='fa fa-instagram login-icon'></span>
                    ". Text::_('LIB_NOBOSS_FIELD_NOBOSSINSTAGRAMLOGIN_BTN_LOGOFF_LABEL') ."
                </a>";
        $html .= "<a data-id='instagram-login-btn' class='btn btn-nb btn-primary btn-instagram' ".$loginBtn.">
                    <span class='fa fa-instagram login-icon'></span>
                    ". Text::_('LIB_NOBOSS_FIELD_NOBOSSINSTAGRAMLOGIN_BTN_LOGIN_LABEL') ."
                </a>";

        // Decoda o valor json salvo
        if (!empty($this->value)){
            $hideLoggedInfo = "";
            try{
                $decodedValue = json_decode($this->value);
                if(empty($decodedValue)){
                    throw new Exception;
                }
                $userName = $decodedValue->user->username;
            } catch (\Exception $e){
                $userUrl = "";
                $userName = '';
                $hideLoggedInfo = "display:none;";
            }
            $userUrl = "https://www.instagram.com/{$userName}/";
        } else {
            $userUrl = "";
            $userName = "";
            $hideLoggedInfo = "display:none;";
        }

        $html .= "<p class='instagram-logged' data-id='instagram-logged' style='{$hideLoggedInfo}'>Usuário logado: <a href='{$userUrl}' target='_blank'><span class='instagram-logged-user' data-id='instagram-logged-user'>@{$userName}</span></a></p>";

        $html .= "<input type='hidden' data-id='instagram-input-hidden' name='{$this->name}' value='{$this->value}'/>";

        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        $wa->registerAndUseScript('nobossinstagramlogin', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobossinstagramlogin.min.js");
        $wa->registerAndUseStyle('nobossinstagramlogin', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobossinstagramlogin.min.css");
        $wa->useStyle('fontawesome');
        
        return $html;
    }
}
