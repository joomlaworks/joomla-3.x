<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_BASE') or die;

class_exists('FinchHelper', false) || require_once __DIR__ . '/../../../../includes/helper.php';

/**
 * The Blocks editor's preview in Finch: its stylesheets, the default style's colour scheme and a post's text (.postContent).
 *
 * @var \Joomla\CMS\Blocks\BlockRenderer $r
 */
$r      = $displayData['renderer'];
$scheme = in_array($displayData['params']->get('colorScheme', 'auto'), array('light', 'dark'), true) ? $displayData['params']->get('colorScheme') : 'auto';
?>
<!DOCTYPE html>
<html lang="<?php echo $r->e($displayData['language']); ?>" dir="<?php echo $r->e($displayData['direction']); ?>" data-scheme="<?php echo $scheme; ?>">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<base href="<?php echo $r->e($displayData['base']); ?>" />
	<?php foreach (FinchHelper::stylesheets() as $url) : ?>
	<link rel="stylesheet" href="<?php echo $r->e($url); ?>" />
	<?php endforeach; ?>
	<style>.blocks-preview .container {padding-top:32px;padding-bottom:32px;}</style>
</head>
<body class="blocks-preview">
	<div class="container"><div class="postContent"><?php echo $displayData['html']; ?></div></div>
</body>
</html>
