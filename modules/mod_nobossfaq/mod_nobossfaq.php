<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss FAQ
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');

$app = JFactory::getApplication();
// pega o template vinculado a pagina
$tmpl = $app->getTemplate(true);
// pega os parametros do template
$tmplParams = $tmpl->params;
// pega a cor primaria setada nos parametros do template
$tmplPrimaryColor = $tmplParams->get('primary_color');
// pega a cor secundaria setada nos parametros do template
$tmplSecondaryColor = $tmplParams->get('secondary_color');

// Importa os arquivos da library noboss
jimport('noboss.util.loadextensionassets');
jimport('noboss.util.fonts');

// Parametro com nome do modulo nao esta definido
if (!isset($module->name)){
    $module->name = str_replace('mod_', '', $module->module);
}
$moduleName = $module->name;
$extensionName = "mod_" . $module->name;

// Carrega helper do modulo
JLoader::register('ModNobossfaqHelper', __DIR__ . '/helper.php');

// Verifica se no boss library esta instalada: se ainda nao estiver, tenta instalar e se ainda nao der retorna false
if(!ModNobossfaqHelper::checkLibraryInstallation()){
    return;
}

// Pega o documento
$doc = JFactory::getDocument();

// Pega os parametros do modulo conforme seu grupo.
$moduleParams = ModNobossfaqHelper::getModuleParamsByFaqsGroup($module->id);

// variavel que armazena o tema escolhido 
$theme = json_decode($moduleParams->faqs_display_theme)->theme;

// armazema estilo da seção, elemento mais externo do html 
$sectionStyle = "";
// armazema estilo da seção para o mobile
$sectionStyleMobile = "";

// string com o nome da modal de itens concatenado com o modelo selecionado 
$itemsCustomizationXml = 'faqs_display_items_customization_' . $theme;

// variavel que armazena o objeto com os parametros da modal de itens do tema selecionado  
$itemsCustomization = json_decode($moduleParams->$itemsCustomizationXml);

// string com o nome da modal da area externa concatenado com o modelo selecionado 
$externalAreaXml = 'faqs_display_external_area_' . $theme;

// variavel que armazena o objeto com os parametros da modal de area externa do tema selecionado  
$externalArea = json_decode($moduleParams->$externalAreaXml);

// cor do loader 
// pega a cor definida no modulo ou a cor primaria do template
$externalArea->loader_color = !empty($externalArea->loader_color) ? $externalArea->loader_color : $tmplPrimaryColor;

// Ajustes manuais da área externa caso exista valor
if ($externalArea->external_area_display_mode){
    if(!empty($externalArea->external_area_width)){
        $sectionStyle .= 'margin-left: auto !important; margin-right: auto !important; width:' . $externalArea->external_area_width . '%;';
    }
    if(!empty($externalArea->external_area_width_mobile)){
        $sectionStyleMobile .= 'width:' . $externalArea->external_area_width_mobile . '% !important; ';
    }
}
// verifica qual o modo de exibição
// 1: é manual, então exibe container
// 0: é full width 
if($externalArea->content_display_mode){
    $itemColumns = !empty($externalArea->content_columns) ? "nb-lg-{$externalArea->content_columns} nb-md-{$externalArea->content_columns} nb-sm-12 nb-xs-12" : "";
}

// armazena o estilo do espaçamento interno na variavel que eh colocada no elemento mais externo
$sectionStyle .= ' padding: ' . implode(' ', $externalArea->external_area_inner_space) . '; ';
// armazena o estilo do espaçamento interno para o mobile na variavel que eh colocada no elemento mais externo
$sectionStyleMobile .= 'padding: ' . implode(' ', $externalArea->external_area_inner_space_mobile) . ' !important; ';
// armazena o estilo do espaçamento externo na variavel que eh colocada no elemento mais externo
$sectionStyle .= ' margin: ' . implode(' ', $externalArea->external_area_outer_space) . '; ';
// armazena o estilo do espaçamento externo na variavel que eh colocada no elemento mais externo
$sectionStyleMobile .= 'margin: ' . implode(' ', $externalArea->external_area_outer_space_mobile) . ' !important;';

// Armazena o tipo de fundo da area externa (cor ou gradiente)
$faqBackgroundType = $externalArea->external_area_background_type;

