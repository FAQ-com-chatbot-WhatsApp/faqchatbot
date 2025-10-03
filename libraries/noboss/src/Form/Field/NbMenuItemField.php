<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\MenuitemField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbMenuItemField extends MenuitemField{

    protected function getInput(){
        // Busca no banco o total de items de menu cadastrados 
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        $query->select('count(*)')
            ->from('#__menu');
        $db->setQuery($query);
        $total = $db->loadResult();

        // Armazena maximo de itens aceitaveis
        $limiteItens = 700;

        // Se tiver mais do que xx itens, impede de usar o campo para não ter problema de performance
        if($total > $limiteItens){
            return Text::sprintf("LIB_NOBOSS_FIELD_NOBOSSITEMMENU_MESSAGE_LIMIT", $limiteItens);
        }
        else{
            return parent::getInput();
        }
    }
}