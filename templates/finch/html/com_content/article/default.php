<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

require_once JPATH_THEMES . '/finch/helper.php';

JLoader::register('TagsHelperRoute', JPATH_SITE . '/components/com_tags/helpers/route.php');

/** @var ContentViewArticle $this */
$item = $this->item;

// Standalone pages (an article with a menu item of its own, e.g. About): title and text only
if (FinchHelper::isPage()) : ?>
<article class="pageView">
	<h1 class="pageTitle"><?php echo FinchHelper::e($item->title); ?></h1>
	<?php echo $item->event->beforeDisplayContent; ?>
	<div class="postContent"><?php echo FinchHelper::lazyImages($item->text, true); ?></div>
	<?php echo $item->event->afterDisplayContent; ?>
</article>
<?php return; endif;

$params   = $this->params;
$author   = FinchHelper::author($item);
$image    = FinchHelper::image($item, true);
$bio      = trim((string) JFactory::getApplication()->getTemplate(true)->params->get('authorBio', ''));
$url      = JUri::getInstance()->toString(array('scheme', 'host', 'port')) . JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language));
$hue      = (int) (crc32($author) % 360);
$share    = array(
	'x'        => array('X', 'https://x.com/intent/post?url=' . rawurlencode($url) . '&text=' . rawurlencode($item->title)),
	'facebook' => array('Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url)),
	'linkedin' => array('LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($url)),
	'mail'     => array(JText::_('JGLOBAL_EMAIL'), 'mailto:?subject=' . rawurlencode($item->title) . '&body=' . rawurlencode($url)),
);

// The intro as the standfirst, when the text doesn't start with it already ("Show Intro Text" off)
$standfirst = !$params->get('show_intro', 1) ? FinchHelper::excerpt($item) : '';
?>
<article class="postView" itemscope itemtype="https://schema.org/BlogPosting">
	<header class="postHeader">
		<?php echo FinchHelper::category((object) array('category_title' => $item->category_title, 'catid' => $item->catid, 'language' => $item->language)); ?>
		<h1 class="postViewTitle" itemprop="headline"><?php echo FinchHelper::e($item->title); ?></h1>
		<?php echo $item->event->afterDisplayTitle; ?>
		<?php if ($standfirst !== '') : ?>
		<p class="standfirst" itemprop="description"><?php echo $standfirst; ?></p>
		<?php endif; ?>
		<div class="byline">
			<?php if ($author !== '') : ?>
			<span class="avatar" aria-hidden="true" style="--hue:<?php echo $hue; ?>"><?php echo FinchHelper::e(FinchHelper::initials($author)); ?></span>
			<span class="bylineName" itemprop="author"><?php echo FinchHelper::e($author); ?></span>
			<?php endif; ?>
			<span class="bylineMeta"><?php echo FinchHelper::date($item); ?> · <?php echo JText::sprintf('TPL_FINCH_READING_TIME', FinchHelper::readingTime($item)); ?></span>
		</div>
	</header>

	<?php if ($image['src'] !== '') : ?>
	<figure class="postFigure">
		<img src="<?php echo FinchHelper::e($image['src']); ?>" alt="<?php echo FinchHelper::e($image['alt']); ?>" width="1280" height="720" fetchpriority="high" itemprop="image" />
		<?php if ($image['caption'] !== '') : ?><figcaption><?php echo FinchHelper::e($image['caption']); ?></figcaption><?php endif; ?>
	</figure>
	<?php endif; ?>

	<?php echo $item->event->beforeDisplayContent; ?>
	<div class="postContent" itemprop="articleBody"><?php echo FinchHelper::lazyImages($item->text, $image['src'] === ''); ?></div>
	<?php echo $item->event->afterDisplayContent; ?>

	<footer class="postFooter">
		<?php if (!empty($item->tags->itemTags)) : ?>
		<div class="postTags">
			<span class="postTagsLabel"><?php echo JText::_('TPL_FINCH_TAGGED'); ?></span>
			<?php foreach ($item->tags->itemTags as $tag) : ?>
			<a class="tag" href="<?php echo JRoute::_(TagsHelperRoute::getTagRoute($tag->tag_id . ':' . $tag->alias)); ?>"><?php echo FinchHelper::e($tag->title); ?></a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<ul class="shareLinks" aria-label="<?php echo JText::_('TPL_FINCH_SHARE'); ?>">
			<li class="shareLabel"><?php echo JText::_('TPL_FINCH_SHARE'); ?></li>
			<?php foreach ($share as $network => $link) : ?>
			<li><a href="<?php echo FinchHelper::e($link[1]); ?>"<?php echo $network !== 'mail' ? ' class="sharePopup" target="_blank" rel="noopener"' : ''; ?> aria-label="<?php echo JText::sprintf('TPL_FINCH_SHARE_ON', $link[0]); ?>"><?php echo FinchHelper::icon($network); ?></a></li>
			<?php endforeach; ?>
			<li><button type="button" class="copyLink" data-url="<?php echo FinchHelper::e($url); ?>" data-copied="<?php echo JText::_('TPL_FINCH_LINK_COPIED'); ?>" aria-label="<?php echo JText::_('TPL_FINCH_COPY_LINK'); ?>"><?php echo FinchHelper::icon('link'); ?></button></li>
		</ul>

		<?php if ($author !== '') : ?>
		<div class="authorBox">
			<span class="avatar avatarLarge" aria-hidden="true" style="--hue:<?php echo $hue; ?>"><?php echo FinchHelper::e(FinchHelper::initials($author)); ?></span>
			<div>
				<p class="authorLabel"><?php echo JText::_('TPL_FINCH_WRITTEN_BY'); ?></p>
				<p class="authorName"><?php echo FinchHelper::e($author); ?></p>
				<?php if ($bio !== '') : ?><p class="authorBio"><?php echo FinchHelper::e($bio); ?></p><?php endif; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php // Previous and next posts, from the "Content - Page Navigation" plugin ?>
		<?php if (!empty($item->prev) || !empty($item->next)) : ?>
		<nav class="postNav" aria-label="<?php echo JText::_('TPL_FINCH_POST_NAVIGATION'); ?>">
			<?php if (!empty($item->prev)) : ?>
			<a class="postNavPrev" href="<?php echo $item->prev; ?>" rel="prev"><?php echo FinchHelper::icon('arrow-left'); ?><span><?php echo JText::_('TPL_FINCH_PREVIOUS_POST'); ?><?php echo !empty($item->prev_label) && $item->prev_label !== JText::_('JPREV') ? '<strong>' . FinchHelper::e($item->prev_label) . '</strong>' : ''; ?></span></a>
			<?php endif; ?>
			<?php if (!empty($item->next)) : ?>
			<a class="postNavNext" href="<?php echo $item->next; ?>" rel="next"><span><?php echo JText::_('TPL_FINCH_NEXT_POST'); ?><?php echo !empty($item->next_label) && $item->next_label !== JText::_('JNEXT') ? '<strong>' . FinchHelper::e($item->next_label) . '</strong>' : ''; ?></span><?php echo FinchHelper::icon('arrow-right'); ?></a>
			<?php endif; ?>
		</nav>
		<?php endif; ?>
	</footer>
</article>
