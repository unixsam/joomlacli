<?php
/**
 * @package     Joomlacli.plugins
 * @subpackage  Core
 * @copyright   Copyright 2012 joomlacli. All rights re-served.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Plugin with logic installation for joomla-cms
 * 
 * @package  Joomlacli.plugins
 * @since    0.1
 */
class PlgInstallerJoomlacms extends JPlugin implements JPluginInterfaceInstaller
{
	/**
	 * Call when extract github source
	 * 
	 * @return  void
	 * 
	 * @since   1.0
	 */
	public function onScafolding()
	{
		$input = JApplicationCli::getInstance()->input;

		$git_repo	 = $input->get('git_repo');

		// Fix github extraction
		if ($git_repo == 'joomla-cms')
		{
			$source_path = $input->getString('pid');

			// Check if exists installation folder
			if (!JFolder::exists($source_path . '/installation'))
			{
				$tmpFolder = JFolder::folders($source_path);
				$git_folder = $source_path . DIRECTORY_SEPARATOR . $tmpFolder[0];
				if (JFolder::exists($git_folder))
				{
					// Move files
					$files = JFolder::files($git_folder, '.', true, true);
					foreach ($files as $tmpFile)
					{
						JFile::move($tmpFile, str_replace($tmpFolder, null, $tmpFile));
					}

					// Move folders
					$folders = JFolder::folders($git_folder, '.', true, true);
					foreach ($folders as $subFolder)
					{
						JFolder::move($subFolder, str_replace($tmpFolder, null, $subFolder));
					}
					JFolder::delete($git_folder);
				}
			}
		}

		switch ($git_repo)
		{
			case 'joomla-cms':
				$modelInstallation = JModelFactory::get('installer.joomlacms.model.installation');
				$modelInstallation->initialise();
				break;
		}
	}

	/**
	 * Get source info
	 * 
	 * Return filename, version  number, url source
	 * 
	 * @return  Array
	 * 
	 * @since   1.0
	 */
	public function onPrepareVersion()
	{
		$input = JApplicationCli::getInstance()->input;

		$url     = $input->get('url');
		$version = $input->get('v');

		// If empty url we check github
		if (empty($url))
		{
			$tags = JModelFactory::get('command.core.model.github')->tags($input->get('git_owner'), $input->get('git_repo'));

			if (!empty($version) && empty($tags->$version))
			{
				throw new RuntimeException(JText::_('PLG_CORE_INSTALL_INVALID_GITHUB_VERSION'));
			}

			if (empty($version))
			{
				$properties = get_object_vars($tags);
				$keys = array_keys($properties);
				$input->set('v', max($keys));
				$version = $input->get('v');
			}

			$property = str_replace(' ', '_', $version);
			$gitTag = $tags->$property;

			return array('file' => $gitTag->file, 'version' => $version, 'url' => $gitTag->zipball_url);
		}

		// Get version number/file from joomlacode url
		if (strpos($url, 'joomlacode.org') !== false)
		{
			$file = JFile::getName($url);
			preg_match('\d.\d.\d', $file, $matches);
			return array('file' => $file, 'version' => $matches[0], 'url' => $url);
		}

		return array();
	}

	/**
	 * List version from joomla-cms git repo
	 * 
	 * @return  void
	 * 
	 * @since   1.0
	 */
	public function onListVersions()
	{
		$input = JApplicationCli::getInstance()->input;

		$git_owner	 = $input->get('git_owner');
		$git_repo	 = $input->get('git_repo');

		if ($git_owner == 'joomla' && $git_repo == 'joomla-cms') {
			$tags = JModelFactory::get('command.core.model.github')->tags($git_owner, $git_repo);

			JApplicationCli::getInstance()->out(JText::_('PLG_COMMAND_CORE_VERSIONS'));
			$versions = array();
			foreach ($tags as $version => $data)
			{
				// Dont support joomla-cms versions less than 2.5.4
				if ($version < '2.5.5')
					continue;

				$versions[intval($version)][] = $version;
			}
			asort($versions);

			$current_group = 0;
			foreach ($versions as $group => $versions)
			{
				JApplicationCli::getInstance()->out($group . '.X');
				asort($versions);
				foreach ($versions as $version)
				{
					JApplicationCli::getInstance()->out(chr(9) . $version);
				}
			}
		}
		else {
			$tags = JModelFactory::get('command.core.model.github')->tags($git_owner, $git_repo);
			asort($tags);
			JApplicationCli::getInstance()->out(JText::_('PLG_COMMAND_CORE_VERSIONS'));
			foreach ($tags as $tag) {
				JApplicationCli::getInstance()->out(chr(9) . $tag->name);
			}
		}
	}
}
