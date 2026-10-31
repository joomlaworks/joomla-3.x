<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2014 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

$button = $displayData;

?>
<?php if ($button->get('name')) : ?>
	<?php
        $class    = $button->get('class') ?: null;
    $class	 .= $button->get('modal') ? ' modal-button' : null;
    $href     = $button->get('link') ? ' href="' . JUri::base() . $button->get('link') . '"' : null;
    $onclick  = $button->get('onclick') ? ' onclick="' . $button->get('onclick') . '"' : '';
    $title    = $button->get('title') ?: $button->get('text');

    // Load modal popup behavior
    if ($button->get('modal')) {
        JHtml::_('behavior.modal', 'a.modal-button');
    }
    ?>
	<a class="<?php echo $class; ?>" title="<?php echo $title; ?>" <?php echo $href, $onclick; ?> rel="<?php echo $button->get('options'); ?>">
		<?php if (is_array($button->get('svg'))) : ?>
		<?php echo JHtml::_('icons.svg', $button->get('svg')['name'], '', array('path' => $button->get('svg')['path'], 'size' => 14, 'class' => 'icon-svg', 'style' => 'vertical-align:-0.125em')); ?> <?php echo $button->get('text'); ?>
		<?php else : ?>
		<span class="icon-<?php echo $button->get('name'); ?>" aria-hidden="true"></span> <?php echo $button->get('text'); ?>
		<?php endif; ?>
	</a>
<?php endif;
