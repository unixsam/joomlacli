<?php
/**
 * Factory for model
 * 
 * @package joomlacli
 * @since	0.1
 */
abstract class KModelFactory
{
	/**
	 * Array list with instances of models
	 * 
	 * @var array
	 */
	static private $instances;

	/**
	 * Return a instance of model
	 * 
	 * @param string $identifier
	 * @param JRegistry $config
	 * @throws RuntimeException
	 */
	public static function get($identifier, JRegistry $config = null)
	{
		$parts = explode('.', $identifier);
		$class_name = implode('', $parts);
		
		$key = strtolower($class_name);
		
		$parts[2] = JStringInflector::getInstance()->toPlural($parts[2]);
		$path_identifier = implode('.', $parts);
		
		$base_path = is_object($config) ? $config->get('base_path', JPATH_BASE.'/plugins/') : JPATH_BASE.'/plugins/';
		
		JLoader::import($path_identifier, $base_path);
		
		if (!class_exists($class_name))
		{
			throw new RuntimeException(JText::_('JERROR_MODEL_CANT_FIND_CLASS'));
		}
		
		self::$instances[$key] = new $class_name($config);
		
		return self::$instances[$key];
	}
}