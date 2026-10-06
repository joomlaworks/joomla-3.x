<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

require_once JPATH_THEMES . '/hammond/helper.php';

JLoader::register('TagsHelperRoute', JPATH_SITE . '/components/com_tags/helpers/route.php');

/** @var ContentViewArticle $this */
$item   = $this->item;
$params = $this->params;
$author = HammondHelper::author($item);
$image  = HammondHelper::image($item, true);
$url    = JUri::getInstance()->toString(array('scheme', 'host', 'port')) . JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language));
$share  = array(
	'x'        => array('X', 'https://x.com/intent/post?url=' . rawurlencode($url) . '&text=' . rawurlencode($item->title)),
	'facebook' => array('Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url)),
	'linkedin' => array('LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($url)),
	'mail'     => array(JText::_('JGLOBAL_EMAIL'), 'mailto:?subject=' . rawurlencode($item->title) . '&body=' . rawurlencode($url)),
);

// Info pages (the company menu): title and text only
if (HammondHelper::isPage()) : ?>
<article class="pageView">
	<h1 class="pageTitle"><?php echo HammondHelper::e($item->title); ?></h1>
	<div class="itemFullText"><?php echo HammondHelper::lazyImages($item->text, true); ?></div>
</article>
<?php return; endif; ?>
<article class="articleView" itemscope itemtype="https://schema.org/NewsArticle">
	<header class="articleHeader">
		<?php echo HammondHelper::category((object) array('category_title' => $item->category_title, 'category_alias' => $item->category_alias, 'catid' => $item->catid, 'language' => $item->language)); ?>
		<h1 class="articleTitle" itemprop="headline"><?php echo HammondHelper::e($item->title); ?></h1>
		<?php echo $item->event->afterDisplayTitle; ?>
		<?php if (trim(strip_tags($item->introtext)) !== '') : ?>
		<p class="articleStandfirst" itemprop="description"><?php echo HammondHelper::excerpt($item); ?></p>
		<?php endif; ?>
		<div class="articleByline">
			<?php if ($author !== '') : ?>
			<span class="avatar" aria-hidden="true" style="--hue:<?php echo (int) (crc32($author) % 360); ?>"><?php echo HammondHelper::e(HammondHelper::initials($author)); ?></span>
			<span class="bylineText">
				<span class="itemAuthor" itemprop="author"><?php echo HammondHelper::e($author); ?></span>
				<span class="bylineMeta"><?php echo HammondHelper::date($item); ?> · <?php echo JText::sprintf('TPL_HAMMOND_READING_TIME', HammondHelper::readingTime($item)); ?></span>
			</span>
			<?php endif; ?>
			<ul class="shareLinks" aria-label="<?php echo JText::_('TPL_HAMMOND_SHARE'); ?>">
				<?php foreach ($share as $network => $link) : ?>
				<li><a href="<?php echo HammondHelper::e($link[1]); ?>"<?php echo $network !== 'mail' ? ' class="sharePopup" target="_blank" rel="noopener"' : ''; ?> aria-label="<?php echo JText::sprintf('TPL_HAMMOND_SHARE_ON', $link[0]); ?>"><?php echo HammondHelper::icon($network); ?></a></li>
				<?php endforeach; ?>
				<li><button type="button" class="copyLink" data-url="<?php echo HammondHelper::e($url); ?>" data-copied="<?php echo JText::_('TPL_HAMMOND_LINK_COPIED'); ?>" aria-label="<?php echo JText::_('TPL_HAMMOND_COPY_LINK'); ?>"><?php echo HammondHelper::icon('link'); ?></button></li>
			</ul>
		</div>
	</header>

	<?php if ($image['src'] !== '') : ?>
	<figure class="articleImage">
		<img src="<?php echo HammondHelper::e($image['src']); ?>" alt="<?php echo HammondHelper::e($image['alt']); ?>" width="1280" height="720" fetchpriority="high" itemprop="image" />
		<?php if ($image['caption'] !== '') : ?><figcaption><?php echo HammondHelper::e($image['caption']); ?></figcaption><?php endif; ?>
	</figure>
	<?php endif; ?>

	<?php echo $item->event->beforeDisplayContent; ?>
	<div class="itemFullText" itemprop="articleBody"><?php echo HammondHelper::lazyImages($item->text, $image['src'] === ''); ?></div>
	<?php echo $item->event->afterDisplayContent; ?>

	<?php if (!empty($item->tags->itemTags)) : ?>
	<footer class="articleTags">
		<span class="tagsLabel"><?php echo JText::_('TPL_HAMMOND_TAGGED'); ?></span>
		<?php foreach ($item->tags->itemTags as $tag) : ?>
		<a class="tag" href="<?php echo JRoute::_(TagsHelperRoute::getTagRoute($tag->tag_id . ':' . $tag->alias)); ?>">#<?php echo HammondHelper::e($tag->title); ?></a>
		<?php endforeach; ?>
	</footer>
	<?php endif; ?>
</article>
