<?php
/**
 * Model for installation of joomla-cms
 * 
 * @package	joomlacli
 * @since	0.1
 */
class InstallerJoomlacmsModelInstallation extends KModelBase
{
	public function initialise($source_path, $arguments)
	{
		// Add the logger.
		JLog::addLogger(
		     // Pass an array of configuration options
		    array(
		            // Set the name of the log file
		            'text_file' => 'command.'.$date.'.php',
		            // (optional) you can change the directory
		            'text_file_path' => JPATH_VAR.'/logs/'
		     ),
		     JLog::INFO,
		     'Command'
		);
		
		if (!JFolder::exists($source_path.'/installation'))
		{
			throw new RuntimeException(JText::sprintf('PLG_JOOMLACMS_INSTALLATION_ERROR_CANT_FIND_INSTALLATION_APPLICATION_CURRENT_FOLDER', $source_path));
		}
		
		$version = substr($arguments['v'],0,3);
		
		if ($version > '1.7')
		{
			define('JPATH_ROOT', $source_path);
			define('JPATH_SITE', $source_path);
			define('JPATH_CONFIGURATION', $source_path);
			define('JPATH_INSTALLATION', $source_path.'/installation');
			define('JPATH_ADMINISTRATOR', $source_path.'/administrator');
			define('JPATH_MANIFESTS',     JPATH_ADMINISTRATOR . '/manifests');
			
			chdir(JPATH_INSTALLATION);
			JFactory::$application = null;
			$installation = JFactory::getApplication('installation');
			
			$database_prefix = !empty($arguments['db_prefix']) ? $arguments['db_prefix'] : 'jos_' ;
			$database_name = !empty($arguments['db_name']) ? $arguments['db_name'] : 'joo_'.str_replace('.','_',basename($source_path)) ;
			
			require_once JPATH_CONFIG.'/database.php';
			$configDatabase = new JConfigdatabase();
			
			$db_type = !empty($arguments['db_type']) ? $arguments['db_type'] : $configDatabase->driver ;
			$db_host = !empty($arguments['db_host']) ? $arguments['db_host'] : $configDatabase->host ;
			$db_user = !empty($arguments['db_user']) ? $arguments['db_user'] : $configDatabase->user ;
			$db_pass = !empty($arguments['db_pass']) ? $arguments['db_pass'] : $configDatabase->pass ;
			
			if (empty($db_type))
			{
				$db_type = 'mysql';
			}
			
			$admin_user = !empty($arguments['u']) ? $arguments['u'] : 'admin' ;
			$admin_password = !empty($arguments['p']) ? $arguments['p'] : 'admin' ;
			$admin_email = !empty($arguments['email']) ? $arguments['email'] : 'admin@admin.com' ;
			
			$options = array(
				"site_offline" => false,
				"site_name" => "Joomla Site",
				"site_metadesc" => "",
				"site_metakeys" => "",
				"db_type" => $db_type,
				"db_host" => $db_host,
				"db_user" => $db_user,
				"db_pass" => $db_pass,
				"db_name" => $database_name,
				"db_prefix" => $database_prefix,
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
			
			JModelLegacy::addIncludePath($source_path.'/installation/models');
			
			$modelPrefix = 'InstallationModel';
			if ($version == '2.5')
			{
				$modelPrefix = 'J'.$modelPrefix;;
			}
			
			if ($arguments['v'] <= '2.5.4')
			{
				require_once 'database.php';
				require_once 'configuration.php';
			}
			
			// Attempt to create the database tables.
			$installationModeldatabase		= JModelLegacy::getInstance('database', $modelPrefix, array('dbo' => null));
			//create configuration and root user
			$installationModelConfiguration = JModelLegacy::getInstance('configuration', $modelPrefix, array('dbo' => null));
			
			if ($installationModeldatabase == false || $installationModelConfiguration == false)
			{
				JApplicationCli::getInstance()->out(JText::sprintf('PLG_JOOMLACMS_INSTALLATION_CANT_INSTANCE_MODELS', $modelPrefix.'Database', $modelPrefix.'Configuration', $source_path));
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
				else if ($version == '3.0')
				{
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
				
				JApplicationCli::getInstance()->out(JText::_('PLG_JOOMLACMS_INSTALLATION_CREATING_CONFIGURATION'));
				
				$logEntry = new JLogEntry(JText::_('PLG_JOOMLACMS_INSTALLATION_CREATING_CONFIGURATION'), JLog::INFO, 'INSTALLATION');
				JLog::add($logEntry);
				
				//create configuration
				$returnConfiguration = $installationModelConfiguration->setup($options);
				if (!$returnConfiguration) {
					throw new RuntimeException($installationModelConfiguration->getError());
				}
				
				//remove installation dir
				JFolder::delete($source_path.'/installation');
				
				//info user and pass
				JApplicationCli::getInstance()->out(JText::sprintf('PLG_JOOMLACMS_INSTALLATION_MESSAGE', $admin_user, $admin_password));
			}
		}
		
		$logEntry = new JLogEntry(JText::_('PLG_JOOMLACMS_INSTALLATION_FINISHED_INSTALLATION'), JLog::INFO, 'INSTALLATION');
		JLog::add($logEntry);
		
		JApplicationCli::getInstance()->out(JText::_('PLG_JOOMLACMS_INSTALLATION_FINISHED_INSTALLATION'));
	}
}