<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_housekeeping
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Session\Session;

/** @var boolean $canClean */
/** @var boolean $isAdmin */

JHtml::_('stylesheet', 'mod_housekeeping/housekeeping.css', array('version' => 'auto', 'relative' => true));
JHtml::_('script', 'mod_housekeeping/housekeeping.js', array('version' => 'auto', 'relative' => true), array('defer' => true));

$url = JUri::base(true) . '/index.php?option=com_ajax&module=housekeeping&method=clean&format=json';
$e   = function ($text)
{
	return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
};

// Icons of the module's own (no icon font)
$icon = function ($name)
{
	$paths = array(
		'clean'   => '<path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 10v7M14 10v7"/>',
		'all'     => '<path d="M14 4l6 6M4 20l7.5-7.5M12 6l6 6-6.5 6.5a3 3 0 0 1-4 0l-2-2a3 3 0 0 1 0-4z"/>',
		'checkin' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 7.5-2"/>',
		'more'    => '<path d="M6 15l6-6 6 6"/>',
	);

	return '<svg class="hkIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" '
		. 'stroke-linecap="round" stroke-linejoin="round">' . $paths[$name] . '</svg>';
};
?>
<div class="btn-group housekeeping" data-housekeeping-url="<?php echo $e($url); ?>" data-token="<?php echo $e(Session::getFormToken()); ?>"
	data-working="<?php echo $e(JText::_('MOD_HOUSEKEEPING_WORKING')); ?>" data-failed="<?php echo $e(JText::_('MOD_HOUSEKEEPING_REQUEST_FAILED')); ?>"
	data-close="<?php echo $e(JText::_('MOD_HOUSEKEEPING_CLOSE')); ?>">
	<?php if ($canClean) : ?>
	<button type="button" class="hkButton" data-housekeeping-action="cache"><?php echo $icon('clean'); ?><span><?php echo JText::_('MOD_HOUSEKEEPING_CLEAN_CACHE'); ?></span></button>
	<?php endif; ?>
	<?php if ($isAdmin) : ?>
	<button type="button" class="hkButton hkToggle" aria-haspopup="true" aria-expanded="false" aria-controls="housekeepingMenu"
		title="<?php echo $e(JText::_('MOD_HOUSEKEEPING_MORE')); ?>" aria-label="<?php echo $e(JText::_('MOD_HOUSEKEEPING_MORE')); ?>"><?php echo $icon('more'); ?></button>
	<ul class="hkMenu" id="housekeepingMenu" role="menu" hidden>
		<li role="none"><button type="button" role="menuitem" data-housekeeping-action="all"
			data-confirm="<?php echo $e(JText::_('MOD_HOUSEKEEPING_CONFIRM_ALL')); ?>"><?php echo $icon('all'); ?><span><?php echo JText::_('MOD_HOUSEKEEPING_CLEAN_ALL'); ?></span></button></li>
		<li role="none"><button type="button" role="menuitem" data-housekeeping-action="checkin"
			data-confirm="<?php echo $e(JText::_('MOD_HOUSEKEEPING_CONFIRM_CHECKIN')); ?>"><?php echo $icon('checkin'); ?><span><?php echo JText::_('MOD_HOUSEKEEPING_CHECKIN'); ?></span></button></li>
	</ul>
	<?php endif; ?>
</div>