// Se o tipo de fundo for "cor"
if($faqBackgroundType == "background-color" && !empty($externalArea->external_area_background_color)){
    // pega a cor definida no modulo ou a cor primaria do template
    $externalArea->external_area_background_color = !empty($externalArea->external_area_background_color) ? $externalArea->external_area_background_color : $tmplPrimaryColor;
    // Armazena essa cor ao estilo de fundo da secao
    $sectionStyle .= "background-color: {$externalArea->external_area_background_color};";
}elseif($faqBackgroundType == "background-gradient"){
    // Se o tipo for gradiente, acresenta o CSS de gradiente ao estilo da seção
    $gradient1 = $externalArea->external_area_gradient_filter_1;
    $gradient2 = $externalArea->external_area_gradient_filter_2;
    $sectionStyle .= "background: {$gradient2}; ";
    $sectionStyle .= "background: -webkit-linear-gradient(to right, {$gradient1}, {$gradient2}); "; 
    $sectionStyle .= "background: linear-gradient(to right, {$gradient1}, {$gradient2}); ";
}

// Modelo 1 e 2: inativa campo de exibir categorias que eh usado apenas no modelo 3
if($theme != 'model3'){
    $moduleParams->faqs_display_categories = 0;
}
// Modelo 3: inativa campo de exibir formulario que eh apenas dos modelos 1 e 2
else{
    $moduleParams->faqs_display_search_form = 0;
}

