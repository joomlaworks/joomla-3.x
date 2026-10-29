<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_stats_admin
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A link opens its page on the tab named after # (the tab state, as the page's own tabs keep it)
JFactory::getDocument()->addScriptDeclaration('
	document.addEventListener("click", function (event) {
		var link = event.target.closest ? event.target.closest("a.js-revert") : null;

		if (!link) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		var href = link.href;
		var path = window.location.pathname;

		localStorage.removeItem(href.replace(/&return=[a-zA-Z0-9%]+/, "").replace(/&[a-zA-Z-_]+=[0-9]+/, ""));
		localStorage.setItem(path + href.split("index.php")[1].split("#")[0], JSON.stringify(["#" + href.split("#")[1]]));
		window.location.href = href.split("#")[0];
	});
');
?>
<?php // The Control Panel shows the icon position's modules without their titles: Quick Icons has headings of its own?>
<?php if ($module->position === 'icon' && $module->showtitle) : ?>
	<h2 class="nav-header stats-module-title"><?php echo htmlspecialchars($module->title, ENT_COMPAT, 'UTF-8'); ?></h2>
<?php endif; ?>
<div class="row-striped">
	<?php foreach ($list as $item) : ?>
		<div class="row-fluid">
			<div class="span4">
				<span class="icon-<?php echo $item->icon; ?>" aria-hidden="true"></span> <?php echo $item->title; ?>
			</div>
			<div class="span8">
				<?php if (isset($item->link)) : ?>
					<a class="btn btn-info btn-small js-revert" href="<?php echo $item->link; ?>"><?php echo $item->data; ?></a>
				<?php else : ?>
					<?php echo $item->data; ?>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
