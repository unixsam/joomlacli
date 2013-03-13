<?php
/**
 * Plugin for core commands
 * 
 * @package joomlacli.plugins
 * @since	0.1
 */
class plgCommandCore extends KPluginCommand
{
	/**
	 * Versions list command
	 * 
	 * @param array $functions
	 * @param array $arguments
	 */
	public function commandversions($functions, $arguments)
	{
		if (!isset($arguments['git_owner']))
		{
			$arguments['git_owner'] = 'joomla';
		}

		if (!isset($arguments['git_repo']))
		{
			$arguments['git_repo'] = 'joomla-cms';
		}
		
		KPluginHelper::importPlugin('installer');
		JApplicationCli::getInstance()->triggerEvent('onListVersions', array($functions, $arguments));
	}

	/**
	 * Database dbsetup command
	 * 
	 * @param array $functions
	 * @param array $arguments
	 */
	public function commanddbsetup($functions, $arguments)
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
			$file_path = JPATH_CONFIG."/{$configurationFile}.php";
			
			$registry = new JRegistry();
			foreach ($parameters as $parameter)
			{
				JApplicationCli::getInstance()->out(JText::_('CONFIG_'.$configurationFile.'_'.$parameter));
				$registry->set($parameter , JApplicationCli::getInstance()->in());
			}
			
			// Generate the configuration class string buffer.
			$buffer = $registry->toString('PHP', array('class' => 'JConfig'.$configurationFile, 'closingtag' => false));
			
			//write configuration file
			if (!JFile::write($file_path, $buffer))
			{
				JApplicationCli::getInstance()->out(JText::sprinf('CONFIG_CANT_WRITE_FILE', $file_path));
			}
		}
	}

	/**
	 * Help command
	 * 
	 * @param array $functions
	 * @param array $arguments
	 */
	final public function commandhelp($functions, $arguments)
	{
		$commandPaths = JFolder::folders(JPATH_BASE.'/plugins/'.$this->_type, '.', false, true);
		
		if (empty($functions[0]))
		{
			JApplicationCli::getInstance()->out(JText::_('HELP_COMMANDS_USAGE'));
			JApplicationCli::getInstance()->out();
			JApplicationCli::getInstance()->out(JText::_('HELP_COMMANDS_LIST'));
			$tableInfo = array();
			foreach ($commandPaths as $commandPath)
			{
				$file_path = $commandPath.'/help.json';
				if (!JFile::exists($file_path))
				{
					continue;			
				}
			
				$help_content = JFile::read($file_path);
			
				try {
					$helps = json_decode($help_content);
					
					foreach ($helps as $command => $info)
					{
						$tableInfo[0][] = $command;
						$tableInfo[1][] = JText::_($info->short_desc);
					}
				}
				catch(Exception $e) {
				}
			}
			
			$table = new KTable;
			$table->render($tableInfo);
			
			JApplicationCli::getInstance()->out();
			JApplicationCli::getInstance()->out(JText::_('HELP_COMMANDS_COMMAND'));
		}
		else 
		{
			foreach ($commandPaths as $commandPath)
			{
				$file_path = $commandPath.'/help.json';
				if (!JFile::exists($file_path))
				{
					continue;			
				}
				
				$help_content = JFile::read($file_path);
				
				try {
					$helps = json_decode($help_content);
					
					$commandHelp = $helps->$functions[0];
					if (!empty($commandHelp)) break;
				}
				catch(Exception $e) {
					continue;
				}
			}
				
			if (empty($commandHelp))
			{
				throw new RuntimeException(JText::sprintf('PLG_CORE_COMMAND_DONT_SUPPORT_HELP_FUNCTION',$functions[0]));
			}
			
			JApplicationCli::getInstance()->out(JText::sprintf('HELP_COMMAND_NAME', JText::_($commandHelp->name)));
			JApplicationCli::getInstance()->out(JText::sprintf('HELP_COMMAND_DESC', JText::_($commandHelp->desc)));
			JApplicationCli::getInstance()->out(JText::_('HELP_COMMAND_OPTIONS'));
			if ($commandHelp->options)
			{
				foreach ($commandHelp->options as $option)
				{
					JApplicationCli::getInstance()->out(chr(9).$option->name);
					JApplicationCli::getInstance()->out(chr(9).'  '.JText::_($option->desc));
				}
			}
		}
	}

	/**
	 * Install command
	 * 
	 * @param array $functions
	 * @param array $arguments
	 */
	public function commandinstall($functions, $arguments)
	{
		if (!function_exists('curl_init')) {
			throw new RuntimeException(JText::sprintf('PLG_CORE_COMMAND_NEED_PHP_MODULE','curl'));
		}
		
		$config = new JRegistry();
		$config->set('command', $functions);
		$config->set('arguments', $arguments);
		
		$modelInstall = KModelFactory::get('command.core.model.install', $config);
		$modelInstall->prepareVersion();
		$modelInstall->prepareDirectory();
		$modelInstall->preparePackage();
		$modelInstall->scafolding();
	}
}
