<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Layout
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var  JForm  $form  The set's options */
$form = $displayData['form'];

?>
<div class="setoptions-form-wrapper">
<?php foreach ($form->getGroup(null) as $field) : ?>
	<?php echo $field->renderField(); ?>
<?php endforeach; ?>
</div>
