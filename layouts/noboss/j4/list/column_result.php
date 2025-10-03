<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/*
 * Esse arquivo pode ser sobreescrito dentro da pasta tmpl da view do componente utilizando o mesmo nome (column_result.php)
 * 
 * Nota: a sobreescrita eh util e necessaria somente quando voce precisar fazer tratamentos mais complexos dos dados a serem exibidos como resultado das colunas especificas do componente que voce ira detalhar mais abaixo no array $this->customColumns
 */

// TODO: atencao: ao copiar o conteudo desse arquivo para sobreescrita, exclua as duas linhas abaixo
$item = $displayData['item'];
$column = $displayData['column'];

// Existem valores definidos para replace do resultado
if(!empty($column->replaceArraySearch) && !empty($column->replaceArrayNew)){
    $item->{$column->alias} = str_ireplace($column->replaceArraySearch, $column->replaceArrayNew, $item->{$column->alias});
}

// Existe valor definido
if(!empty($item->{$column->alias})){
    // Existe '%VALUE%' no retorno que deve ser trocado pelo valor do item
    if (strpos($column->returnHtml, '%VALUE%') !== true) {
        echo str_replace('%VALUE%', $item->{$column->alias}, $column->returnHtml);
    }
    // Exibe direto o valor do item
    else{
        echo $item->{$column->alias};
    }
}
// Nao existe valor definido: seta valor default
else{
    echo $column->returnHtmlWhenNull;
}