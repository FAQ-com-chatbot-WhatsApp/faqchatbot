<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss FAQ
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2018 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

?>

<div class="noboss-faq-header">
    <?php
        // Exibe titulo da area externa
        if ($showTitle && !empty($title)) {
            echo "<{$titleTagHtml} class='{$module->name}__title' style='{$titleStyle}'>{$title}</{$titleTagHtml}>";
        }
    ?>
    <?php
        // Exibe texto de apoio da area externa
        if ($showSubtitle && !empty($subtitle)) {
            echo "<{$subtitleTagHtml} class='{$module->name}__subtitle' style='{$subtitleStyle}'>{$subtitle}</{$subtitleTagHtml}>";
        }
    ?>
</div>
