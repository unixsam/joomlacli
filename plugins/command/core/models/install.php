<?php
/**
 * Model for installation proccess
 * 
 * @package joomlacli.plugins
 * @since	0.1
 */
class CommandCoreModelInstall extends KModelBase
{
	protected $new_install = false;
	
	public function prepareDirectory()
	{
		if (empty($this->arguments['d']))
		{
			$this->_source_path .= DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $this->command);
			
			//set default value for argument
			$this->arguments['d'] = $this->_source_path;
		} else {
			//check if is a valid directory
			if (strpos($this->arguments['d'],DIRECTORY_SEPARATOR) === false)
			{
				throw new RuntimeException(JText::sprintf('CORE_ERROR_VALID_DIRECTORY', $this->arguments['d']));
			}
		}
		
		return true;
	}

	private function downloadPackageUrl($url, $target = false)
	{
		$logEntry = new JLogEntry(sprintf('downloading %s', $url), JLog::INFO, 'GITHUB');
		JLog::add($logEntry);
		
		// Capture PHP errors
		$track_errors = ini_get('track_errors');
		ini_set('track_errors', true);
		$http = JHttpFactory::getHttp();
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

	public function preparePackage()
	{
		if (empty($this->file))
		{
			throw new RuntimeException(JText::_('PLG_CORE_INSTALL_INVALID_SOURCE_URL'));
		}
		
		JApplicationCli::getInstance()->out(JText::_('PLG_CORE_INSTALL_STARTING_INSTALLATION'));
		
		$url = $this->arguments['url'];
		$file = $this->file;
		
		if (!JFile::exists(JPATH_TMP.'/'. $file))
		{
			//download git package
			$package = $this->downloadPackageUrl($url, $file);
		}
	}
	
	public function scafolding()
	{
		// Unpack the downloaded package file
		try
		{
			$logEntry = new JLogEntry(sprintf('Prepare directory %s', $this->_source_path), JLog::INFO, 'INSTALLATION');
			JLog::add($logEntry);
			
			if (!JFolder::exists($this->_source_path))
			{
				JApplicationCli::getInstance()->out(JText::sprintf('PLG_CORE_INSTALL_PREPARING_FOLDER', $this->_source_path));
				
				if (!JFolder::create($this->_source_path))
				{
					throw new RuntimeException(JText::sprintf('PLG_CORE_INSTALL_ERROR_CANT_CREATE_FOLDER',$this->_source_path));
				}
				
				$logEntry = new JLogEntry(sprintf('Extracting %s in %s', JPATH_TMP . '/' .$this->file, $this->_source_path), JLog::INFO, 'INSTALLATION');
				JLog::add($logEntry);
				JArchive::extract(JPATH_TMP . '/' .$this->file, $this->_source_path);
			}
		}
		catch (Exception $e)
		{
			throw new RuntimeException(JText::_('PLG_CORE_INSTALL_EXTRACT_FILE_ERROR'));
		}
		
		KPluginHelper::importPlugin('installer');
		JApplicationCli::getInstance()->triggerEvent('onScafolding', array($this->arguments));
	}

	public function prepareVersion()
	{
		// Add the logger.
		JLog::addLogger(
		     // Pass an array of configuration options
		    array(
		            // Set the name of the log file
		            'text_file' => 'command.'.$date.'.php',
		            // (optional) you can change the directory
		            'text_file_path' => __DIR__.'/var/logs/'
		     ),
		     JLog::INFO,
		     'Command'
		);
		if (!isset($this->arguments['git_owner']))
		{
			$this->arguments['git_owner'] = 'joomla';
		}

		if (!isset($this->arguments['git_repo']))
		{
			$this->arguments['git_repo'] = 'joomla-cms';
		}
		
		KPluginHelper::importPlugin('installer');
		$versionsInfo = JApplicationCli::getInstance()->triggerEvent('onPrepareVersion', array($this->arguments));
		
		//set filename and version
		if (!empty($versionsInfo))
		{
			foreach ($versionsInfo as $versionInfo)
			{
				if (!empty($versionInfo['file']) && !empty($versionInfo['version']) && !empty($versionInfo['url']))
				{
					$this->file = $versionInfo['file'];
					$this->arguments['v'] = $versionInfo['version'];
					$this->arguments['url'] = $versionInfo['url'];
					return;
				}
			}
		}
	}
}