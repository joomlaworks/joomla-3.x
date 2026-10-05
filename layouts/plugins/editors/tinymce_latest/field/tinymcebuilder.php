<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Layout
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

extract($displayData);

/**
 * Layout variables
 *
 * @var  string   $name           The field's name (the configuration's form control)
 * @var  array    $value          The configuration: toolbars and setoptions per set
 * @var  array    $menus          The menus the sets can use
 * @var  array    $buttons        The buttons the sets can use
 * @var  array    $toolbarPreset  The presets
 * @var  int      $setsAmount     How many sets there are
 * @var  array    $setsNames      The sets' titles
 * @var  JForm[]  $setsForms      The sets' options
 * @var  string   $languageFile   TinyMCE's translation of the labels, if any
 */

$media = 'media/editors/tinymce_latest';

JHtml::_('behavior.core');
JHtml::_('stylesheet', $media . '/css/tinymce-builder.min.css', array('version' => 'auto'));
JHtml::_('script', $media . '/js/tinymce-builder.min.js', array('version' => 'auto'));
JHtml::_('script', $media . '/icons/default/icons.min.js', array('version' => 'auto'));

if ($languageFile)
{
	JHtml::_('script', $languageFile, array('version' => 'auto'));
}

JFactory::getDocument()->addScriptOptions('plg_editors_tinymce_latest_builder', array(
	'menus'         => $menus,
	'buttons'       => $buttons,
	'toolbarPreset' => $toolbarPreset,
	'formControl'   => $name . '[toolbars]',
));

JText::script('PLG_TINYMCE_LATEST_SET_REMOVE_ITEM');

$escape = function ($value)
{
	return htmlspecialchars(json_encode($value), ENT_QUOTES, 'UTF-8');
};

$presetClasses = array('simple' => 'btn-success', 'medium' => 'btn-info', 'advanced' => 'btn-warning');

?>
<div id="joomla-tinymce-latest-builder" class="tinymce-latest-builder">
	<p><?php echo JText::_('PLG_TINYMCE_LATEST_SET_SOURCE_PANEL_DESCRIPTION'); ?></p>

	<div class="tmb-panel tmb-source">
		<div class="tmb-bar tmb-menu" data-group="menu" data-source="true" data-value="<?php echo $escape(array_keys($menus)); ?>"></div>
		<div class="tmb-bar tmb-toolbar" data-group="toolbar" data-source="true" data-value="<?php echo $escape(array_keys($buttons)); ?>"></div>
	</div>

	<p><?php echo JText::_('PLG_TINYMCE_LATEST_SET_TARGET_PANEL_DESCRIPTION'); ?></p>

	<div class="tmb-tabs" role="tablist">
		<?php foreach ($setsNames as $num => $title) : ?>
		<button type="button" role="tab" class="tmb-tab" id="tmb-tab-<?php echo $num; ?>" aria-controls="tmb-set-<?php echo $num; ?>"
			aria-selected="<?php echo $num === 0 ? 'true' : 'false'; ?>" data-set="<?php echo $num; ?>"><?php echo $title; ?></button>
		<?php endforeach; ?>
	</div>

	<?php foreach ($setsNames as $num => $title) :
		if (empty($value['toolbars'][$num]['menu']) && empty($value['toolbars'][$num]['toolbar1']) && empty($value['toolbars'][$num]['toolbar2']))
		{
			$value['toolbars'][$num] = $toolbarPreset[$num === 0 ? 'advanced' : ($num === 1 ? 'medium' : 'simple')];
		}

		$bars = $value['toolbars'][$num];
		?>
	<div class="tmb-set" role="tabpanel" id="tmb-set-<?php echo $num; ?>" aria-labelledby="tmb-tab-<?php echo $num; ?>"<?php echo $num === 0 ? '' : ' hidden'; ?>>
		<div class="tmb-actions">
			<?php foreach (array_keys($toolbarPreset) as $preset) : ?>
			<button type="button" class="btn btn-small <?php echo isset($presetClasses[$preset]) ? $presetClasses[$preset] : 'btn-primary'; ?>"
				data-action="setPreset" data-preset="<?php echo $preset; ?>" data-set="<?php echo $num; ?>">
				<?php echo JText::_('PLG_TINYMCE_LATEST_SET_PRESET_BUTTON_' . strtoupper($preset)); ?>
			</button>
			<?php endforeach; ?>
			<button type="button" class="btn btn-small btn-danger" data-action="clearPane" data-set="<?php echo $num; ?>"><?php echo JText::_('JCLEAR'); ?></button>
		</div>

		<div class="tmb-panel">
			<div class="tmb-bar tmb-menu" data-group="menu" data-set="<?php echo $num; ?>"
				data-value="<?php echo $escape(empty($bars['menu']) ? array() : array_values((array) $bars['menu'])); ?>"></div>
			<div class="tmb-bar tmb-toolbar" data-group="toolbar1" data-set="<?php echo $num; ?>"
				data-value="<?php echo $escape(empty($bars['toolbar1']) ? array() : array_values((array) $bars['toolbar1'])); ?>"></div>
			<div class="tmb-bar tmb-toolbar" data-group="toolbar2" data-set="<?php echo $num; ?>"
				data-value="<?php echo $escape(empty($bars['toolbar2']) ? array() : array_values((array) $bars['toolbar2'])); ?>"></div>
		</div>

		<?php echo JLayoutHelper::render('plugins.editors.tinymce_latest.field.tinymcebuilder.setoptions', array('form' => $setsForms[$num])); ?>
	</div>
	<?php endforeach; ?>
</div>
