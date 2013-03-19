<?php
/**
 * @package     Joomlacli.plugins
 * @subpackage  Core
 * @copyright   Copyright 2012 joomlacli. All rights re-served.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Model for installation of joomla-cms
 * 
 * @package  Joomlacli.plugins
 * @since    0.1
 */
class InstallerJoomlacmsModelInstallation extends JModelBase
{
	/**
	 * Intialize automatic installation
	 * 
	 * @return  void
	 * 
	 * @since   1.0
	 */
	public function initialise()
	{
		$input = JApplicationCli::getInstance()->input;

		$version = $input->get('v');
		$source_path = $input->getString('pid');
		$db_prefix = $input->get('db_prefix', chr(97 + mt_rand(0, 25)) . chr(97 + mt_rand(0, 25)) . chr(97 + mt_rand(0, 25)) . "_");
		$db_name = $input->get('db_name', 'joo_' . str_replace('.', '_', basename($source_path)));

		if (!JFolder::exists($source_path . '/installation'))
		{
			throw new RuntimeException(JText::sprintf('PLG_JOOMLACMS_INSTALLATION_ERROR_CANT_FIND_INSTALLATION_APPLICATION_CURRENT_FOLDER', $source_path));
		}

		$version = substr($version, 0, 3);
		if ($version > '1.7')
		{
			define('JPATH_ROOT', $source_path);
			define('JPATH_SITE', $source_path);
			define('JPATH_CONFIGURATION', $source_path);
			define('JPATH_INSTALLATION', $source_path . '/installation');
			define('JPATH_ADMINISTRATOR', $source_path . '/administrator');
			define('JPATH_MANIFESTS',     JPATH_ADMINISTRATOR . '/manifests');

			chdir(JPATH_INSTALLATION);
			JFactory::$application = null;
			$installation = JFactory::getApplication('installation');

			if ($installation instanceof JException) {
				$installation = JApplicationWeb::getInstance('InstallationApplicationWeb');
			}
			
			JFactory::$application = $installation;
			
			require_once JPATH_CONFIG . '/database.php';
			$configDatabase = new JConfigdatabase;

			$db_type = $input->get('db_type', $configDatabase->driver);
			$db_host = $input->get('db_host', $configDatabase->host);
			$db_user = $input->get('db_user', $configDatabase->user);
			$db_pass = $input->get('db_pass', $configDatabase->pass);

			if (empty($db_type))
			{
				$db_type = 'mysql';
			}

			$admin_user = $input->get('u', 'admin');
			$admin_password = $input->get('p', 'admin');
			$admin_email = $input->get('email', 'admin@admin.com');

			$options = array(
				"site_offline" => false,
				"site_name" => "Joomla Site",
				"site_metadesc" => "",
				"site_metakeys" => "",
				"db_type" => $db_type,
				"db_host" => $db_host,
				"db_user" => $db_user,
				"db_pass" => $db_pass,
				"db_name" => $db_name,
				"db_prefix" => $db_prefix,
				"db_old" => 'remove',
				"db_select" => false,
				"ftp_host" => "localhost",
				"ftp_port" => "21",
				"ftp_save" => true,
				"ftp_user" => "admin",
				"ftp_pass" => "admin",
				"ftp_root" => "/",
				"ftp_enable" => false,
				"ftpEnable" => false,
				"admin_password" => $admin_password,
				"admin_user" => $admin_user,
				"admin_email" => $admin_email
			);

			JModelLegacy::addIncludePath($source_path . '/installation/models');
			JModelLegacy::addIncludePath($source_path . '/installation/model');
			
			if (is_file($source_path.'/installation/helper/database.php')) {
				require_once $source_path.'/installation/helper/database.php';
			}
			
			$modelPrefix = 'InstallationModel';
			if ($version == '2.5')
			{
				$modelPrefix = 'J' . $modelPrefix;
			}
			
			// Attempt to create the database tables.
			$installationModeldatabase		= JModelLegacy::getInstance('database', $modelPrefix, new JRegistry(array('dbo' => null)));

			// Create configuration and root user
			$installationModelConfiguration = JModelLegacy::getInstance('configuration', $modelPrefix, new JRegistry(array('dbo' => null)));

			if ($installationModeldatabase == false || $installationModelConfiguration == false)
			{
				JApplicationCli::getInstance()->out(JText::_('PLG_JOOMLACMS_INSTALLATION_CANT_AUTOMATISE_INSTALLATION'));
			}
			else
			{
				JApplicationCli::getInstance()->out(JText::_('PLG_JOOMLACMS_INSTALLATION_CREATING_DATABASE'));

				$logEntry = new JLogEntry(JText::_('PLG_JOOMLACMS_INSTALLATION_CREATING_DATABASE'), JLog::INFO, 'INSTALLATION');
				JLog::add($logEntry);
				
				if ($version == '2.5')
				{
					$return = $installationModeldatabase->initialise($options);

					// Check if creation of database tables was successful
					if (!$return)
					{
						throw new RuntimeException($installationModeldatabase->getError());
					}
				}
				elseif ($version >= '3.0')
				{
					try {
						
						$return = $installationModeldatabase->createDatabase($options);
						
						// Check if creation of database tables was successful
						if (!$return)
						{
							throw new RuntimeException($installationModeldatabase->getError());
						}
	
						$options['db_created'] = 1;
	
						$returnDatabase = $installationModeldatabase->createTables($options);
	
						// Check if the database was initialised
						if (!$returnDatabase)
						{
							throw new RuntimeException($installationModeldatabase->getError());
						}
					}
					catch (Exception $e) {
						die($e->getTraceAsString());
					}
				}

				JApplicationCli::getInstance()->out(JText::_('PLG_JOOMLACMS_INSTALLATION_CREATING_CONFIGURATION'));

				$logEntry = new JLogEntry(JText::_('PLG_JOOMLACMS_INSTALLATION_CREATING_CONFIGURATION'), JLog::INFO, 'INSTALLATION');
				JLog::add($logEntry);

				// Create configuration
				$returnConfiguration = $installationModelConfiguration->setup($options);
				if (!$returnConfiguration)
				{
					throw new RuntimeException($installationModelConfiguration->getError());
				}

				// Remove installation dir
				JFolder::delete($source_path . '/installation');

				// Info user and pass
				JApplicationCli::getInstance()->out(JText::sprintf('PLG_JOOMLACMS_INSTALLATION_MESSAGE', $admin_user, $admin_password));
			}
		}

		$logEntry = new JLogEntry(JText::_('PLG_JOOMLACMS_INSTALLATION_FINISHED_INSTALLATION'), JLog::INFO, 'INSTALLATION');
		JLog::add($logEntry);
		JApplicationCli::getInstance()->out(JText::_('PLG_JOOMLACMS_INSTALLATION_FINISHED_INSTALLATION'));
	}
}