// Parametros do formulario de pesquisa
if($theme != 'model3' && $moduleParams->faqs_display_search_form){
    // string com o nome da modal de barra de pesquisa concatenado com o modelo selecionado 
    $searchFormXml = 'faqs_display_search_form_' . $theme;
    // objeto com os parâmetros definidos na modal de barra de pesquisa do tema selecionado  
    $searchForm = json_decode($moduleParams->$searchFormXml);

    // Estilo dos campos do formulario
    $searchForm->inputStyle = "";
    $searchForm->inputStyle .= NoBossUtilFonts::importNobossfontlist($searchForm->inputs_text_font);
    $searchForm->inputStyle .= " text-align: " . $searchForm->inputs_text_alignment . "; text-align-last: " . $searchForm->inputs_text_alignment . ";";
    $searchForm->inputStyle .= " text-transform: " . $searchForm->inputs_text_transform . ";";
    // pega a cor definida no modulo ou a cor primaria do template
    $searchForm->inputs_text_color = !empty($searchForm->inputs_text_color) ? $searchForm->inputs_text_color : $tmplPrimaryColor;
    $searchForm->inputStyle .= " color: " . $searchForm->inputs_text_color . ";";
    // pega a cor definida no modulo ou a cor primaria do template
    $searchForm->background_color = !empty($searchForm->background_color) ? $searchForm->background_color : $tmplPrimaryColor;
    $searchForm->inputStyle .= " background-color: " . $searchForm->background_color . ";";
    $inputSize = $searchForm->inputs_text_size;
    if(!empty($inputSize)){
        $inputSizeEm = $inputSize/16;
        $searchForm->inputStyle .= " font-size: {$inputSize}px; font-size: {$inputSizeEm}em;";
    }
    
    // Variavel que armazenara o estilo da borda dos campos
    $searchForm->borderStyle = '';

    // Verifica se deve exibir borda
    if($searchForm->show_inputs_border){
        // Verifica se deve exibir borda apenas embaixo do campo
        if($searchForm->inputs_one_line_border){
            $searchForm->borderStyle .= "border-radius: 0px; border: 0; border-bottom: " . $searchForm->border_size . "px solid " . $searchForm->border_color . ";";
        }else{
            $searchForm->borderStyle .= "padding: .5em 1em; border: " . $searchForm->border_size . "px solid " . $searchForm->border_color . ";";
            $searchForm->borderStyle .= " \n border-radius: " . $searchForm->border_radius . "px;";
        }
    }else{
        $searchForm->borderStyle = 'border: 0px;';
    }

    // Variavel que armazenara o estilo do botão 
    $searchForm->buttonStyle = "";
    // Variavel que armazenara o estilo do botao no hover
    $searchForm->buttonStyleHover = "";
    // pega a cor definida no modulo ou a cor primaria do template
    $searchForm->buttons_text_color = !empty($searchForm->buttons_text_color) ? $searchForm->buttons_text_color : $tmplPrimaryColor;
    // pega a cor definida no modulo ou a cor primaria do template
    $searchForm->buttons_background_color = !empty($searchForm->buttons_background_color) ? $searchForm->buttons_background_color : $tmplPrimaryColor;
    // pega a cor definida no modulo ou a cor secundaria do template
    $searchForm->buttons_hover_text_color = !empty($searchForm->buttons_hover_text_color) ? $searchForm->buttons_hover_text_color : $tmplSecondaryColor;
    // pega a cor definida no modulo ou a cor secundaria do template
    $searchForm->buttons_background_hover_color = !empty($searchForm->buttons_background_hover_color) ? $searchForm->buttons_background_hover_color : $tmplSecondaryColor;
    // pega a cor definida no modulo ou a cor primaria do template
    $searchForm->buttons_border_color = !empty($searchForm->buttons_border_color) ? $searchForm->buttons_border_color : $tmplPrimaryColor;
    // pega a cor definida no modulo ou a cor secundaria do template
    $searchForm->buttons_hover_border_color = !empty($searchForm->buttons_hover_border_color) ? $searchForm->buttons_hover_border_color : $tmplSecondaryColor;
    
    // Verifica modelo de botao entre as opcoes abaixo e monta o estilo a ser colocado
    switch ($searchForm->buttons_style) {
        // Botão quadrado
        case 'squared-button':
            $searchForm->buttonStyle = "color: {$searchForm->buttons_text_color}; background-color: {$searchForm->buttons_background_color};";
            $searchForm->buttonStyleHover = "background-color: {$searchForm->buttons_background_hover_color} !important;";
            break;
        // Botão arredondado
        case 'rounded-button':
            $searchForm->buttonStyle = "color: {$searchForm->buttons_text_color}; background-color: {$searchForm->buttons_background_color}; border-radius: {$searchForm->buttons_border_radius_size}px;";
            $searchForm->buttonStyleHover = "background-color: {$searchForm->buttons_background_hover_color} !important;";
            break;
        // Botão transparente quadrado
        case 'ghost-squared-button':
            $searchForm->buttonStyle = "background-color: transparent; border: 2px solid;  color: {$searchForm->buttons_text_color}; border-color: {$searchForm->buttons_border_color};";
            $searchForm->buttonStyleHover = "color: {$searchForm->buttons_hover_text_color} !important; border-color: {$searchForm->buttons_hover_border_color} !important; background-color: {$searchForm->buttons_background_hover_color} !important;";
            break;
        // Botão transparente arredondado
        case 'ghost-rounded-button':
            $searchForm->buttonStyle = "background-color: transparent; border: 2px solid; color: {$searchForm->buttons_text_color}; border-color: {$searchForm->buttons_border_color}; border-radius: {$searchForm->buttons_border_radius_size}px;";
            $searchForm->buttonStyleHover = "color: {$searchForm->buttons_hover_text_color} !important; border-color: {$searchForm->buttons_hover_border_color} !important; background-color: {$searchForm->buttons_background_hover_color} !important;";
            break;
        default:
            $searchForm->buttonStyle = "color: {$searchForm->buttons_text_color};";
            break;
    }
    // Parametros de texto do botao
    $searchForm->buttonStyle .= NoBossUtilFonts::importNobossfontlist($searchForm->buttons_font);
    $searchForm->buttonStyle .= " text-transform: {$searchForm->buttons_text_transform};";
    
    
    $buttonsSize = $searchForm->buttons_text_size;
    if(!empty($buttonsSize)){
        $buttonsSizeEm = $buttonsSize/16;
        $searchForm->buttonStyle .= " font-size: {$buttonsSize}px; font-size: {$buttonsSizeEm}em;";
    }
    // posicionamento do botao 
    if($searchForm->buttons_position == "left" || $searchForm->buttons_position == "right"){
        $searchForm->buttonStyle .= " float: {$searchForm->buttons_position}; ";
    }else{
        $searchForm->buttonStyle .= " margin: auto; display: table; ";
    }

    $searchForm->buttonStyle .= isset($searchForm->buttons_space) ? ' padding: '.implode(' ', $searchForm->buttons_space).';' : ' padding: 0.5em 2em;';
}

