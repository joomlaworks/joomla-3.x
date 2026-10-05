<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_config
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Cancel Controller for global configuration components
 *
 * @since  3.2
 */
class ConfigControllerComponentCancel extends ConfigControllerCanceladmin
{
	/**
	 * Method to cancel global configuration component.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function execute()
	{
		$this->context = 'com_config.config.global';

		$this->component = $this->input->get('component');

		$this->redirect = 'index.php?option=' . $this->component;

		// Back to the page the Options button was used on, as Save & Close does (the parent checks the token, clears the form data and refuses external URLs)
		$returnUri = $this->input->post->get('return', null, 'base64');

		if (!empty($returnUri))
		{
			$this->redirect = base64_decode($returnUri);
		}

		parent::execute();
	}
}
