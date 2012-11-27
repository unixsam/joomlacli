<?php
/**
 * @package     Joomla.Cli
 * @subpackage  Cli
 * @copyright   Copyright 2012 joomlacli. All rights re-served.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// We are a valid Joomla entry point.
define('_JEXEC', 1);

// error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Setup the base path related constant.
define('JPATH_BASE', dirname(__DIR__));
define('JPATH_ETC', JPATH_BASE . '/etc/');
define('JPATH_LIB', JPATH_BASE . '/lib/');
define('JPATH_SRC', JPATH_BASE . '/src/');

// Specific paths
define('JPATH_LOG', JPATH_ETC . '/logs/');
define('JPATH_CONFIG', JPATH_ETC . '/config/');
define('JPATH_TMP', JPATH_ETC . '/tmp/');

Phar::loadPhar(realpath(JPATH_LIB . 'joomla-platform12.2.phar'));

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
require_once JPATH_SRC . '/import.php';

jimport('joomla.filesystem.path');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * Joomlacli is a command line shell and scripting interface for Joomla.
 *
 * @package  Joomlacli
 * @since    1.0
 */
final class Joomlacli extends JApplicationCli
{
	/**
	 * @var    JLanguage  The application language object.
	 * @since  1.0
	 */
	protected $language;

	/**
	 * Class constructor.
	 *
	 * @param   mixed  $input       An optional argument to provide dependency injection for the application's
	 *                              input object.  If the argument is a JInputCli object that object will become
	 *                              the application's input object, otherwise a default input object is created.
	 * @param   mixed  $config      An optional argument to provide dependency injection for the application's
	 *                              config object.  If the argument is a JRegistry object that object will become
	 *                              the application's config object, otherwise a default config object is created.
	 * @param   mixed  $dispatcher  An optional argument to provide dependency injection for the application's
	 *                              event dispatcher.  If the argument is a JEventDispatcher object that object will become
	 *                              the application's event dispatcher, if it is null then the default event dispatcher
	 *                              will be created based on the application's loadDispatcher() method.
	 *
	 * @see     loadDispatcher()
	 * @since   11.1
	 */
	public function __construct(JInputCli $input = null, JRegistry $config = null, JEventDispatcher $dispatcher = null)
	{
		parent::__construct($input, $config, $dispatcher);

		// Import execution plugin
		JPluginHelper::importPlugin('execution');

		// Set Language
		$this->language = JFactory::getLanguage();

		// Loading source language
		$this->language->load(null, JPATH_SRC);
	}

	/**
	 * Cli Application execution
	 *
	 * @return   void
	 *
	 * @since    1.0
	 */
	public function doExecute()
	{
		$this->main();
	}

	/**
	 * Main execution proccess
	 * 
	 * @return   void
	 * 
	 * @since    1.0
	 */
	public function main()
	{
		$date = date('Y-m-d');

		// Instance a logger
		JLog::addLogger(
			// Pass an array of configuration options
			array(
				// Set the name of the log file
				'text_file' => 'command.' . $date . '.php',
				// (optional) you can change the directory
				'text_file_path' => JPATH_LOG
			)
		);

		if (!empty($this->input->args))
		{
			$text = $_SERVER['argv'];
			array_shift($text);
			$text = trim(implode(' ', $text));

			$logEntry = new JLogEntry($text, JLog::INFO, 'COMMAND');
			JLog::add($logEntry);

			JPluginHelper::importPlugin('command');
			$this->triggerEvent('onExecuteCommand');
		} else {
			$this->out(JText::_('HELP_COMMAND'));
		}
	}
}

// Wrap the execution in a try statement to catch any exceptions thrown anywhere in the script.
try
{
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