// Parametro da listagem de categorias
elseif($theme == 'model3' && $moduleParams->faqs_display_categories){
    // string com o nome da modal de personalizacao da exibicao de categorias concatenado com o modelo selecionado 
    $categoriesXml = 'faqs_display_categories_' . $theme;
    // objeto com os parametros definidos na modal de exibicao das categorias do tema selecionado  
    $categoriesParams = json_decode($moduleParams->$categoriesXml);
    // Posicao da lista de categorias e tamanhos da area externa
    $position = $categoriesParams->categories_position;
    $marginTop = 'margin-top: ' . $itemsCustomization->items_outer_space[0] . ';';
    $manualAreaSize = $categoriesParams->show_manual_size && $position != 'top' ? 'width: ' . $categoriesParams->categories_area_size . '%;' : '';
    // Ordem de exibicao das categorias
    $displayOrderCategories = (!empty($categoriesParams->display_order)) ? $categoriesParams->display_order : '';

    // Variavel que armazenara o estilo das categorias
    $categoriesStyle = "";
    $categoriesStyle .= 'padding: ' . implode(' ', $categoriesParams->items_inner_space) . '; ';
    $categoriesStyle .= 'margin: ' . implode(' ', $categoriesParams->items_outer_space) . '; ';
    // pega a cor definida no modulo ou a cor primaria do template
    $categoriesParams->background_color = !empty($categoriesParams->background_color) ? $categoriesParams->background_color : $tmplPrimaryColor;
    $categoriesStyle .= $categoriesParams->show_background ? 'background-color: ' . $categoriesParams->background_color . '; ' : '';
    $categoriesStyle .= 'border-radius: ' . $categoriesParams->items_border_radius_size . 'px; ';
    $categoriesSpaceMobile = 'padding: ' . implode(' ', $categoriesParams->items_inner_space_mobile) . '!important; ';
    $categoriesSpaceMobile .= 'margin: ' . implode(' ', $categoriesParams->items_outer_space_mobile) . '!important; ';
    // pega a cor definida no modulo ou a cor secundaria do template
    $categoriesParams->background_hover = !empty($categoriesParams->background_hover) ? $categoriesParams->background_hover : $tmplSecondaryColor;
    $backgroundHover = $categoriesParams->show_background ? 'background-color: ' . $categoriesParams->background_hover . '!important; ' : '';
    
    // Verifica se deve ser exibida borda apenas em baixo da categoria
    if($categoriesParams->underline_border){
        // Se a posição for diferente de 'em cima' 
        // if($position != 'top'){
        //     $categoriesUnderlineBorder = $categoriesParams->show_border ? 'border-bottom: ' . ($categoriesParams->border_size + 1) . 'px solid ' . 'transparent;  ' : ''; //margin-bottom: -1px !important;
        // }else{
        //     $categoriesUnderlineBorder = $categoriesParams->show_border ? 'border-bottom: ' . $categoriesParams->border_size . 'px solid ' . 'transparent; ' : '';
        // }
        // $categoriesUnderlineBorder = $categoriesParams->show_border ? "box-shadow: 0px {$categoriesParams->border_size}px transparent" : '';
        // $categoriesLastItemUnderlineBorder = $categoriesParams->show_border ? 'border-bottom: ' . $categoriesParams->border_size . 'px solid ' . 'transparent; ' : '';
        $categoriesUnderlineActiveBorder = $categoriesParams->show_border ? "box-shadow: 0px -{$categoriesParams->border_size}px {$categoriesParams->border_color} inset !important;" : '';
    }else{
        $categoriesStyle .= $categoriesParams->show_border ? 'border: ' . $categoriesParams->border_size . 'px solid ' . $categoriesParams->border_color . ';' : '';
    }
    
    // Parametrizacoes de texto das categorias
    $textStyle = "";
    $textStyle .= $textFontFamily = NoBossUtilFonts::importNobossfontlist($categoriesParams->text_font);
    $textStyle .= " text-align: " . $categoriesParams->text_alignment . ";";
    $textStyle .= " text-transform: " . $categoriesParams->text_transform . ";";
    // pega a cor definida no modulo ou a cor primaria do template
    $categoriesParams->text_color = !empty($categoriesParams->text_color) ? $categoriesParams->text_color : $tmplPrimaryColor;
    $textStyle .= " color: " . $categoriesParams->text_color . ";";
    // pega a cor definida no modulo ou a cor secundaria do template
    $categoriesParams->text_color_hover = !empty( $categoriesParams->text_color_hover) ?  $categoriesParams->text_color_hover : $tmplSecondaryColor;
    $textHoverColor = " color: " . $categoriesParams->text_color_hover . "!important;";
    $textSize = $categoriesParams->text_size;
    if(!empty($textSize)){
        $textSizeEm = $textSize/16;
        $textStyle .= " font-size: {$textSize}px; font-size: {$textSizeEm}em;";
    }
}

