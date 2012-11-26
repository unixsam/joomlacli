<?php
/**
 * @package     Joomla.Cli
 * @subpackage  Plugins
 * @copyright   Copyright 2012 joomlacli. All rights re-served.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Joomlacli is a command line shell and scripting interface for Joomla.
 *
 * @package  Joomlacli
 * @since    1.0
 **/
class PlgExecutionCli extends JPlugin
{
	/**
	 * Initialise proccess
	 * 
	 * @return void
	 * 
	 * @since  1.0
	 */
	public function onBeforeExecute()
	{
		$this->cli = JApplicationCli::getInstance();

		// Checking configuration
		$this->_checkConfiguration();
	}

	/**
	 * Check configuration file
	 * 
	 * @return void
	 * 
	 * @since  1.0
	 */
	private function _checkConfiguration()
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
			$file_path = JPATH_CONFIG . $configurationFile . '.php';
			if (!JFile::exists($file_path))
			{
				$registry = new JRegistry;
				foreach ($parameters as $parameter)
				{
					$this->cli->out(JText::_('CONFIG_' . $configurationFile . '_' . $parameter));
					$registry->set($parameter, $this->cli->in());
				}

				// Generate the configuration class string buffer.
				$buffer = $registry->toString('PHP', array('class' => 'JConfig' . $configurationFile, 'closingtag' => false));

				// Checking file
				if (!JFile::write($file_path, $buffer))
				{
					$this->cli->out(JText::sprintf('CONFIG_CANT_WRITE_FILE', $file_path));
				}
			}
		}
	}
}
