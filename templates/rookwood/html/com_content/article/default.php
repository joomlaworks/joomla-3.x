<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.rookwood
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('RookwoodHelper', false) || require_once dirname(__DIR__, 3) . '/includes/helper.php';

JLoader::register('TagsHelperRoute', JPATH_SITE . '/components/com_tags/helpers/route.php');

/** @var ContentViewArticle $this */
$item   = $this->item;
$author = RookwoodHelper::author($item);
$image  = RookwoodHelper::image($item, true);
$intro  = trim(strip_tags($item->introtext)) !== '' ? RookwoodHelper::excerpt($item) : '';

/*
 * Readers without access to the article (shown it because "Show Unauthorised Links" is on) get its intro text only, and
 * guests a link to log in and read the rest, as Joomla's own layout does: the view leaves that check to the layout
 */
$text  = $item->text;
$login = '';

if (!$item->params->get('access-view')) {
    $text = JFactory::getUser()->get('guest') ? JHtml::_('content.prepare', \Joomla\CMS\Blocks\BlockRenderer::stripMarker($item->introtext), '', 'com_content.article') : '';

    if ($text !== '' && $item->params->get('show_readmore') && (string) $item->fulltext !== '') {
        $active = JFactory::getApplication()->getMenu()->getActive();
        $link   = new JUri(JRoute::_('index.php?option=com_users&view=login&Itemid=' . ($active ? (int) $active->id : 0), false));
        $link->setVar('return', base64_encode(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language)));
        $login  = JLayoutHelper::render('joomla.content.readmore', array('item' => $item, 'params' => $item->params, 'link' => $link));
    }
}

// Resource hints (also sent as HTTP Link headers): what the text embeds, and the main image with the sizes its img() gets
RookwoodHelper::embedHints($text);
RookwoodHelper::preloadImage($image, RookwoodHelper::isPage() ? '100vw' : '(min-width: 1440px) 1400px, 100vw');

// A page (an article with a menu item of its own, e.g. Studio or Contact): its title and lead in a band, then the text
if (RookwoodHelper::isPage()) : ?>
<article class="pageView">
	<header class="pageHero<?php echo $image['src'] !== '' ? ' hasImage' : ''; ?>">
		<?php if ($image['src'] !== '') : ?>
		<div class="pageHeroImage"><?php echo RookwoodHelper::img($image, '100vw', true); ?></div>
		<?php endif; ?>
		<div class="container">
			<h1 class="pageTitle"><?php echo RookwoodHelper::e($item->title); ?></h1>
			<?php echo RookwoodHelper::editLink($item); ?>
			<?php echo $item->event->afterDisplayTitle; ?>
		</div>
	</header>
	<?php echo $item->event->beforeDisplayContent; ?>
	<div class="container prose pageText"><?php echo RookwoodHelper::lazyImages($text, $image['src'] === '') . $login; ?></div>
	<?php echo $item->event->afterDisplayContent; ?>
</article>
<?php return; endif; ?>
<article class="articleView">
	<header class="articleHeader container">
		<div class="articleLabels">
			<?php echo RookwoodHelper::category((object) array('category_title' => $item->category_title)); ?>
			<?php echo RookwoodHelper::tags($item, 3); ?>
			<?php echo RookwoodHelper::editLink($item); ?>
		</div>
		<h1 class="articleTitle"><?php echo RookwoodHelper::e($item->title); ?></h1>
		<?php echo $item->event->afterDisplayTitle; ?>
		<?php if ($intro !== '') : ?>
		<p class="articleLead"><?php echo $intro; ?></p>
		<?php endif; ?>
		<?php if ($author !== '') : ?>
		<p class="articleByline">
			<span class="avatar" aria-hidden="true" style="--hue:<?php echo (int) (crc32($author) % 360); ?>"><?php echo RookwoodHelper::e(RookwoodHelper::initials($author)); ?></span>
			<span class="itemAuthor"><?php echo RookwoodHelper::e($author); ?></span>
			<?php echo RookwoodHelper::date($item); ?>
			<span class="readingTime"><?php echo JText::sprintf('TPL_ROOKWOOD_READING_TIME', RookwoodHelper::readingTime($item)); ?></span>
		</p>
		<?php endif; ?>
	</header>

	<?php if ($image['src'] !== '') : ?>
	<figure class="articleImage">
		<?php echo RookwoodHelper::img($image, '(min-width: 1440px) 1400px, 100vw', true); ?>
		<?php if ($image['caption'] !== '') : ?><figcaption class="container"><?php echo RookwoodHelper::e($image['caption']); ?></figcaption><?php endif; ?>
	</figure>
	<?php endif; ?>

	<?php echo $item->event->beforeDisplayContent; ?>
	<div class="container prose articleText"><?php echo RookwoodHelper::lazyImages($text, $image['src'] === '') . $login; ?></div>
	<?php echo $item->event->afterDisplayContent; ?>

	<?php // Previous and next, from the "Content - Page Navigation" plugin (its own layout is empty in this template)?>
	<?php if (!empty($item->prev) || !empty($item->next)) : ?>
	<nav class="container postNav" aria-label="<?php echo JText::_('TPL_ROOKWOOD_MORE_TO_READ'); ?>">
		<?php if (!empty($item->prev)) : ?>
		<a class="postNavLink postNavPrev" href="<?php echo $item->prev; ?>" rel="prev"><span class="postNavLabel"><?php echo RookwoodHelper::icon('arrow-left') . JText::_('JPREV'); ?></span><?php if (!empty($item->prev_label) && $item->prev_label !== JText::_('JPREV')) : ?><span class="postNavTitle"><?php echo RookwoodHelper::e($item->prev_label); ?></span><?php endif; ?></a>
		<?php endif; ?>
		<?php if (!empty($item->next)) : ?>
		<a class="postNavLink postNavNext" href="<?php echo $item->next; ?>" rel="next"><span class="postNavLabel"><?php echo JText::_('JNEXT') . RookwoodHelper::icon('arrow-right'); ?></span><?php if (!empty($item->next_label) && $item->next_label !== JText::_('JNEXT')) : ?><span class="postNavTitle"><?php echo RookwoodHelper::e($item->next_label); ?></span><?php endif; ?></a>
		<?php endif; ?>
	</nav>
	<?php endif; ?>
</article>
