<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/*
 * none (output raw module content)
 */
function modChrome_none($module, &$params, &$attribs)
{
    echo $module->content;
}

/*
 * html5 (chosen html5 tag and font header tags)
 */
function modChrome_html5($module, &$params, &$attribs)
{
    $moduleTag      = htmlspecialchars((string) $params->get('module_tag', 'div'), ENT_QUOTES, 'UTF-8');
    $headerTag      = htmlspecialchars((string) $params->get('header_tag', 'h3'), ENT_QUOTES, 'UTF-8');
    $bootstrapSize  = (int) $params->get('bootstrap_size', 0);
    $moduleClass    = $bootstrapSize !== 0 ? ' span' . $bootstrapSize : '';

    // Temporarily store header class in variable
    $headerClass    = $params->get('header_class');
    $headerClass    = !empty($headerClass) ? ' class="' . htmlspecialchars($headerClass, ENT_COMPAT, 'UTF-8') . '"' : '';

    if (!empty($module->content)): ?>

	<<?php echo $moduleTag; ?> class="moduletable<?php echo htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_COMPAT, 'UTF-8') . $moduleClass; ?>">
		<?php if ((bool) $module->showtitle) :?>
		<<?php echo $headerTag . $headerClass . '>' . $module->title; ?></<?php echo $headerTag; ?>>
		<?php endif; ?>
		<?php echo $module->content; ?>
	</<?php echo $moduleTag; ?>>

	<?php endif;
}

/*
 * Module chrome that wraps the module in a table
 */
function modChrome_table($module, &$params, &$attribs)
{
	?>

	<table cellpadding="0" cellspacing="0" class="moduletable<?php echo htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_COMPAT, 'UTF-8'); ?>">
		<?php if ((bool) $module->showtitle): ?>
		<tr>
			<th>
				<?php echo $module->title; ?>
			</th>
		</tr>
		<?php endif; ?>
		<tr>
			<td>
				<?php echo $module->content; ?>
			</td>
		</tr>
	</table>

	<?php
}

/*
 * Module chrome that wraps the tabled module output in a <td> tag of another table
 */
function modChrome_horz($module, &$params, &$attribs)
{
	?>

	<table cellspacing="1" cellpadding="0" width="100%">
		<tr>
			<td>
				<?php modChrome_table($module, $params, $attribs); ?>
			</td>
		</tr>
	</table>

	<?php
}

/*
 * xhtml (divs and font header tags)
 * With the new advanced parameter it does the same as the html5 chrome
 */
function modChrome_xhtml($module, &$params, &$attribs)
{
    $moduleTag      = htmlspecialchars((string) $params->get('module_tag', 'div'), ENT_QUOTES, 'UTF-8');
    $headerTag      = htmlspecialchars((string) $params->get('header_tag', 'h3'), ENT_QUOTES, 'UTF-8');
    $bootstrapSize  = (int) $params->get('bootstrap_size', 0);
    $moduleClass    = $bootstrapSize !== 0 ? ' span' . $bootstrapSize : '';

    // Temporarily store header class in variable
    $headerClass    = $params->get('header_class');
    $headerClass    = $headerClass ? ' class="' . htmlspecialchars($headerClass, ENT_COMPAT, 'UTF-8') . '"' : '';

    if (!empty($module->content)): ?>

	<<?php echo $moduleTag; ?> class="moduletable<?php echo htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_COMPAT, 'UTF-8') . $moduleClass; ?>">
		<?php if ((bool) $module->showtitle): ?>
		<<?php echo $headerTag . $headerClass . '>' . $module->title; ?></<?php echo $headerTag; ?>>
		<?php endif; ?>
		<?php echo $module->content; ?>
	</<?php echo $moduleTag; ?>>

	<?php endif;
}

/*
 * Module chrome that allows for rounded corners by wrapping in nested div tags
 */
function modChrome_rounded($module, &$params, &$attribs)
{
	?>

	<div class="module<?php echo htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_COMPAT, 'UTF-8'); ?>">
		<div>
			<div>
				<div>
					<?php if ((bool) $module->showtitle): ?>
					<h3><?php echo $module->title; ?></h3>
					<?php endif; ?>
					<?php echo $module->content; ?>
				</div>
			</div>
		</div>
	</div>

	<?php
}

/*
 * Module chrome that add preview information to the module
 */
function modChrome_outline($module, &$params, &$attribs)
{
    // The preview's styles (frontend.css: .mod-preview...), as the site's templates don't load it themselves
    JHtml::_('stylesheet', 'system/frontend.min.css', array('version' => 'auto', 'relative' => true));
    ?>
	<div class="mod-preview">
		<div class="mod-preview-info">
			<div class="mod-preview-position">
				<?php echo JText::sprintf('JGLOBAL_PREVIEW_POSITION', $module->position); ?>
			</div>
			<div class="mod-preview-style">
				<?php echo JText::sprintf('JGLOBAL_PREVIEW_STYLE', $module->style); ?>
			</div>
		</div>
		<div class="mod-preview-wrapper">
			<?php echo $module->content; ?>
		</div>
	</div>
	<?php
}
