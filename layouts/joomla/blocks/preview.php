<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_BASE') or die;

/**
 * The Blocks editor's preview: a page with the rendered blocks, styled with the site template's css/template.css and
 * css/custom.css. A template whose content needs more (other stylesheets, a wrapper with its classes, a theme attribute)
 * overrides it as html/layouts/joomla/blocks/preview.php.
 *
 * $displayData: html (the rendered blocks), template, params (the default style's options), base (the site's address),
 * language, direction, renderer.
 */

/** @var \Joomla\CMS\Blocks\BlockRenderer $r */
$r        = $displayData['renderer'];
$template = $displayData['template'];
$links    = '';

foreach (array('template.css', 'custom.css') as $file) {
    $path = JPATH_ROOT . '/templates/' . $template . '/css/' . $file;

    if ($template !== '' && is_file($path)) {
        $links .= '<link rel="stylesheet" href="templates/' . $r->e($template) . '/css/' . $file . '?t=' . filemtime($path) . '" />';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $r->e($displayData['language']); ?>" dir="<?php echo $r->e($displayData['direction']); ?>">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<base href="<?php echo $r->e($displayData['base']); ?>" />
<?php echo $links; ?>
<style>body {padding:24px;}</style>
<?php echo isset($displayData['head']) ? $displayData['head'] : ''; ?>
</head>
<body class="blocks-preview">
<div class="item-page"><?php echo $displayData['html']; ?></div>
</body>
</html>
