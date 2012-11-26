<?php
/**
 * Unit Test bootstrap file for the Joomlacli Joomla.
 *
 * @package    Joomlacli
 *
 * @copyright  Copyright (C) 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// Make sure that the Joomla Platform has been successfully loaded.
if (!class_exists('JLoader'))
{
	throw new RuntimeException('Joomla Platform not loaded.');
}

// Setup the autoloader for our overloaded Joomla Platform classes.
JLoader::register('JPluginHelper', __DIR__ . '/joomla/plugin/helper.php', true);
JLoader::registerPrefix('J', __DIR__ . '/joomla');