<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * The hidden buttons of an item's form opened in a modal, which the modal's own buttons click (data-frame-click="#saveBtn"):
 * Save and Save & Close also hand the title to the opening page's function, for fields made before Joomla 3.7 (frontend.js).
 *
 * task        The controller's prefix (article: article.apply, article.save, article.cancel)
 * form        The form's id
 * titleField  The title field's id
 * function    The opening page's function, in the address (function=), else this default; empty for none
 */
JHtml::_('behavior.core');

$data     = (array) $displayData;
$function = '';

if (!empty($data['function'])) {
    $function = (string) JFactory::getApplication()->input->getCmd('function', $data['function']);
}

$title = $function === '' ? '' : ' data-modal-function="' . htmlspecialchars($function, ENT_QUOTES, 'UTF-8') . '"'
    . ' data-modal-form="' . htmlspecialchars($data['form'], ENT_QUOTES, 'UTF-8') . '"'
    . ' data-modal-title-field="' . htmlspecialchars($data['titleField'], ENT_QUOTES, 'UTF-8') . '"';
$task  = htmlspecialchars($data['task'], ENT_QUOTES, 'UTF-8');
?>
<button id="applyBtn" type="button" class="hidden" data-modal-submit="<?php echo $task; ?>.apply"<?php echo $title; ?>></button>
<button id="saveBtn" type="button" class="hidden" data-modal-submit="<?php echo $task; ?>.save"<?php echo $title; ?>></button>
<button id="closeBtn" type="button" class="hidden" data-modal-submit="<?php echo $task; ?>.cancel"></button>
