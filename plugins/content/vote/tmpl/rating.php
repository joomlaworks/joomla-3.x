<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.vote
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Layout variables
 * -----------------
 * @var   string   $context  The context of the content being passed to the plugin
 * @var   object   &$row     The article object
 * @var   object   &$params  The article params
 * @var   integer  $page     The 'page' number
 * @var   array    $parts    The context segments
 * @var   string   $path     Path to this file
 */

if ($context == 'com_content.categories')
{
	return;
}

$rating = (int) $row->rating;
$rcount = (int) $row->rating_count;

// The template's own system/rating_star*.png when it has them
$starImageOn  = JHtml::_('icons.svg', 'star-fill', JText::_('PLG_VOTE_STAR_ACTIVE'), array('style' => 'color:#f5b301', 'legacy' => 'system/rating_star.png'));
$starImageOff = JHtml::_('icons.svg', 'star', JText::_('PLG_VOTE_STAR_INACTIVE'), array('style' => 'color:#999', 'legacy' => 'system/rating_star_blank.png'));

$img = '';

for ($i = 0; $i < $rating; $i++)
{
	$img .= $starImageOn;
}

for ($i = $rating; $i < 5; $i++)
{
	$img .= $starImageOff;
}

?>
<div class="content_rating">
	<?php if ($rcount) : ?>
		<p class="unseen element-invisible">
			<?php echo JText::sprintf('PLG_VOTE_USER_RATING', $rating, '5'); ?>
		</p>
	<?php endif; ?>
	<?php echo $img; ?>
</div>
