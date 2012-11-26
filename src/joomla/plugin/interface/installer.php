<?php
/**
 * @package     Joomlacli.plugins
 * @subpackage  Core
 * @copyright   Copyright 2012 joomlacli. All rights re-served.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Interface for installer command plugins
 * 
 * @package  Joomlacli
 * 
 * @since    1.1
 */
interface JPluginInterfaceInstaller
{
	/**
	 * Event for custom script after extraction
	 * 
	 * @since  1.1
	 * 
	 * @return void
	 */
	public function onScafolding();

	/**
	 * Method to return filename and version from url install
	 * 
	 * @since  1.1
	 * 
	 * @return void
	 */
	public function onPrepareVersion();

	/**
	 * Method to list versions
	 * 
	 * @since  1.1
	 * 
	 * @return void
	 */
	public function onListVersions();
}