// Parametros para título da area externa
$showTitle = $moduleParams->faqs_display_show_title;
$title = rtrim($moduleParams->faqs_display_title);
$titleTagHtml   = $externalArea->title_tag_html;
$titleStyle = "";
$titleStyle .= NoBossUtilFonts::importNobossfontlist($externalArea->title_font);
$titleStyle .= " text-align: " . $externalArea->title_alignment . ";";
$titleStyle .= " text-transform: " . $externalArea->title_transform . ";";
// pega a cor definida no modulo ou a cor primaria do template
$externalArea->title_color = !empty($externalArea->title_color) ? $externalArea->title_color : $tmplPrimaryColor;
$titleStyle .= " color: " . $externalArea->title_color . ";";
$titleSize = $externalArea->title_size;
if(!empty($titleSize)){
    $titleSizeEm = $titleSize/16;
    $titleStyle .= " font-size: {$titleSize}px; font-size: {$titleSizeEm}em;";
}
$titleStyleMobile = "";
if(isset($externalArea->title_size_mobile)){
    $titleSizeMobile = $externalArea->title_size_mobile;
    $titleSizeMobileEm = $titleSizeMobile/16;
    $titleStyleMobile = " font-size: {$titleSizeMobile}px; font-size: {$titleSizeMobileEm}em !important;";
}
if(isset($externalArea->title_space)){
    $titleStyle .= " padding: ".implode(" ", $externalArea->title_space).";";
}

// Parametros para texto de apoio da area externa
$showSubtitle = $moduleParams->faqs_display_show_subtitle;
$subtitle = rtrim($moduleParams->faqs_display_subtitle);
$subtitleTagHtml = $externalArea->subtitle_tag_html;
$subtitleStyle = "";
$subtitleStyle .= NoBossUtilFonts::importNobossfontlist($externalArea->subtitle_font);
$subtitleStyle .= " text-align: " . $externalArea->subtitle_alignment . ";";
$subtitleStyle .= " text-transform: " . $externalArea->subtitle_transform . ";";
// pega a cor definida no modulo ou a cor primaria do template
$externalArea->subtitle_color = !empty($externalArea->subtitle_color) ? $externalArea->subtitle_color : $tmplPrimaryColor;
$subtitleStyle .= " color: " . $externalArea->subtitle_color . ";";
$subtitleSize = $externalArea->subtitle_size;
if(!empty($subtitleSize)){
    $subtitleSizeEm = $subtitleSize/16;
    $subtitleStyle .= " font-size: {$subtitleSize}px; font-size: {$subtitleSizeEm}em;";
}
$subtitleStyleMobile = "";
if(isset($externalArea->subtitle_size_mobile)){
    $subtitleSizeMobile = $externalArea->subtitle_size_mobile;
    $subtitleSizeMobileEm = $subtitleSizeMobile/16;
    $subtitleStyleMobile = " font-size: {$subtitleSizeMobile}px; font-size: {$subtitleSizeMobileEm}em !important;";
}
if(isset($externalArea->subtitle_space)){
    $subtitleStyle .= " padding: ".implode(" ", $externalArea->subtitle_space).";";
}
// Armazena se deve separar as perguntas das respostas
$separateItems = $itemsCustomization->separate_questions_answers;

// Parametros gerais dos itens da FAQ
$itemsStyle = "";
$itemsStyle .= 'border-radius: ' . $itemsCustomization->border_radius . 'px; overflow: hidden;';
// pega a cor definida no modulo ou a cor primaria do template
$itemsCustomization->background_color = !empty($itemsCustomization->background_color) ? $itemsCustomization->background_color : $tmplPrimaryColor;
$itemsStyle .= !$separateItems ? 'background-color: ' . $itemsCustomization->background_color . ';' : '';
$itemsStyle .= !$separateItems ? 'position: relative;' : '';

$itemsStyle .= 'margin: ' . implode(' ', $itemsCustomization->items_outer_space) . '; ';
// armazena o estilo do espaçamento externo na variavel que eh colocada no elemento mais externo
$itemsStyleMobile = 'margin: ' . implode(' ', $itemsCustomization->items_outer_space_mobile) . ' !important;';
// pega a cor definida no modulo ou a cor primaria do template
$itemsCustomization->border_color = !empty($itemsCustomization->border_color) ? $itemsCustomization->border_color : $tmplPrimaryColor;
$itemsStyle .= $itemsCustomization->show_border ? 'border: ' . $itemsCustomization->border_size . 'px solid ' . $itemsCustomization->border_color . ';' : '';
$itemBorder = $itemsCustomization->show_border ? 'border-top: ' . $itemsCustomization->border_size . 'px solid ' . $itemsCustomization->border_color . ';' : '';

