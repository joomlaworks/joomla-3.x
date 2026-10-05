<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_sampledata
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Session\Session;

JHtml::_('behavior.core');
JHtml::_('script', 'mod_sampledata/sampledata-process.js', array('version' => 'auto', 'relative' => true), array('defer' => true));

JText::script('MOD_SAMPLEDATA_CONFIRM_START');
JText::script('MOD_SAMPLEDATA_ITEM_ALREADY_PROCESSED');
JText::script('MOD_SAMPLEDATA_INVALID_RESPONSE');
JText::script('MOD_SAMPLEDATA_REQUEST_FAILED');
JText::script('MOD_SAMPLEDATA_INSTALLED');

JFactory::getDocument()->addScriptDeclaration('
	var modSampledataUrl = "index.php?option=com_ajax&format=json&group=sampledata&' . Session::getFormToken() . '=1",
		modSampledataIconProgress = "' . JUri::root(true) . '/media/jui/images/ajax-loader.gif";
');

JFactory::getDocument()->addStyleDeclaration('
	.sampledata-set { padding: 12px 0; border-bottom: 1px solid #eee; }
	.sampledata-set:last-child { border-bottom: 0; }
	.sampledata-head { display: flex; align-items: center; gap: 16px; }
	.sampledata-info { flex: 1; min-width: 0; }
	.sampledata-info small { display: block; margin-top: 2px; color: #555; }
	.sampledata-head .btn { flex: none; }
	.sampledata-progress { width: 100%; margin-top: 10px; }
	.sampledata-messages { margin: 10px 0 0; }
	.sampledata-messages .alert { margin-bottom: 6px; }
');
?>
<div class="sampledata-container">
	<?php if ($items) : ?>
		<?php foreach ($items as $item) : $name = htmlspecialchars((string) $item->name, ENT_QUOTES, 'UTF-8'); ?>
		<div class="sampledata-set sampledata-<?php echo $name; ?>">
			<div class="sampledata-head">
				<div class="sampledata-info">
					<strong class="row-title">
						<span class="icon-<?php echo htmlspecialchars((string) $item->icon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></span>
						<?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?>
					</strong>
					<small><?php echo $item->description; ?></small>
				</div>
				<button type="button" class="btn btn-small sampledata-apply" data-type="<?php echo $name; ?>" data-steps="<?php echo (int) $item->steps; ?>">
					<span class="icon-download" aria-hidden="true"></span> <?php echo JText::_('MOD_SAMPLEDATA_INSTALL'); ?>
				</button>
			</div>
			<progress class="sampledata-progress" value="0" max="1" hidden></progress>
			<ul class="sampledata-messages unstyled" hidden></ul>
		</div>
		<?php endforeach; ?>
	<?php else : ?>
		<div class="alert"><?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
	<?php endif; ?>
</div>
