<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Sampledata.Blog
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;

/**
 * Sample Data plugin: the News and Blog sample data sets (the installer's, set.php), installed from the Sample Data module. The
 * element stays "blog", the plugin's name before it offered more than one set.
 *
 * @since  3.8.0
 */
class PlgSampledataBlog extends JPlugin
{
	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  3.8.0
	 */
	protected $db;

	/**
	 * Application object
	 *
	 * @var    JApplicationCms
	 * @since  3.8.0
	 */
	protected $app;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  3.8.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * The sets, in the order the module shows them.
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	const SETS = array('news', 'blog', 'studio');

	/**
	 * Get an overview of the sets.
	 *
	 * @return  stdClass[]|void
	 *
	 * @since   3.8.0
	 */
	public function onSampledataGetOverview()
	{
		// Only for Super Users: a set replaces the one installed before and changes the site's template
		if (!Factory::getUser()->authorise('core.admin'))
		{
			return;
		}

		$sets = array();

		foreach (self::SETS as $name)
		{
			$sets[] = $this->getSet($name)->getOverview();
		}

		return $sets;
	}

	/**
	 * Step 1 of a set.
	 *
	 * @return  array|void  The JSON response to the module
	 *
	 * @since   3.8.0
	 */
	public function onAjaxSampledataApplyStep1()
	{
		return $this->step(1);
	}

	/**
	 * Step 2 of a set.
	 *
	 * @return  array|void  The JSON response to the module
	 *
	 * @since   3.8.0
	 */
	public function onAjaxSampledataApplyStep2()
	{
		return $this->step(2);
	}

	/**
	 * Step 3 of a set.
	 *
	 * @return  array|void  The JSON response to the module
	 *
	 * @since   3.8.0
	 */
	public function onAjaxSampledataApplyStep3()
	{
		return $this->step(3);
	}

	/**
	 * Step 4 of a set.
	 *
	 * @return  array|void  The JSON response to the module
	 *
	 * @since   3.8.0
	 */
	public function onAjaxSampledataApplyStep4()
	{
		return $this->step(4);
	}

	/**
	 * Step 5 of a set.
	 *
	 * @return  array|void  The JSON response to the module
	 *
	 * @since   3.8.0
	 */
	public function onAjaxSampledataApplyStep5()
	{
		return $this->step(5);
	}

	/**
	 * Step 6 of a set.
	 *
	 * @return  array|void  The JSON response to the module
	 *
	 * @since   3.8.0
	 */
	public function onAjaxSampledataApplyStep6()
	{
		return $this->step(6);
	}

	/**
	 * Step 7 of a set.
	 *
	 * @return  array|void  The JSON response to the module
	 *
	 * @since   3.8.0
	 */
	public function onAjaxSampledataApplyStep7()
	{
		return $this->step(7);
	}

	/**
	 * Step 8 of a set.
	 *
	 * @return  array|void  The JSON response to the module
	 *
	 * @since   3.17.0
	 */
	public function onAjaxSampledataApplyStep8()
	{
		return $this->step(8);
	}

	/**
	 * A set.
	 *
	 * @param   string  $name  The set
	 *
	 * @return  PlgSampledataBlogSet
	 *
	 * @since   3.17.0
	 */
	private function getSet($name)
	{
		JLoader::register('PlgSampledataBlogSet', __DIR__ . '/set.php');

		return new PlgSampledataBlogSet($this->app, $this->db, $name);
	}

	/**
	 * Run a step of the set in the request.
	 *
	 * @param   integer  $step  The step
	 *
	 * @return  array|void  The JSON response to the module (nothing for the sets of other plugins)
	 *
	 * @since   3.17.0
	 */
	private function step($step)
	{
		$input = $this->app->input;
		$name  = $input->post->getCmd('type');

		// From the Sample Data module only (com_ajax also runs plugins on the site), and only for this plugin's sets
		if (!$this->app->isClient('administrator') || !in_array($name, self::SETS, true))
		{
			return;
		}

		// A step replaces content: POST only, with the form token in the request's body (never in the URL, which ends up in logs)
		if (strtoupper($input->server->getWord('REQUEST_METHOD')) !== 'POST' || !Session::checkToken('post'))
		{
			return array('success' => false, 'message' => JText::_('JINVALID_TOKEN_NOTICE'));
		}

		if (!Factory::getUser()->authorise('core.admin'))
		{
			return array('success' => false, 'message' => JText::_('JERROR_ALERTNOAUTHOR'));
		}

		return $this->getSet($name)->step($step);
	}
}
