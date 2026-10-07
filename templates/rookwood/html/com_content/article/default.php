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
class_exists('RookwoodHelper', false) || require_once dirname(__DIR__, 3) . '/helper.php';

JLoader::register('TagsHelperRoute', JPATH_SITE . '/components/com_tags/helpers/route.php');

/** @var ContentViewArticle $this */
$item   = $this->item;
$author = RookwoodHelper::author($item);
$image  = RookwoodHelper::image($item, true);
$intro  = trim(strip_tags($item->introtext)) !== '' ? RookwoodHelper::excerpt($item) : '';

// A page (an article with a menu item of its own, e.g. Studio or Contact): its title and lead in a band, then the text
if (RookwoodHelper::isPage()) : ?>
<article class="pageView">
	<header class="pageHero<?php echo $image['src'] !== '' ? ' hasImage' : ''; ?>">
		<?php if ($image['src'] !== '') : ?>
		<div class="pageHeroImage"><?php echo RookwoodHelper::img($image, '100vw', true); ?></div>
		<?php endif; ?>
		<div class="container">
			<h1 class="pageTitle"><?php echo RookwoodHelper::e($item->title); ?></h1>
			<?php echo $item->event->afterDisplayTitle; ?>
		</div>
	</header>
	<?php echo $item->event->beforeDisplayContent; ?>
	<div class="container prose pageText"><?php echo RookwoodHelper::lazyImages($item->text, $image['src'] === ''); ?></div>
	<?php echo $item->event->afterDisplayContent; ?>
</article>
<?php return; endif; ?>
<article class="articleView" itemscope itemtype="https://schema.org/Article">
	<header class="articleHeader container">
		<div class="articleLabels">
			<?php echo RookwoodHelper::category((object) array('category_title' => $item->category_title)); ?>
			<?php echo RookwoodHelper::tags($item, 3); ?>
		</div>
		<h1 class="articleTitle" itemprop="headline"><?php echo RookwoodHelper::e($item->title); ?></h1>
		<?php echo $item->event->afterDisplayTitle; ?>
		<?php if ($intro !== '') : ?>
		<p class="articleLead" itemprop="description"><?php echo $intro; ?></p>
		<?php endif; ?>
		<?php if ($author !== '') : ?>
		<p class="articleByline">
			<span class="avatar" aria-hidden="true" style="--hue:<?php echo (int) (crc32($author) % 360); ?>"><?php echo RookwoodHelper::e(RookwoodHelper::initials($author)); ?></span>
			<span class="itemAuthor" itemprop="author"><?php echo RookwoodHelper::e($author); ?></span>
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
	<div class="container prose articleText" itemprop="articleBody"><?php echo RookwoodHelper::lazyImages($item->text, $image['src'] === ''); ?></div>
	<?php echo $item->event->afterDisplayContent; ?>

	<?php // Previous and next, from the "Content - Page Navigation" plugin (its own layout is empty in this template) ?>
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
