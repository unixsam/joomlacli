<?php
// We are a valid Joomla entry point.
define('_JEXEC', 1);

error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Setup the base path related constant.
define('JPATH_BASE', __DIR__);
define('JPATH_VAR', JPATH_BASE.'/var/');
define('JPATH_CONFIG', JPATH_VAR.'/config/');
define('JPATH_TMP', JPATH_VAR.'/tmp/');
define('JPATH_CERTIFICATE', JPATH_VAR.'/transport');

Phar::loadPhar(realpath(JPATH_BASE . '/vendor/joomla-platform12.2.phar'));

// Define the path for the Joomla Platform.
if (!defined('JPATH_PLATFORM'))
{
	$platform = getenv('JPLATFORM_HOME');
	if ($platform)
	{
		define('JPATH_PLATFORM', realpath($platform));
	}
	else
	{
		define('JPATH_PLATFORM', 'phar://joomla-platform12.2.phar/libraries');
	}
}

// Import the platform(s).
require_once JPATH_PLATFORM . '/import.php';
require_once JPATH_PLATFORM . '/import.legacy.php';

jimport('joomla.filesystem.path');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
JLoader::registerPrefix('K', __DIR__.'/lib/kore/');

/**
 * Joomlacli is a command line shell and scripting interface for Joomla.
 *
 * @package  Joomlacli
 * @since    0.1
 */
final class Joomlacli extends JApplicationCli
{
	/**
	 * Application execution
	 * 
	 * @since 0.1
	 */
	public function execute()
	{
		$date = JFactory::getDate()->format('Y-m-d');
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
		
		$this->checkConfiguration();
		$this->main();
	}

	/**
	 * Check if exists configuration file
	 * 
	 * @since 0.1
	 */
	private function checkConfiguration()
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
			$file_path = JPATH_CONFIG.$configurationFile.'.php';
			if (!JFile::exists($file_path))
			{
				$registry = new JRegistry();
				foreach ($parameters as $parameter)
				{
					$this->out(JText::_('CONFIG_'.$configurationFile.'_'.$parameter));
					$registry->set($parameter , $this->in());
				}
				
				// Generate the configuration class string buffer.
				$buffer = $registry->toString('PHP', array('class' => 'JConfig'.$configurationFile, 'closingtag' => false));
				
				//write configuration file
				if (!JFile::write($file_path, $buffer))
				{
					$this->out(JText::sprintf('CONFIG_CANT_WRITE_FILE', $file_path));
				}
			}
		}
	}

	/**
	 * Cli Applicatoin logic
	 * 
	 * @since 0.1
	 */
	private function main()
	{
		$this->input 	= new KInputCLI;
		$functions		= $this->input->args;
		$arguments		= $this->input->data;
		
		if (!empty($functions))
		{
			$text = '';
			if (!empty($functions))
			{
				$text .= ' '.implode(' ',$functions);
			}
			if (!empty($arguments))
			{
				$text .= ' '.implode(' ',$arguments);
			}
			$text = trim($text);
			
			$logEntry = new JLogEntry($text, JLog::INFO, 'COMMAND');
			JLog::add($logEntry);
			KPluginHelper::importPlugin('command');
			$this->triggerEvent('onExecuteCommand', array($functions, $arguments));
		} else {
			$this->out(JText::_('HELP_COMMAND'));
		}
	}
}

// Wrap the execution in a try statement to catch any exceptions thrown anywhere in the script.
try {
	// Instantiate the application object, passing the class name to JApplicationCli::getInstance
	// and use chaining to execute the application
	JApplicationCli::getInstance('Joomlacli')->execute();
}
catch (Exception $e)
{
	$logEntry = new JLogEntry($e->getMessage(), JLog::INFO, 'ERROR');
	JLog::add($logEntry);
	// An exception has been caught, just echo the message.
	fwrite(STDOUT, $e->getMessage() . "\n");
	exit($e->getCode());
}