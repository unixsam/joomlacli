<?php
/**
 * @package     Joomlacli.plugins
 * @subpackage  Core
 * @copyright   Copyright 2012 joomlacli. All rights re-served.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Model for installation proccess
 * 
 * @package  Joomlacli.plugins
 * @since    0.1
 */
class CommandCoreModelInstall extends JModelBase
{
	/**
	 * Prepare package
	 * 
	 * @return void
	 * 
	 * @since  1.0
	 */
	public function prepareDirectory()
	{
		$input = JApplicationCli::getInstance()->input;

		$source_path = JApplicationCli::getInstance()->get('cwd');
		$source_path .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $input->args);

		// Set Path Install Directory value
		$input->set('pid', $source_path);

		return true;
	}

	/**
	 * Download package url
	 * 
	 * @param   String  $url     Url file to download.
	 * @param   String  $target  Full path name to write file.
	 * 
	 * @return void
	 * 
	 * @since  1.0
	 */
	private function downloadPackageUrl($url, $target = false)
	{
		$logEntry = new JLogEntry(sprintf('downloading %s', $url), JLog::INFO, 'GITHUB');
		JLog::add($logEntry);

		// Capture PHP errors
		$track_errors = ini_get('track_errors');
		ini_set('track_errors', true);
		$options = new JRegistry;
		$options->set('curl.certpath', JPATH_ETC . '/transport/cacert.pem');
		$http = JHttpFactory::getHttp($options);
		$response = $http->get($url);
		if (200 != $response->code)
		{
			throw new RuntimeException(JText::_('CORE_ERROR_CANT_SERVER_CONNECT'));
		}

		if (isset($response->headers['wrapper_data']['Content-Disposition']))
		{
			$contentfilename = explode("\"", $response->headers['wrapper_data']['Content-Disposition']);
			$target = $contentfilename[1];
		}

		// Set the target path if not given
		$target = JPATH_TMP . '/' . basename($target);

		$target = JPath::clean($target);

		// Write buffer to file
		JFile::write($target, $response->body);

		$logEntry = new JLogEntry(sprintf('file have been created in %s', $target), JLog::INFO, 'GITHUB');
		JLog::add($logEntry);

		// Restore error tracking to what it was before
		ini_set('track_errors', $track_errors);

		// Bump the max execution time because not using built in php zip libs are slow
		@set_time_limit(ini_get('max_execution_time'));

		// Return the name of the downloaded package
		return basename($target);
	}

	/**
	 * Prepare package
	 * 
	 * @return void
	 * 
	 * @since  1.0
	 */
	public function preparePackage()
	{
		$input = JApplicationCli::getInstance()->input;

		if (empty($this->file))
		{
			throw new RuntimeException(JText::_('PLG_CORE_INSTALL_INVALID_SOURCE_URL'));
		}
		JApplicationCli::getInstance()->out(JText::_('PLG_CORE_INSTALL_STARTING_INSTALLATION'));
		$url = $input->getString('url');
		$file = $this->file;

		if (!JFile::exists(JPATH_TMP . '/' . $file))
		{
			// Download git package
			$package = $this->downloadPackageUrl($url, $file);
		}
	}

	/**
	 * Prepare package
	 * 
	 * @return void
	 * 
	 * @since  1.0
	 */
	public function scafolding()
	{
		$input = JApplicationCli::getInstance()->input;

		$source_path = $input->getString('pid');

		// Unpack the downloaded package file
		try
		{
			$logEntry = new JLogEntry(sprintf('Prepare directory %s', $source_path), JLog::INFO, 'INSTALLATION');
			JLog::add($logEntry);

			if (!JFolder::exists($source_path))
			{
				JApplicationCli::getInstance()->out(JText::sprintf('PLG_CORE_INSTALL_PREPARING_FOLDER', $source_path));

				if (!JFolder::create($source_path))
				{
					throw new RuntimeException(JText::sprintf('PLG_CORE_INSTALL_ERROR_CANT_CREATE_FOLDER', $source_path));
				}

				$logEntry = new JLogEntry(sprintf('Extracting %s in %s', JPATH_TMP . '/' . $this->file, $source_path), JLog::INFO, 'INSTALLATION');
				JLog::add($logEntry);
				JArchive::extract(JPATH_TMP . '/' . $this->file, $source_path);
			}
		}
		catch (Exception $e)
		{
			throw new RuntimeException(JText::_('PLG_CORE_INSTALL_EXTRACT_FILE_ERROR'));
		}

		JCliPluginHelper::importPlugin('installer');
		JApplicationCli::getInstance()->triggerEvent('onScafolding');
	}

	/**
	 * Discover version, url
	 * 
	 * @return void
	 * 
	 * @since  1.0
	 */
	public function prepareVersion()
	{
		$input = JApplicationCli::getInstance()->input;

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

		JCliPluginHelper::importPlugin('installer');
		$versionsInfo = JApplicationCli::getInstance()->triggerEvent('onPrepareVersion');

		// Set filename and version
		if (!empty($versionsInfo))
		{
			foreach ($versionsInfo as $versionInfo)
			{
				if (!empty($versionInfo['file']) && !empty($versionInfo['version']) && !empty($versionInfo['url']))
				{
					$this->file = $versionInfo['file'];
					$input->set('v', $versionInfo['version']);
					$input->set('url', $versionInfo['url']);
					return;
				}
			}
		}
	}
}
