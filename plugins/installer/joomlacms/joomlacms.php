<?php
/**
 * Plugin with logic installation for joomla-cms
 * 
 * @package	joomlacli.plugins
 * @since	0.1
 */
class plgInstallerJoomlacms extends KPlugin implements KPluginInterfaceInstaller
{
	/**
	 * Logic for install after unarchive source
	 * 
	 * @param string $source_path
	 * @param array $arguments
	 * 
	 * @since 0.1
	 */
	public function onScafolding($arguments)
	{
		//fix github extraction
		if ($arguments['git_repo'] == 'joomla-cms')
		{
			$source_path = $arguments['d'];
			//check if exists installation folder
			if (!JFolder::exists($source_path.'/installation'))
			{
				$tmpFolder = JFolder::folders($source_path);
				$git_folder = $source_path.DIRECTORY_SEPARATOR.$tmpFolder[0];
				if (JFolder::exists($git_folder))
				{
					//move files
					$files = JFolder::files($git_folder, '.', true, true);
					foreach ($files as $tmpFile)
					{
						JFile::move($tmpFile, str_replace($tmpFolder,null,$tmpFile));
					}
					
					//move folders
					$folders = JFolder::folders($git_folder, '.', true, true);
					foreach ($folders as $subFolder)
					{
						JFolder::move($subFolder, str_replace($tmpFolder,null,$subFolder));
					}
					JFolder::delete($git_folder);
				}
			}
		}
		
		switch ($arguments['git_repo'])
		{
			case 'joomla-cms':
				$modelInstallation = KModelFactory::get('installer.joomlacms.model.installation');
				$modelInstallation->initialise($source_path, $arguments);
				break;
		}
	}

	/**
	 * Get source info
	 * 
	 * Return filename, version  number, url source
	 * 
	 * @param array $arguments
	 * 
	 * @since 0.1
	 */
	public function onPrepareVersion($arguments)
	{
		//if empty url we check github
		if (empty($arguments['url']))
		{
			$tags = KModelFactory::get('command.core.model.github')->tags($arguments['git_owner'], $arguments['git_repo']);
		
			$version = $arguments['v'];
			if (!empty($arguments['v']) && empty($tags->$version))
			{
				throw new RuntimeException(JText::_('PLG_CORE_INSTALL_INVALID_GITHUB_VERSION'));
			}
			
			if (empty($arguments['v']))
			{
				$properties = get_object_vars($tags);
				$keys = array_keys($properties);
				$arguments['v'] = max($keys);
			}
			
			$property = str_replace(' ','_',$arguments['v']);
			$gitTag = $tags->$property;
			
			return array('file' => $gitTag->file, 'version' => $version, 'url' => $gitTag->zipball_url);
		}
		
		//get version number/file from joomlacode url
		if (strpos($arguments['url'], 'joomlacode.org') !== false)
		{
			$file = JFile::getName($arguments['url']);
			preg_match('\d.\d.\d', $file, $matches);
			return array('file' => $file, 'version' => $matches[0], 'url' => $arguments['url']);
		}
		
		return array();
	}

	/**
	 * List version from joomla-cms git repo
	 * 
	 * @since 0.1
	 */
	public function onListVersions($functions, $arguments)
	{
		if ($arguments['git_repo'] == 'joomla-cms')
		{
			$tags = KModelFactory::get('command.core.model.github')->tags($arguments['git_owner'], $arguments['git_repo']);
			
			JApplicationCli::getInstance()->out(JText::_('PLG_COMMAND_CORE_VERSIONS'));
			$versions = array();
			foreach ($tags as $version => $data)
			{
				//dont support joomla-cms versions less than 2.5.4
				if ($version < '2.5.4') continue;
				
				$versions[intval($version)][] = $version;
			}
			asort($versions);
			
			$current_group = 0;
			foreach ($versions as $group => $versions)
			{
				JApplicationCli::getInstance()->out($group.'.X');
				asort($versions);
				foreach ($versions as $version)
				{
					JApplicationCli::getInstance()->out(chr(9).$version);
				}
			}
		}
		
	}
}