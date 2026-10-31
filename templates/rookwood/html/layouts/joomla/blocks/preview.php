<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.rookwood
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_BASE') or die;

class_exists('RookwoodHelper', false) || require_once __DIR__ . '/../../../../includes/helper.php';

/**
 * The Blocks editor's preview in Rookwood: its stylesheets, the default style's theme, and an article's text column (.prose,
 * which wide blocks break out of).
 *
 * @var \Joomla\CMS\Blocks\BlockRenderer $r
 */
$r     = $displayData['renderer'];
$theme = $displayData['params']->get('defaultTheme', 'dark') === 'light' ? 'light' : 'dark';
?>
<!DOCTYPE html>
<html lang="<?php echo $r->e($displayData['language']); ?>" dir="<?php echo $r->e($displayData['direction']); ?>" data-theme="<?php echo $theme; ?>">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<base href="<?php echo $r->e($displayData['base']); ?>" />
	<?php foreach (RookwoodHelper::stylesheets() as $url) : ?>
	<link rel="stylesheet" href="<?php echo $r->e($url); ?>" />
	<?php endforeach; ?>
</head>
<body class="blocks-preview">
	<main class="main"><div class="container prose"><?php echo $displayData['html']; ?></div></main>
</body>
</html>
