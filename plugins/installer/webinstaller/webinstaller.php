<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Installer.webinstaller
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Rule\UrlRule;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Updater\Update;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

/**
 * Support for the "Install from Web" tab
 *
 * @since  1.0
 */
class PlgInstallerWebinstaller extends CMSPlugin
{
	/**
	 * The URL for the remote server.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const REMOTE_URL = 'https://appscdn.joomla.org/webapps/';

	/**
	 * The application object.
	 *
	 * @var    CMSApplication
	 * @since  2.0
	 */
	protected $app;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * The URL to install from
	 *
	 * @var    string|null
	 * @since  1.0
	 */
	private $installfrom = null;

	/**
	 * Event listener for the `onInstallerBeforeDisplay` event.
	 *
	 * @param   boolean  $showJedAndWebInstaller  Flag indicating the install from web prompt should be displayed
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onInstallerBeforeDisplay(&$showJedAndWebInstaller)
	{
		$showJedAndWebInstaller = false;
	}

	/**
	 * Event listener for the `onInstallerAddInstallationTab` event.
	 *
	 * @return  array  Returns an array with the tab information
	 *
	 * @since   2.0
	 */
	public function onInstallerAddInstallationTab()
	{
		$tab = array(
			'name'  => 'web',
			'label' => Text::_('COM_INSTALLER_INSTALL_FROM_WEB'),
		);

		// Render the input
		ob_start();
		include PluginHelper::getLayoutPath('installer', 'webinstaller');
		$tab['content'] = ob_get_clean();

		return $tab;
	}

	/**
	 * Event listener for the `onBeforeCompileHead` event.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 * @deprecated  Removed when the plugin is merged to 4.0
	 */
	public function onBeforeCompileHead()
	{
		// Installer plugins are also loaded elsewhere (e.g. Privacy: Capabilities), which have no Install from Web tab
		$input = $this->app->input;

		if (!$this->app->isClient('administrator') || $input->get('option') !== 'com_installer' || $input->get('view', 'install') !== 'install'
			|| Factory::getDocument()->getType() !== 'html')
		{
			return;
		}

		$installfrom = $this->getInstallFrom();

		// Push language strings to the JavaScript store
		Text::script('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL');
		Text::script('PLG_INSTALLER_WEBINSTALLER_CANNOT_INSTALL_EXTENSION_IN_PLUGIN');
		Text::script('PLG_INSTALLER_WEBINSTALLER_REDIRECT_TO_EXTERNAL_SITE_TO_INSTALL');

		HTMLHelper::_('behavior.core');
		HTMLHelper::_('script', 'plg_installer_webinstaller/client.min.js', array('version' => 'auto', 'relative' => true));
		HTMLHelper::_('stylesheet', 'plg_installer_webinstaller/client.min.css', array('version' => 'auto', 'relative' => true));

		$devLevel = Version::PATCH_VERSION;
		$extraVer = Version::EXTRA_VERSION;

		if (!empty($extraVer))
		{
			$devLevel .= '-' . $extraVer;
		}

		$installer = new Installer;
		$manifest  = $installer->isManifest(__DIR__ . '/webinstaller.xml');

		$doc = Factory::getDocument();

		$doc->addScriptOptions(
			'plg_installer_webinstaller',
			array(
				'base_url'        => self::REMOTE_URL,
				'installat_url'   => base64_encode(Uri::current() . '?option=com_installer&view=install'),
				'installfrom_url' => $installfrom,
				'product'         => base64_encode(Version::PRODUCT),
				'release'         => base64_encode(Version::MAJOR_VERSION . '.' . Version::MINOR_VERSION),
				'dev_level'       => base64_encode($devLevel),
				'installfromon'   => $installfrom ? 1 : 0,
				'language'        => base64_encode(Factory::getLanguage()->getTag()),
				// The plugin's version, which the remote server compares with its latest release
				'pv'              => base64_encode($manifest ? (string) $manifest->version : ''),
			)
		);
	}

	/**
	 * Get the install from URL
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	private function getInstallFrom()
	{
		if ($this->installfrom === null)
		{
			$installfrom = base64_decode($this->app->input->getBase64('installfrom', ''));

			$field = new SimpleXMLElement('<field></field>');
			$rule  = new UrlRule;

			if ($rule->test($field, $installfrom) && preg_match('/\.xml\s*$/', $installfrom))
			{
				$update = new Update;
				$update->loadFromXml($installfrom);
				$download    = $update->get('downloadurl', false);
				$package_url = $download ? trim((string) $download->_data) : '';

				if ($package_url)
				{
					$installfrom = $package_url;
				}
			}

			$this->installfrom = $installfrom;
		}

		return $this->installfrom;
	}
}
