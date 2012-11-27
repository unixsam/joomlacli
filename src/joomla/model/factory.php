<?php
/**
 * @package     Joomla.Cli
 * @subpackage  Models
 * @copyright   Copyright 2012 joomlacli. All rights re-served.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Model Factory
 * 
 * @package  Joomlacli
 * @since    1.0
 */
abstract class JModelFactory
{
	/**
	 * Array list with instances of models
	 * 
	 * @var array
	 */
	static private $_instances;

	/**
	 * Return a instance of model
	 * 
	 * @param   string  $identifier  String identifier.
	 * @param   mixed   $config      An optional argument to provide dependency injection for the application's
	 *                               config object.  If the argument is a JRegistry object that object will become
	 *                               the application's config object, otherwise a default config object is created.
	 * 
	 * @return  KModelBase
	 * 
	 * @throws RuntimeException
	 * 
	 * @since   1.0
	 */
	public static function get($identifier, JRegistry $config = null)
	{
		$parts = explode('.', $identifier);
		$class_name = implode('', $parts);

		$key = strtolower($class_name);

		$parts[2] = JStringInflector::getInstance()->toPlural($parts[2]);
		$path_identifier = implode('.', $parts);

		$base_path = is_object($config) ? $config->get('base_path', JPATH_BASE . '/plugins/') : JPATH_BASE . '/plugins/';

		JLoader::import($path_identifier, $base_path);

		if (!class_exists($class_name))
		{
			throw new RuntimeException(JText::sprintf('ERROR_MODEL_CANT_FIND_CLASS', end($parts)));
		}

		self::$_instances[$key] = new $class_name($config);

		return self::$_instances[$key];
	}
}
