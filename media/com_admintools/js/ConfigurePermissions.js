/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

window.addEventListener("DOMContentLoaded", function ()
{
    document.querySelectorAll("button.admintoolsSaveApplyPermissions")
            .forEach(function (elButton)
            {
                elButton.addEventListener('click', function () {
                    document.forms.adminForm.task.value = "saveapplyperms";

                    return true;
                })
            });
});