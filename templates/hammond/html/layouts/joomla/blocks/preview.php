<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_BASE') or die;

class_exists('HammondHelper', false) || require_once __DIR__ . '/../../../../helper.php';

/**
 * The Blocks editor's preview in Hammond: its stylesheets and an article's text (.itemFullText).
 *
 * @var \Joomla\CMS\Blocks\BlockRenderer $r
 */
$r = $displayData['renderer'];
?>
<!DOCTYPE html>
<html lang="<?php echo $r->e($displayData['language']); ?>" dir="<?php echo $r->e($displayData['direction']); ?>">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<base href="<?php echo $r->e($displayData['base']); ?>" />
	<?php foreach (HammondHelper::stylesheets() as $url) : ?>
	<link rel="stylesheet" href="<?php echo $r->e($url); ?>" />
	<?php endforeach; ?>
	<style>.blocks-preview .container {padding-top:32px;padding-bottom:32px;max-width:860px;}</style>
</head>
<body class="blocks-preview">
	<div class="container"><div class="itemFullText"><?php echo $displayData['html']; ?></div></div>
</body>
</html>
