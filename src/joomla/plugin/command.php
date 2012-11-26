<?php
/**
 * @package     Joomla.Cli
 * @subpackage  Table
 * @copyright   Copyright 2012 joomlacli. All rights re-served.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Command Plugin Class
 *
 * @package     Joomla.Platform
 * @subpackage  cli
 * 
 * @since       1.0
 */
class JPluginCommand extends JPlugin
{
	/**
	 * Default command execution
	 * 
	 * @return  void
	 * 
	 * @since   1.0
	 **/
	final public function onExecuteCommand()
	{
		$input		 = JApplicationCli::getInstance()->input;

		$command 	 = $input->args[0];
		$method_name = 'cmd_' . $command;

		if (method_exists($this, $method_name))
		{
			array_shift($input->args);
			$this->$method_name();

			return true;
		}
		return false;
	}
}