// Parametros para exibicao das perguntas
$questionTagHtml = $itemsCustomization->questions_text_tag_html;
$questionStyle = "";
$questionStyle .= NoBossUtilFonts::importNobossfontlist($itemsCustomization->questions_text_font);
$questionStyle .= " text-align: " . $itemsCustomization->questions_text_alignment . ";";
$questionStyle .= " text-transform: " . $itemsCustomization->questions_text_transform . ";";
// pega a cor definida no modulo ou a cor primaria do template
$itemsCustomization->questions_text_color = !empty($itemsCustomization->questions_text_color) ? $itemsCustomization->questions_text_color : $tmplPrimaryColor;
$questionStyle .= " color: " . $itemsCustomization->questions_text_color . ";";
$questionStyle .= $separateItems ? 'position: relative; ' : '';
if(isset($itemsCustomization->questions_space)){
    $questionStyle .= " padding: ".implode(" ", $itemsCustomization->questions_space).";";
}
$questionSize = $itemsCustomization->questions_text_size;
if(!empty($questionSize)){
    $questionSizeEm = $questionSize/16;
    $questionSize = " font-size: {$questionSize}px; font-size: {$questionSizeEm}em; margin: 0px;";
}
if(isset($itemsCustomization->questions_text_size_mobile)){
    $questionSizeMobile = $itemsCustomization->questions_text_size_mobile;
    $questionSizeMobileEm = $questionSizeMobile/16;
    $questionSizeMobile = " font-size: {$questionSizeMobile}px; font-size: {$questionSizeMobileEm}em !important;";
}
// Acresenta estilo de quando a pergunta eh separada da resposta
// pega a cor definida no modulo ou a cor primaria do template
$itemsCustomization->questions_background_color = !empty($itemsCustomization->questions_background_color) ? $itemsCustomization->questions_background_color : $tmplPrimaryColor;
$questionStyle .= $separateItems ? 'background-color: ' . $itemsCustomization->questions_background_color . ';' : '';
// Acrescenta estilo a questao aberta
// pega a cor definida no modulo ou a cor secundaria do template
$itemsCustomization->questions_active_background_color = !empty($itemsCustomization->questions_active_background_color) ? $itemsCustomization->questions_active_background_color : $tmplSecondaryColor;
$questionActiveBackgroundColor = $separateItems ? 'background-color: ' . $itemsCustomization->questions_active_background_color . '!important;' : '';

// Parametros de icones
// armazena o array com as classes de icones
$questionIconClasses = explode(';', $itemsCustomization->questions_icon); 
// variavel que armazena a classe do icone 
$questionIcon = $questionIconClasses[0];
// variavel que armazena a classe do icone ativo
$questionIconActive = $questionIconClasses[1];
// variavel que armazena a posicao do icone 
$questionIconAlignment = $itemsCustomization->questions_icons_alignment;

// Variavel que armazena o estilo do ícone
$questionIconStyle = '';
// Monta o estilo do icone
$questionIconStyle .= $questionIconAlignment == 'right' ? 'margin-left: 0.75em; ' : 'margin-right: 0.75em;';
$questionIconSize = $itemsCustomization->questions_icons_size;
if(!empty($questionIconSize)){
    $questionIconSizeEm = $questionIconSize/16;
    $questionIconStyle .= "font-size: {$questionIconSize}px; font-size: {$questionIconSizeEm}em;";
}
// Verifica se foi definida cor ao icone
if (!empty($itemsCustomization->questions_icons_color)){
    // pega a cor definida no modulo ou a cor primaria do template
    $itemsCustomization->questions_icons_color = !empty($itemsCustomization->questions_icons_color) ? $itemsCustomization->questions_icons_color : $tmplPrimaryColor;
    $questionIconStyle .= ' color:' . $itemsCustomization->questions_icons_color . ';';
}

