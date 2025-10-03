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
    ".{$module->name} .faq-item__question:hover .faq-item__icon, .{$module->name} .faq-item__question.is-open .faq-item__icon{
        {$questionIconBackgroundHoverColor}
    }";
}

$s .=
".{$module->name} .faq-item__question.is-open, .{$module->name} .faq-item__question:hover{
    {$questionActiveBackgroundColor}
}";

if($moduleParams->faqs_display_categories && $categoriesParams->underline_border){
    if(!empty($categoriesUnderlineBorder)){

        $s .= 
        ".{$module->name} .category__link{
            {$categoriesUnderlineBorder} 
        }";
    }
}

if(!empty($categoriesLastItemUnderlineBorder) && !empty($position) && $position !='top'){
    $s .=
    ".{$module->name} .category__item:last-child .category__link{
        {$categoriesLastItemUnderlineBorder}
    }";
}

$s .= 
".{$module->name} .faq-answer *{
    font-family: inherit;
}";

$s .= 
".{$module->name} .category__link:hover{";
    if(!empty($textHoverColor)){
        $s .= $textHoverColor;
    }

    if(!empty($backgroundHover)){
        $s .= $backgroundHover;
    }

    if(!empty($categoriesUnderlineActiveBorder)){
        $s .= $categoriesUnderlineActiveBorder;
    }
$s .= "}";

$s .= 
".{$module->name} .category__link.is-active{";
    if(!empty($textHoverColor)){
        $s .= $textHoverColor;
    }

    if(!empty($backgroundHover)){
        $s .= $backgroundHover;
    }
    if(!empty($categoriesUnderlineActiveBorder)){
        $s .= $categoriesUnderlineActiveBorder;
    }
$s .= "}";

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
    
    if(!empty($categoriesSpaceMobile)){
        $s .="
            .{$module->name} .category__link{
                {$categoriesSpaceMobile}
            }
        ";
    }

$s .= "}";

$assetsObject->addStyleWithPrefix($s); 

?>
