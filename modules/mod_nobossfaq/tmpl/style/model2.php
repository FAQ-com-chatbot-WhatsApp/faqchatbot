<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss FAQ
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

$s = "";

if(!empty($questionIconBackgroundHoverColor)){
    $s .= 
    ".{$module->name} .faq-item__question:hover .faq-item__icon, .{$module->name} .faq-item.is-open .faq-item__icon{
        {$questionIconBackgroundHoverColor}
    }";
}

if(!empty($questionActiveBackgroundColor)){
    $s .=
    ".{$module->name} .faq-item.is-open .faq-item__question, .{$module->name} .faq-item__question:hover{
        {$questionActiveBackgroundColor}
    }";
}

if(!empty($searchForm)){
    $s .=
    ".{$module->name} .control-group{
        color: {$searchForm->inputs_text_color};
    }";
}

if($itemsCustomization->box_shadow){
    $s .=
    ".{$module->name} .faq-item{
        box-shadow: rgba(0, 0, 0, 0.12) 0px 1px 6px, rgba(0, 0, 0, 0.12) 0px 1px 4px;
        transition: box-shadow 0.5s ease;
    }
    .{$module->name} .faq-item.is-open{
        box-shadow: rgba(0, 0, 0, 0.19) 0px 10px 30px, rgba(0, 0, 0, 0.23) 0px 6px 10px;
    }";
}

if($moduleParams->faqs_display_search_form && (!empty($searchForm))){
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
