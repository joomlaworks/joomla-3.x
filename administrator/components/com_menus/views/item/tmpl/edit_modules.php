<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_menus
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

foreach ($this->levels as $key => $value) {
    $allLevels[$value->id] = $value->title;
}

// The tab's script (js/menus.js, loaded by the form): its filters and the module form in a modal, which updates the
// rows from the level names and the menu item's ID
JFactory::getDocument()->addScriptOptions('com_menus.modules', array(
    'levels'  => $allLevels,
    'menuId'  => (int) $this->item->id,
    'editUrl' => 'index.php?option=com_modules&client_id=0&task=module.edit&tmpl=component&view=module&layout=modal&id=',
));

JFactory::getDocument()->addStyleDeclaration('
ul.horizontal-buttons li {
  display: inline-block;
  padding-right: 10%;
}
');

// Set up the bootstrap modal that will be used for all module editors
echo JHtml::_(
    'bootstrap.renderModal',
    'moduleEditModal',
    array(
        'title'       => JText::_('COM_MENUS_EDIT_MODULE_SETTINGS'),
        'backdrop'    => 'static',
        'keyboard'    => false,
        'closeButton' => false,
        'bodyHeight'  => '70',
        'modalWidth'  => '80',
        'footer'      => '<button type="button" class="btn" data-dismiss="modal" data-frame-click="#closeBtn">'
                . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
                . '<button type="button" class="btn btn-primary" data-dismiss="modal" data-frame-click="#saveBtn">'
                . JText::_('JSAVE') . '</button>'
                . '<button type="button" class="btn btn-success" data-frame-click="#applyBtn">'
                . JText::_('JAPPLY') . '</button>',
    )
);

?>
<?php
// Set main fields.
$this->fields = array('toggle_modules_assigned','toggle_modules_published');

echo JLayoutHelper::render('joomla.menu.edit_modules', $this); ?>

	<table class="table table-striped">
		<thead>
		<tr>
			<th class="left">
				<?php echo JText::_('COM_MENUS_HEADING_ASSIGN_MODULE'); ?>
			</th>
			<th>
				<?php echo JText::_('COM_MENUS_HEADING_LEVELS'); ?>
			</th>
			<th>
				<?php echo JText::_('COM_MENUS_HEADING_POSITION'); ?>
			</th>
			<th>
				<?php echo JText::_('COM_MENUS_HEADING_DISPLAY'); ?>
			</th>
			<th>
				<?php echo JText::_('COM_MENUS_HEADING_PUBLISHED_ITEMS'); ?>
			</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($this->modules as $i => &$module) : ?>
			<?php if (is_null($module->menuid)) : ?>
				<?php if (!$module->except || $module->menuid < 0) : ?>
					<?php $no = 'no '; ?>
				<?php else : ?>
					<?php $no = ''; ?>
				<?php endif; ?>
			<?php else : ?>
				<?php $no = ''; ?>
			<?php endif; ?>
			<?php if ($module->published) : ?>
				<?php $status = ''; ?>
			<?php else : ?>
				<?php $status = 'unpublished '; ?>
			<?php endif; ?>
			<tr class="<?php echo $no; ?><?php echo $status; ?>row<?php echo $i % 2; ?>" id="tr-<?php echo $module->id; ?>" style="display:table-row">
				<td id="<?php echo $module->id; ?>">
					<button type="button"
						data-target="#moduleEditModal"
						class="btn btn-link module-edit-link"
						title="<?php echo JText::_('COM_MENUS_EDIT_MODULE_SETTINGS'); ?>"
						id="title-<?php echo $module->id; ?>"
						data-module-id="<?php echo $module->id; ?>">
						<?php echo $this->escape($module->title); ?></button>
				</td>
				<td id="access-<?php echo $module->id; ?>">
					<?php echo $this->escape($module->access_title); ?>
				</td>
				<td id="position-<?php echo $module->id; ?>">
					<?php echo $this->escape($module->position); ?>
				</td>
				<td id="menus-<?php echo $module->id; ?>">
					<?php if (is_null($module->menuid)) : ?>
						<?php if ($module->except) : ?>
							<span class="label label-success">
								<?php echo JText::_('JYES'); ?>
							</span>
						<?php else : ?>
							<span class="label label-important">
								<?php echo JText::_('JNO'); ?>
							</span>
						<?php endif; ?>
					<?php elseif ($module->menuid > 0) : ?>
						<span class="label label-success">
							<?php echo JText::_('JYES'); ?>
						</span>
					<?php elseif ($module->menuid < 0) : ?>
						<span class="label label-important">
							<?php echo JText::_('JNO'); ?>
						</span>
					<?php else : ?>
						<span class="label label-info">
							<?php echo JText::_('JALL'); ?>
						</span>
					<?php endif; ?>
				</td>
				<td id="status-<?php echo $module->id; ?>">
						<?php if ($module->published) : ?>
							<span class="label label-success">
								<?php echo JText::_('JYES'); ?>
							</span>
						<?php else : ?>
							<span class="label label-important">
								<?php echo JText::_('JNO'); ?>
							</span>
						<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
