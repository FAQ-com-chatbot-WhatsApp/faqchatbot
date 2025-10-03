<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss FAQ
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2018 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

$s = "";

if(!empty($questionIconBackgroundHoverColor)){
    $s .= 
    ".{$module->name} .faq-item__question:hover .faq-item__icon, .{$module->name} .faq-item__question.is-open .faq-item__icon{
        {$questionIconBackgroundHoverColor}
    }";
}

if(!empty($questionActiveBackgroundColor)){
    $s .=
    ".{$module->name} .faq-item__question.is-open, .{$module->name} .faq-item__question:hover{
        {$questionActiveBackgroundColor}
    }";
}

if($moduleParams->faqs_display_search_form){
    $s .=
    ".{$module->name} .control-group{
        color: {$searchForm->inputs_text_color};
     }
     .{$module->name} .faq-form__search:hover{   
         {$searchForm->buttonStyleHover}
     }";
}

$s .= 
".{$module->name} .faq-answer *{
    font-family: inherit;
}";

$s .= 
"@media screen and (max-width: 767px){";
    $s .="
        .{$module->name}{
            {$sectionStyleMobile}
        }
        .{$module->name} .{$module->name}__title{
            {$titleStyleMobile}
        }
        .{$module->name} .{$module->name}__subtitle{
            {$subtitleStyleMobile}
        }
        .{$module->name} .faq-question{
            {$questionSizeMobile}
        }
        .{$module->name} .faq-item{
            {$itemsStyleMobile}
        }
    ";

$s .= "}";

$assetsObject->addStyleWithPrefix($s); 

?>
