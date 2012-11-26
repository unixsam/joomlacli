<?php
/**
 * @package     Joomlacli.plugins
 * @subpackage  Core
 * @copyright   Copyright 2012 joomlacli. All rights re-served.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Plugin for core commands
 * 
 * @package  Joomlacli.plugins
 * @since    1.0
 */
class PlgCommandCore extends JPluginCommand
{
	/**
	 * Versions list command
	 * 
	 * @return  void
	 * 
	 * @since   1.0
	 **/
	public function cmd_versions()
	{
		$input		 = JApplicationCli::getInstance()->input;

		$git_owner	 = $input->get('git_owner');
		$git_repo	 = $input->get('git_repo');

		if (!isset($git_owner))
		{
			$input->set('git_owner', 'joomla');
		}

		if (!isset($git_repo))
		{
			$input->set('git_repo', 'joomla-cms');
		}

		JPluginHelper::importPlugin('installer');
		JApplicationCli::getInstance()->triggerEvent('onListVersions');
	}

	/**
	 * Database setup command
	 * 
	 * @return  void
	 * 
	 * @since   1.0
	 **/
	public function cmd_dbsetup()
	{
		$configs = array(
			'database' => array(
				'driver',
				'host',
				'user',
				'pass',
			)
		);

		foreach ($configs as $configurationFile => $parameters)
		{
			$file_path = JPATH_CONFIG . "/{$configurationFile}.php";

			$registry = new JRegistry;
			foreach ($parameters as $parameter)
			{
				JApplicationCli::getInstance()->out(JText::_('CONFIG_' . $configurationFile . '_' . $parameter));
				$registry->set($parameter, JApplicationCli::getInstance()->in());
			}

			// Generate the configuration class string buffer.
			$buffer = $registry->toString('PHP', array('class' => 'JConfig' . $configurationFile, 'closingtag' => false));

			// Write configuration file
			if (!JFile::write($file_path, $buffer))
			{
				JApplicationCli::getInstance()->out(JText::sprinf('CONFIG_CANT_WRITE_FILE', $file_path));
			}
		}
	}

	/**
	 * Help command
	 * 
	 * @return  void
	 * 
	 * @since   1.0
	 **/
	final public function cmd_help()
	{
		$input		  = JApplicationCli::getInstance()->input;
		$commandPaths = JFolder::folders(JPATH_BASE . '/plugins/' . $this->_type, '.', false, true);

		if (empty($input->args[0]))
		{
			JApplicationCli::getInstance()->out(JText::_('HELP_COMMANDS_USAGE'));
			JApplicationCli::getInstance()->out();
			JApplicationCli::getInstance()->out(JText::_('HELP_COMMANDS_LIST'));
			$tableInfo = array();
			foreach ($commandPaths as $commandPath)
			{
				$file_path = $commandPath . '/help.json';
				if (!JFile::exists($file_path))
				{
					continue;
				}

				$help_content = file_get_contents($file_path);
				try
				{
					$helps = json_decode($help_content);

					foreach ($helps as $command => $info)
					{
						$tableInfo[] = array($command, JText::_($info->short_desc));
					}
				}
				catch (Exception $e)
				{
				}
			}

			$table = new JCliTable;
			$table->setRows($tableInfo);
			$table->display();

			JApplicationCli::getInstance()->out();
			JApplicationCli::getInstance()->out(JText::_('HELP_COMMANDS_COMMAND'));
		}
		else
		{
			foreach ($commandPaths as $commandPath)
			{
				$file_path = $commandPath . '/help.json';
				if (!JFile::exists($file_path))
				{
					continue;
				}

				$help_content = file_get_contents($file_path);

				try
				{
					$function 	 = $input->args[0];
					$helps 		 = json_decode($help_content);
					$commandHelp = $helps->$function;
					if (!empty($commandHelp))
						break;
				}
				catch (Exception $e)
				{
					continue;
				}
			}

			if (empty($commandHelp))
			{
				throw new RuntimeException(JText::sprintf('PLG_CORE_COMMAND_DONT_SUPPORT_HELP_FUNCTION', $input->args[0]));
			}

			JApplicationCli::getInstance()->out(JText::sprintf('HELP_COMMAND_NAME', JText::_($commandHelp->name)));
			JApplicationCli::getInstance()->out(JText::sprintf('HELP_COMMAND_DESC', JText::_($commandHelp->desc)));
			JApplicationCli::getInstance()->out(JText::_('HELP_COMMAND_OPTIONS'));
			if ($commandHelp->options)
			{
				foreach ($commandHelp->options as $option)
				{
					JApplicationCli::getInstance()->out(chr(9) . $option->name);
					JApplicationCli::getInstance()->out(chr(9) . '  ' . JText::_($option->desc));
				}
			}
		}
	}

	/**
	 * Install command
	 * 
	 * @return  void
	 * 
	 * @since   1.0
	 **/
	public function cmd_install()
	{
		$modelInstall = JModelFactory::get('command.core.model.install');
		$modelInstall->prepareVersion();
		$modelInstall->prepareDirectory();
		$modelInstall->preparePackage();
		$modelInstall->scafolding();
	}
}
