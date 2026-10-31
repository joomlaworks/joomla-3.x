<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('HammondHelper', false) || require_once dirname(__DIR__, 3) . '/includes/helper.php';

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

/*
 * Readers without access to the article (shown it because "Show Unauthorised Links" is on) get its intro text only, and
 * guests a link to log in and read the rest, as Joomla's own layout does: the view leaves that check to the layout
 */
$text  = $item->text;
$login = '';

if (!$item->params->get('access-view')) {
    $text = JFactory::getUser()->get('guest') ? JHtml::_('content.prepare', $item->introtext, '', 'com_content.article') : '';

    if ($text !== '' && $item->params->get('show_readmore') && (string) $item->fulltext !== '') {
        $active = JFactory::getApplication()->getMenu()->getActive();
        $link   = new JUri(JRoute::_('index.php?option=com_users&view=login&Itemid=' . ($active ? (int) $active->id : 0), false));
        $link->setVar('return', base64_encode(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language)));
        $login  = JLayoutHelper::render('joomla.content.readmore', array('item' => $item, 'params' => $item->params, 'link' => $link));
    }
}

// Resource hints (also sent as HTTP Link headers): what the text embeds, and the main image (info pages show none)
HammondHelper::embedHints($text);

if (!HammondHelper::isPage()) {
    HammondHelper::preloadImage($image);
}

// Info pages (the company menu): title and text only
if (HammondHelper::isPage()) : ?>
<article class="pageView">
	<h1 class="pageTitle"><?php echo HammondHelper::e($item->title); ?></h1>
	<div class="itemFullText"><?php echo HammondHelper::lazyImages($text, true) . $login; ?></div>
</article>
<?php return; endif; ?>
<article class="articleView">
	<header class="articleHeader">
		<?php echo HammondHelper::category((object) array('category_title' => $item->category_title, 'category_alias' => $item->category_alias, 'catid' => $item->catid, 'language' => $item->language)); ?>
		<h1 class="articleTitle"><?php echo HammondHelper::e($item->title); ?></h1>
		<?php echo $item->event->afterDisplayTitle; ?>
		<?php if (trim(strip_tags($item->introtext)) !== '') : ?>
		<p class="articleStandfirst"><?php echo HammondHelper::excerpt($item); ?></p>
		<?php endif; ?>
		<div class="articleByline">
			<?php if ($author !== '') : ?>
			<span class="avatar" aria-hidden="true" style="--hue:<?php echo (int) (crc32($author) % 360); ?>"><?php echo HammondHelper::e(HammondHelper::initials($author)); ?></span>
			<span class="bylineText">
				<span class="itemAuthor"><?php echo HammondHelper::e($author); ?></span>
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
		<img src="<?php echo HammondHelper::e($image['src']); ?>"<?php echo HammondHelper::srcsetAttributes($image['src'], HammondHelper::SIZES_MAIN); ?> alt="<?php echo HammondHelper::e($image['alt']); ?>" width="1280" height="720" fetchpriority="high" />
		<?php if ($image['caption'] !== '') : ?><figcaption><?php echo HammondHelper::e($image['caption']); ?></figcaption><?php endif; ?>
	</figure>
	<?php endif; ?>

	<?php echo $item->event->beforeDisplayContent; ?>
	<div class="itemFullText"><?php echo HammondHelper::lazyImages($text, $image['src'] === '') . $login; ?></div>
	<?php echo $item->event->afterDisplayContent; ?>

	<?php if (!empty($item->tags->itemTags) && JComponentHelper::isEnabled('com_tags')) : ?>
	<footer class="articleTags">
		<span class="tagsLabel"><?php echo JText::_('TPL_HAMMOND_TAGGED'); ?></span>
		<?php foreach ($item->tags->itemTags as $tag) : ?>
		<a class="tag" href="<?php echo JRoute::_(TagsHelperRoute::getTagRoute($tag->tag_id . ':' . $tag->alias)); ?>">#<?php echo HammondHelper::e($tag->title); ?></a>
		<?php endforeach; ?>
	</footer>
	<?php endif; ?>
</article>
