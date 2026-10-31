<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_menus
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.multiselect');

$uri       = JUri::getInstance();
$return    = base64_encode($uri);
$user      = JFactory::getUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$modMenuId = (int) $this->get('ModMenuId');

// The page's script (js/menus.js): it reloads when a module modal closes, and the XML export's task is reset
JFactory::getDocument()->addScriptOptions('com_menus.menus', array('list' => true));
JHtml::_('script', 'administrator/components/com_menus/js/menus.min.js', array('version' => 'auto', 'position' => 'footer'), array('defer' => true));
?>
<form action="<?php echo JRoute::_('index.php?option=com_menus&view=menus'); ?>" method="post" name="adminForm" id="adminForm">
<?php if (!empty($this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif; ?>
		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this, 'options' => array('filterButton' => false))); ?>
		<div class="clearfix"> </div>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table class="table table-striped" id="menuList">
				<thead>
					<tr>
						<th width="1%">
							<?php echo JHtml::_('grid.checkall'); ?>
						</th>
						<th>
							<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
						</th>
						<th width="10%" class="nowrap center">
							<span class="icon-publish" aria-hidden="true"></span>
							<span class="hidden-phone"><?php echo JText::_('COM_MENUS_HEADING_PUBLISHED_ITEMS'); ?></span>
						</th>
						<th width="10%" class="nowrap center">
							<span class="icon-unpublish" aria-hidden="true"></span>
							<span class="hidden-phone"><?php echo JText::_('COM_MENUS_HEADING_UNPUBLISHED_ITEMS'); ?></span>
						</th>
						<th width="10%" class="nowrap center">
							<span class="icon-trash" aria-hidden="true"></span>
							<span class="hidden-phone"><?php echo JText::_('COM_MENUS_HEADING_TRASHED_ITEMS'); ?></span>
						</th>
						<th width="20%" class="nowrap center">
							<span class="icon-cube" aria-hidden="true"></span>
							<span class="hidden-phone"><?php echo JText::_('COM_MENUS_HEADING_LINKED_MODULES'); ?></span>
						</th>
						<th width="1%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="15">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<tbody>
				<?php foreach ($this->items as $i => $item) :
				    $canEdit        = $user->authorise('core.edit', 'com_menus.menu.' . (int) $item->id);
				    $canManageItems = $user->authorise('core.manage', 'com_menus.menu.' . (int) $item->id);
				    ?>
					<tr class="row<?php echo $i % 2; ?>">
						<td class="center">
							<?php echo JHtml::_('grid.id', $i, $item->id); ?>
						</td>
						<td>
							<?php if ($canManageItems) : ?>
							<a href="<?php echo JRoute::_('index.php?option=com_menus&view=items&menutype=' . $item->menutype); ?>">
								<?php echo $this->escape($item->title); ?></a>
							<?php else : ?>
								<?php echo $this->escape($item->title); ?>
							<?php endif; ?>
							<div class="small">
								<?php echo JText::_('COM_MENUS_MENU_MENUTYPE_LABEL'); ?>:
								<?php if ($canEdit) : ?>
									<a href="<?php echo JRoute::_('index.php?option=com_menus&task=menu.edit&id=' . $item->id); ?>" title="<?php echo $this->escape($item->description); ?>">
									<?php echo $this->escape($item->menutype); ?></a>
								<?php else : ?>
									<?php echo $this->escape($item->menutype); ?>
								<?php endif; ?>
							</div>
						</td>
						<td class="center btns">
							<?php if ($canManageItems) : ?>
								<a class="badge<?php if ($item->count_published > 0) {
								    echo ' badge-success';
								} ?>" href="<?php echo JRoute::_('index.php?option=com_menus&view=items&menutype=' . $item->menutype . '&filter[published]=1'); ?>">
									<?php echo $item->count_published; ?></a>
							<?php else : ?>
								<span class="badge<?php if ($item->count_published > 0) {
								    echo ' badge-success';
								} ?>">
									<?php echo $item->count_published; ?></span>
							<?php endif; ?>
						</td>
						<td class="center btns">
							<?php if ($canManageItems) : ?>
								<a class="badge<?php if ($item->count_unpublished > 0) {
								    echo ' badge-important';
								} ?>" href="<?php echo JRoute::_('index.php?option=com_menus&view=items&menutype=' . $item->menutype . '&filter[published]=0'); ?>">
									<?php echo $item->count_unpublished; ?></a>
							<?php else : ?>
								<span class="badge<?php if ($item->count_unpublished > 0) {
								    echo ' badge-important';
								} ?>">
									<?php echo $item->count_unpublished; ?></span>
							<?php endif; ?>
						</td>
						<td class="center btns">
							<?php if ($canManageItems) : ?>
								<a class="badge<?php if ($item->count_trashed > 0) {
								    echo ' badge-inverse';
								} ?>" href="<?php echo JRoute::_('index.php?option=com_menus&view=items&menutype=' . $item->menutype . '&filter[published]=-2'); ?>">
									<?php echo $item->count_trashed; ?></a>
							<?php else : ?>
								<span class="badge<?php if ($item->count_trashed > 0) {
								    echo ' badge-inverse';
								} ?>">
									<?php echo $item->count_trashed; ?></span>
							<?php endif; ?>
						</td>
						<td class="center">
							<?php if (isset($this->modules[$item->menutype])) : ?>
								<div class="btn-group">
									<button type="button" class="btn btn-small dropdown-toggle" data-toggle="dropdown">
										<?php echo JText::_('COM_MENUS_MODULES'); ?>
										<span class="caret"></span>
									</button>
									<ul class="dropdown-menu dropdown-reverse">
										<?php foreach ($this->modules[$item->menutype] as &$module) : ?>
											<li>
												<?php if ($user->authorise('core.edit', 'com_modules.module.' . (int) $module->id)) : ?>
													<?php $link = JRoute::_('index.php?option=com_modules&task=module.edit&id=' . $module->id . '&return=' . $return . '&tmpl=component&layout=modal'); ?>
													<a role="button" href="#moduleEdit<?php echo $module->id; ?>Modal" class="button" data-toggle="modal" title="<?php echo JText::_('COM_MENUS_EDIT_MODULE_SETTINGS'); ?>">
														<?php echo JText::sprintf('COM_MENUS_MODULE_ACCESS_POSITION', $this->escape($module->title), $this->escape($module->access_title), $this->escape($module->position)); ?></a>
												<?php else : ?>
													<a href="#" class="disabled" disabled="disabled">
														<?php echo JText::sprintf('COM_MENUS_MODULE_ACCESS_POSITION', $this->escape($module->title), $this->escape($module->access_title), $this->escape($module->position)); ?></a>
												<?php endif; ?>
											</li>
										<?php endforeach; ?>
									</ul>
								 </div>
								<?php foreach ($this->modules[$item->menutype] as &$module) : ?>
									<?php if ($user->authorise('core.edit', 'com_modules.module.' . (int) $module->id)) : ?>
										<?php $link = JRoute::_('index.php?option=com_modules&task=module.edit&id=' . $module->id . '&return=' . $return . '&tmpl=component&layout=modal'); ?>
										<?php echo JHtml::_(
										    'bootstrap.renderModal',
										    'moduleEdit' . $module->id . 'Modal',
										    array(
										        'title'       => JText::_('COM_MENUS_EDIT_MODULE_SETTINGS'),
										        'backdrop'    => 'static',
										        'keyboard'    => false,
										        'closeButton' => false,
										        'url'         => $link,
										        'height'      => '400px',
										        'width'       => '800px',
										        'bodyHeight'  => '70',
										        'modalWidth'  => '80',
										        'footer'      => '<button type="button" class="btn" data-dismiss="modal" data-frame-click="#closeBtn">'
										                . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
										                . '<button type="button" class="btn btn-primary" data-frame-click="#saveBtn">'
										                . JText::_('JSAVE') . '</button>'
										                . '<button type="button" class="btn btn-success" data-frame-click="#applyBtn">'
										                . JText::_('JAPPLY') . '</button>',
										    )
										); ?>
									<?php endif; ?>
								<?php endforeach; ?>
							<?php elseif ($modMenuId) : ?>
								<?php $link = JRoute::_('index.php?option=com_modules&task=module.add&eid=' . $modMenuId . '&params[menutype]=' . $item->menutype . '&tmpl=component&layout=modal'); ?>
								<button type="button" class="btn btn-small btn-primary" data-toggle="modal" data-target="#moduleAddModal"><?php echo JText::_('COM_MENUS_ADD_MENU_MODULE'); ?></button>
								<?php echo JHtml::_(
								    'bootstrap.renderModal',
								    'moduleAddModal',
								    array(
								        'title'       => JText::_('COM_MENUS_ADD_MENU_MODULE'),
								        'backdrop'    => 'static',
								        'keyboard'    => false,
								        'closeButton' => false,
								        'url'         => $link,
								        'height'      => '400px',
								        'width'       => '800px',
								        'bodyHeight'  => '70',
								        'modalWidth'  => '80',
								        'footer'      => '<button type="button" class="btn" data-dismiss="modal" data-frame-click="#closeBtn">'
								                . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
								                . '<button type="button" class="btn btn-primary" data-frame-click="#saveBtn">'
								                . JText::_('JSAVE') . '</button>'
								                . '<button type="button" class="btn btn-success" data-frame-click="#applyBtn">'
								                . JText::_('JAPPLY') . '</button>',
								    )
								); ?>
							<?php endif; ?>
						</td>
						<td class="hidden-phone">
							<?php echo $item->id; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
