<?php
/**
 * @package     Joomlacli.plugins
 * @subpackage  Core
 * @copyright   Copyright 2012 joomlacli. All rights re-served.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Model for install extension integration
 * 
 * @package  Joomlacli.plugins
 * @since    1.1
 */
class CommandCoreModelAdd extends JModelBase
{
	protected $extension;

	/**
	 * Install extension
	 * 
	 * @return  mixed
	 * 
	 * @since   1.1
	 */
	public function initialise()
	{
		$cli = JApplicationCli::getInstance();
		$input = $cli->input;

		if (!$this->checkDirectory())
		{
			Throw new RuntimeException(JText::sprintf('ERROR_CURRENT_DIRECTORY_IS_NOT_A_VALID_APPLICATION_DIRECTORY', 'Joomla CMS'));
		}

		if (empty($input->args[0]) || !$this->findExtension($input->args[0]))
		{
			Throw new RuntimeException(JText::sprintf('ERROR_EXTENSION_NOT_FOUND_OR_NOT_AVALIABLE', $input->args[0]));
		}

		$cli->out(JText::sprintf('ADD_COMMAND_REQUEST_INFO_FORM_URL', $this->extension->name));

		$options = new JRegistry;
		$options->set('curl.certpath', JPATH_ETC . '/transport/cacert.pem');
		$http = JHttpFactory::getHttp($options);
		$response = $http->get($this->extension->url);
		if (200 != $response->code)
		{
			// TODO: Add a 'mark bad' setting here somehow
			JLog::add(JText::sprintf('JLIB_UPDATER_ERROR_EXTENSION_OPEN_URL', $url), JLog::WARNING, 'jerror');
			return false;
		}

		$updateXML = simplexml_load_string($response->body);
		$latest_version = 0;
		$package_url = null;
		foreach ($updateXML as $node)
		{
			if ($latest_version < $node->version)
			{
				$latest_version = $node->version;
				$package_url = $node->downloads->downloadurl;
				$name = $node->name;
				$type = $node->type;
			}
		}

		$cli->out(JText::sprintf('ADD_COMMAND_REQUEST_LATEST_VERSION', $latest_version));
		$cli->out(JText::sprintf('ADD_COMMAND_DOWNLOAD_FILE', $type, $name));
		$file = JFile::getName($package_url);

		$this->downloadPackageUrl($package_url, JPATH_TMP . '/' . $file);
		$cli->out(JText::sprintf('ADD_COMMAND_DOWNLOAD_COMPLETE', $name));
		$cli->out(JText::_('ADD_COMMAND_PREPARE_TO_INSTALL'));

		// Initialise current joomla paths
		$source_path = JApplicationCli::getInstance()->get('cwd');
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
		$logEntry = new JLogEntry(sprintf('downloading %s', $url), JLog::INFO, 'EXTENSION');
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

		$logEntry = new JLogEntry(sprintf('file have been created in %s', $target), JLog::INFO, 'EXTENSION');
		JLog::add($logEntry);

		// Restore error tracking to what it was before
		ini_set('track_errors', $track_errors);

		// Bump the max execution time because not using built in php zip libs are slow
		@set_time_limit(ini_get('max_execution_time'));

		// Return the name of the downloaded package
		return basename($target);
	}

	/**
	 * Find a extension in list
	 * 
	 * @param   String  $search  Extension name
	 * 
	 * @return  Booelan
	 * 
	 * @since   1.1
	 */
	private function findExtension($search)
	{
		$file_path = JPATH_CONFIG . '/extensions.json';
		$extensions_content = file_get_contents($file_path);
		$extensions = json_decode($extensions_content);

		foreach ($extensions as $extension)
		{
			if ($extension->shortname == $search)
			{
				$this->extension = $extension;
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if its a joomla-cms directory
	 * 
	 * @return  Boolean
	 * 
	 * @since   1.1
	 */
	private function checkDirectory()
	{
		$source_path = JApplicationCli::getInstance()->get('cwd');

		if (!is_file($source_path . DIRECTORY_SEPARATOR . 'configuration.php') || !is_dir($source_path . DIRECTORY_SEPARATOR . 'components') || !is_dir($source_path . DIRECTORY_SEPARATOR . 'administrator'))
		{
			return false;
		}

		return true;
	}
}