// Parametrizacoes especificas para o modelo 1 e 3
if($theme == 'model1' || $theme == 'model3'){
    // Variavel que armazena se o icone deve exibir borda 
    $questionsIconHasBorder = $itemsCustomization->questions_icons_background_type == 'border' || $itemsCustomization->questions_icons_background_type == 'both' ? 1 : 0;
    // Variavel que armazena se o icone deve exibir fundo colorido (background)
    $questionsIconHasBackground = $itemsCustomization->questions_icons_background_type == 'background' || $itemsCustomization->questions_icons_background_type == 'both' ? 1 : 0;
    // Acrescenta posicao do icone na variavel de estilo
    $questionIconStyle .= $questionIconAlignment == 'right' ? '-ms-flex-order: 2; order: 2; ' : '';
    // Acrescenta espacamento dos icones na variavel de estilo
    if(($questionsIconHasBorder && !$itemsCustomization->questions_show_icons_border_radius) || $questionsIconHasBackground){
        $questionIconStyle .= "padding: " . implode(' ', $itemsCustomization->questions_icons_spacing) . "; ";
    }
    // Acrescenta arredondamento da borda dos icones na variavel de estilo
    if ($itemsCustomization->questions_show_icons_border_radius && $itemsCustomization->questions_icons_background_type != 'none'){
        $questionIconStyle .= 'border-radius:' . $itemsCustomization->questions_icons_border_radius . 'px; padding: 0.4em;';
    }
    // Define estilo de espacamento aos itens da FAQ
    $itemsSpace = $separateItems ? ' box-shadow: -1px 0px 5px 0px rgba(65, 65, 65, 0.2);' : '';

}elseif($theme == 'model2'){
    // Parametrizacoes especificas para o modelo 2  
    $questionsIconHasBackground = 1;
    $questionsIconHasBorder = $itemsCustomization->questions_show_icons_border;
    // pega a cor definida no modulo ou a cor primaria do template
    $itemsCustomization->background_color = !empty($itemsCustomization->background_color) ? $itemsCustomization->background_color : $tmplPrimaryColor;
    $itemsStyle .= 'background-color: ' . $itemsCustomization->background_color . ';';

    // Configuração de espaçamento e alinhamento dos icones
    $rightSpace =  (!empty($itemsCustomization->questions_icons_spacing)) ? (int)$itemsCustomization->questions_icons_spacing[0] * 2 : '';
    $itemsStyle .= $questionsIconHasBackground && !$separateItems ? 'padding-' . $questionIconAlignment . ': calc(' . $rightSpace . 'px + ' . $questionIconSizeEm . 'em); ' : '';
    $questionStyle .= $separateItems ? 'box-shadow: -1px 0px 5px 0px rgba(65, 65, 65, 0.2);' : '';
    $questionStyle .= $questionsIconHasBackground && $separateItems ? 'padding-' . $questionIconAlignment . ': calc(' . $rightSpace . 'px + ' . $questionIconSizeEm . 'em); ' : '';
    $questionIconStyle .= $separateItems ? "padding: " . implode(' ', $itemsCustomization->questions_icons_spacing) . "; " : "padding: 0.75em " . implode(' ', $itemsCustomization->questions_icons_spacing) . "; ";
    if ($questionsIconHasBackground){
        $questionIconStyle .= $questionIconAlignment . ': 0; ';
    }
}

// Define a cor de hover no icone da pergunta, caso esteja habilitada
// pega a cor definida no modulo ou a cor secundaria do template
$itemsCustomization->questions_icons_background_hover = !empty($itemsCustomization->questions_icons_background_hover) ? $itemsCustomization->questions_icons_background_hover : $tmplSecondaryColor;
$questionIconBackgroundHoverColor = $questionsIconHasBackground ? 'background-color: ' . $itemsCustomization->questions_icons_background_hover . '!important; ' : '';

// Define a borda dos icones da pergunta, caso esteja habilitada
if ($questionsIconHasBorder){
    $questionIconStyle .= 'border: 1px solid ' . $itemsCustomization->questions_icons_border_color . '; ';
}
// Define a cor de fundo do icone, caso esteja habilitada
if ($questionsIconHasBackground){
    // pega a cor definida no modulo ou a cor primaria do template
    $itemsCustomization->questions_icons_background_color = !empty($itemsCustomization->questions_icons_background_color) ? $itemsCustomization->questions_icons_background_color : $tmplPrimaryColor;
    $questionIconStyle .= 'background-color:' . $itemsCustomization->questions_icons_background_color . '; ';
}

