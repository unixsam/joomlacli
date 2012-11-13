<?php
/**
 * Interface for installer command plugins
 * 
 * @package joomlacli
 * @since	0.1
 */
interface KPluginInterfaceInstaller
{
	/**
	 * Event for custom script after extraction
	 * 
	 * @param array $arguments
	 */
	public function onScafolding($arguments);

	/**
	 * Method to return filename and version from url install
	 * 
	 * @param array $arguments
	 */
	public function onPrepareVersion($arguments);

	/**
	 * Method to list versions
	 * 
	 * @param array $functions
	 * @param array $arguments
	 */
	public function onListVersions($functions, $arguments);
}