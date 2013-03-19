<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Plugin helper class
 *
 * @package     Joomla.Platform
 * @subpackage  Plugin
 * @since       11.1
 */
abstract class JCliPluginHelper
{
	/**
	 * A persistent cache of the loaded plugins.
	 *
	 * @var    array
	 * @since  11.3
	 */
	public static $plugins = null;

	/**
	 * Get the plugin data of a specific type if no specific plugin is specified
	 * otherwise only the specific plugin data is returned.
	 *
	 * @param   string  $type    The plugin type, relates to the sub-directory in the plugins directory.
	 * @param   string  $plugin  The plugin name.
	 *
	 * @return  mixed  An array of plugin data objects, or a plugin data object.
	 *
	 * @since   11.1
	 */
	public static function getPlugin($type, $plugin = null)
	{
		$result = array();
		$plugins = self::_load();

		// Find the correct plugin(s) to return.
		if (!$plugin)
		{
			foreach ($plugins as $p)
			{
				// Is this the right plugin?
				if ($p->type == $type)
				{
					$result[] = $p;
				}
			}
		}
		else
		{
			foreach ($plugins as $p)
			{
				// Is this plugin in the right group?
				if ($p->type == $type && $p->name == $plugin)
				{
					$result = $p;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Checks if a plugin is enabled.
	 *
	 * @param   string  $type    The plugin type, relates to the sub-directory in the plugins directory.
	 * @param   string  $plugin  The plugin name.
	 *
	 * @return  boolean
	 *
	 * @since   11.1
	 */
	public static function isEnabled($type, $plugin = null)
	{
		$result = self::getPlugin($type, $plugin);
		return (!empty($result));
	}

	/**
	 * Loads all the plugin files for a particular type if no specific plugin is specified
	 * otherwise only the specific plugin is loaded.
	 *
	 * @param   string            $type        The plugin type, relates to the sub-directory in the plugins directory.
	 * @param   string            $plugin      The plugin name.
	 * @param   boolean           $autocreate  Autocreate the plugin.
	 * @param   JEventDispatcher  $dispatcher  Optionally allows the plugin to use a different dispatcher.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   11.1
	 */
	public static function importPlugin($type, $plugin = null, $autocreate = true, JEventDispatcher $dispatcher = null)
	{
		static $loaded = array();

		// Check for the default args, if so we can optimise cheaply
		$defaults = false;
		if (is_null($plugin) && $autocreate == true && is_null($dispatcher))
		{
			$defaults = true;
		}

		if (!isset($loaded[$type]) || !$defaults)
		{
			$results = null;

			// Load the plugins from the database.
			$plugins = self::_load();

			// Get the specified plugin(s).
			for ($i = 0, $t = count($plugins); $i < $t; $i++)
			{
				if ($plugins[$i]->type == $type && ($plugin === null || $plugins[$i]->name == $plugin))
				{
					self::_import($plugins[$i], $autocreate, $dispatcher);
					$results = true;
				}
			}

			// Bail out early if we're not using default args
			if (!$defaults)
			{
				return $results;
			}
			$loaded[$type] = $results;
		}

		return $loaded[$type];
	}

	/**
	 * Loads the plugin file.
	 *
	 * @param   JPlugin           $plugin      The plugin.
	 * @param   boolean           $autocreate  True to autocreate.
	 * @param   JEventDispatcher  $dispatcher  Optionally allows the plugin to use a different dispatcher.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   11.1
	 */
	protected static function _import($plugin, $autocreate = true, JEventDispatcher $dispatcher = null)
	{
		static $paths = array();

		$plugin->type = preg_replace('/[^A-Z0-9_\.-]/i', '', $plugin->type);
		$plugin->name = preg_replace('/[^A-Z0-9_\.-]/i', '', $plugin->name);

		$path = JPATH_BASE . '/plugins/' . $plugin->type . '/' . $plugin->name . '/' . $plugin->name . '.php';

		if (!isset($paths[$path]))
		{
			if (file_exists($path))
			{
				if (!isset($paths[$path]))
				{
					require_once $path;
				}
				$paths[$path] = true;

				if ($autocreate)
				{
					// Makes sure we have an event dispatcher
					if (!is_object($dispatcher))
					{
						$dispatcher = JEventDispatcher::getInstance();
					}

					$className = 'plg' . $plugin->type . $plugin->name;
					if (class_exists($className))
					{
						// Load the plugin from the database.
						if (!isset($plugin->params))
						{
							// Seems like this could just go bye bye completely
							$plugin = self::getPlugin($plugin->type, $plugin->name);
						}

						// Instantiate and register the plugin.
						new $className($dispatcher, (array) ($plugin));
						// Loading language from plugin
						JFactory::getLanguage()->load('plg_'.$plugin->type.'_'.$plugin->name, JPATH_SRC);
					}
				}
			}
			else
			{
				$paths[$path] = false;
			}
		}
	}

	/**
	 * Loads plugins from folder
	 *
	 * @return  array  An array of published plugins
	 *
	 * @since   11.1
	 */
	protected static function _load()
	{
		if (self::$plugins !== null)
		{
			return self::$plugins;
		}

		$pluginGroups = JFolder::folders(JPATH_BASE . '/plugins/');
		foreach ($pluginGroups as $pluginGroup)
		{
			$plugin_group_path = JPATH_BASE . '/plugins/' . $pluginGroup . '/';
			$plugins = JFolder::folders($plugin_group_path);
			foreach ($plugins as $plugin)
			{
				$pluginObject = new stdclass;
				$pluginObject->type = $pluginGroup;
				$pluginObject->name = $plugin;
				$pluginObject->params = new JRegistry;

				self::$plugins[] = $pluginObject;
			}
		}

		return self::$plugins;
	}
}