// Paramtro de trazer 'todos resultados exibidos no carregamento' nao esta definido ou esta como '0' e existe filtro de categoria sendo exibidos: coloca como padrao para todos resultados serem exibidos
if((empty($itemsCustomization->show_question_upload)) && ($moduleParams->faqs_display_categories)){
    $itemsCustomization->show_question_upload = 1;
}
// Nao ha nenhum filtro sendo exibido: forca que todos resultados sejam exibidos
else if ((!$moduleParams->faqs_display_search_form) && (!$moduleParams->faqs_display_categories)){
    $itemsCustomization->show_question_upload = 1;
}
// Paramtro de trazer 'todos resultados exibidos no carregamento' apenas nao esta definido: seta ele como '0'
else if(!isset($itemsCustomization->show_question_upload)){
    $itemsCustomization->show_question_upload = 0;
}

// Parametros para exibicao das respostas
$answerStyle = "";
$answerStyle .= NoBossUtilFonts::importNobossfontlist($itemsCustomization->answers_text_font);
// pega a cor definida no modulo ou a cor primaria do template
$itemsCustomization->answers_background_color = !empty($itemsCustomization->answers_background_color) ? $itemsCustomization->answers_background_color : $tmplPrimaryColor;
$answerStyle .= $separateItems ? $itemBorder . "background-color: " . $itemsCustomization->answers_background_color . ";" : '';
if(isset($itemsCustomization->answers_space)){
    $answerStyle .= " padding: " . implode(" ", $itemsCustomization->answers_space) . ";";
}

//FIXME: 
// echo $answerStyle; exit;

// Parametro que define se deve ser mantido apenas a pergunta clicada aberta
if(!isset($itemsCustomization->open_questions)){
    $itemsCustomization->open_questions = 1;
}

// Ordenacao dos resultados nao definida OU ta definida como categoria (opcao que nao existe mais)
if(!isset($itemsCustomization->ordering_questions) || ($itemsCustomization->ordering_questions == 'category')){
    $itemsCustomization->ordering_questions = 'alphabetical';
}

// Parametro que define se os resultados devem ser agrupados conforme categoria
if(!isset($itemsCustomization->group_by_category)){
    $itemsCustomization->group_by_category = '1';
}

// Verifica se o grupo do modulo esta publicado.
if($moduleParams->groupState){
    
    // Define prefixo a ser utilizado na insercao de codigos inline para CSS e JS
    $prefixCodeJsAndCss = "[module-id={$module->name}_{$module->id}]";

    // Obtem informacoes de codigos JS e CSS a serem inseridos
    $loadJs         = $moduleParams->faqs_display_load_js;
    $overwritingJs  = rtrim($moduleParams->faqs_display_overwriting_js);
    $loadCss        = $moduleParams->faqs_display_load_css;
    $overwritingCss = rtrim($moduleParams->faqs_display_overwriting_css);

    // Instancia objeto passando o nome da extensao com prefixo (ex: 'mod_nobossbanners')
    $assetsObject = new NoBossUtilLoadExtensionAssets($extensionName, $prefixCodeJsAndCss);

    // Adiciona arquivos e codigos JS (se definido para exibir)
    $assetsObject->loadJs($loadJs, array('code' => $overwritingJs));

    // Adiciona arquivos e codigos CSS (se definido para carregar)
    $assetsObject->loadCss($loadCss, array('code' => $overwritingCss));
    // Adiciona a família de fonte para exibição dos ícones
    $assetsObject->loadFamilyIcons('font-awesome');
    
    // Se ordem de exibicao de categorias nao estiver definida, seta por padrao a manual
    if(empty($displayOrderCategories)){
        $displayOrderCategories = 'manual';
    }

    $categories = array();
    $questions = array();

    // Parametros sobre origem do conteudo
    $optionsContent = array('display_registered' => $moduleParams->content_display_registered, 'display_articles' => $moduleParams->content_display_articles, 'categories_articles' => $moduleParams->content_categories_articles);

    // Obtem categorias
    $categories = ModNobossfaqHelper::getCategories($moduleParams->id_faqs_group, $displayOrderCategories, $optionsContent);

    // Obtem perguntas
    $questions = ModNobossfaqHelper::getQuestions($moduleParams->id_faqs_group, $itemsCustomization->ordering_questions, $itemsCustomization->group_by_category, $displayOrderCategories, $optionsContent);

    // echo '<pre>';
    // var_dump($questions);
    // exit;

    // Renderiza o estilo e o template do modelo escolhido.
    require JModuleHelper::getLayoutPath($extensionName, 'style/' . $theme);    
    require JModuleHelper::getLayoutPath($extensionName, 'theme/'. $params->get('layout', $theme));
}
