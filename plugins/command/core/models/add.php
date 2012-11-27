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
