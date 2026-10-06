<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_sampledata
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Session\Session;

JHtml::_('behavior.core');
JHtml::_('script', 'mod_sampledata/sampledata-process.js', array('version' => 'auto', 'relative' => true), array('defer' => true));

JText::script('MOD_SAMPLEDATA_CONFIRM_START');
JText::script('MOD_SAMPLEDATA_CONFIRM_REPLACE');
JText::script('MOD_SAMPLEDATA_ITEM_ALREADY_PROCESSED');
JText::script('MOD_SAMPLEDATA_INVALID_RESPONSE');
JText::script('MOD_SAMPLEDATA_REQUEST_FAILED');
JText::script('MOD_SAMPLEDATA_INSTALLED');

// The form token goes in the requests' body. Sample data plugins written for stock Joomla 3 look for it in the URL, so it's added
// there too, but only when such a plugin is enabled (a URL ends up in logs)
$others = array_filter(JPluginHelper::getPlugin('sampledata'), function ($plugin)
{
	return $plugin->name !== 'blog';
});

JFactory::getDocument()->addScriptDeclaration('
	var modSampledataUrl = "index.php?option=com_ajax&format=json&group=sampledata' . ($others ? '&' . Session::getFormToken() . '=1' : '') . '",
		modSampledataToken = "' . Session::getFormToken() . '",
		modSampledataIconProgress = "' . JUri::root(true) . '/media/jui/images/ajax-loader.gif";
');

JFactory::getDocument()->addStyleDeclaration('
	.sampledata-set { padding: 12px 0; border-bottom: 1px solid #eee; }
	.sampledata-set:last-child { border-bottom: 0; }
	.sampledata-head { display: flex; align-items: center; gap: 16px; }
	.sampledata-info { flex: 1; min-width: 0; }
	.sampledata-info small { display: block; margin-top: 2px; color: #555; }
	.sampledata-head .btn { flex: none; }
	.sampledata-head .label { margin-left: 6px; vertical-align: middle; }
	.sampledata-progress { width: 100%; margin-top: 10px; }
	.sampledata-messages { margin: 10px 0 0; }
	.sampledata-messages .alert { margin-bottom: 6px; }
');
?>
<?php
// The set installed now, which installing any set replaces
$installed = '';

foreach ($items as $item)
{
	if (!empty($item->installed))
	{
		$installed = (string) $item->title;
	}
}

// Descriptions come from the plugins' language strings (which overrides can change): a few inline tags, no attributes
$descriptionFilter = JFilterInput::getInstance(array('em', 'strong', 'b', 'i', 'br', 'code'), array(), 0, 0);
?>
<div class="sampledata-container" data-installed="<?php echo htmlspecialchars($installed, ENT_QUOTES, 'UTF-8'); ?>">
	<?php if ($items) : ?>
		<?php foreach ($items as $item) : $name = htmlspecialchars((string) $item->name, ENT_QUOTES, 'UTF-8'); ?>
		<div class="sampledata-set sampledata-<?php echo $name; ?>">
			<div class="sampledata-head">
				<div class="sampledata-info">
					<strong class="row-title">
						<span class="icon-<?php echo htmlspecialchars((string) $item->icon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></span>
						<?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?>
					</strong>
					<?php if (!empty($item->installed)) : ?>
						<span class="label label-success"><?php echo JText::_('MOD_SAMPLEDATA_INSTALLED'); ?></span>
					<?php endif; ?>
					<small><?php echo $descriptionFilter->clean((string) $item->description, 'html'); ?></small>
				</div>
				<button type="button" class="btn btn-small sampledata-apply" data-type="<?php echo $name; ?>" data-steps="<?php echo (int) $item->steps; ?>" data-title="<?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?>">
					<span class="icon-download" aria-hidden="true"></span> <?php echo JText::_(empty($item->installed) ? 'MOD_SAMPLEDATA_INSTALL' : 'MOD_SAMPLEDATA_REINSTALL'); ?>
				</button>
			</div>
			<progress class="sampledata-progress" value="0" max="1" hidden></progress>
			<ul class="sampledata-messages unstyled" hidden></ul>
		</div>
		<?php endforeach; ?>
	<?php else : ?>
		<div class="alert"><?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
	<?php endif; ?>
</div>
