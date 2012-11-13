<?php
/**
 * Base Plugin class for cli
 * 
 * @package Joomlacli
 * @since 	0.1
 */
class KPlugin extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 *
	 * @since   11.1
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		
		$this->loadLanguage();
	}

	/**
	 * Loads the plugin language file
	 *
	 * @param   string  $extension  The extension for which a language file should be loaded
	 * @param   string  $basePath   The basepath to use
	 *
	 * @return  boolean  True, if the file has successfully loaded.
	 *
	 * @since   11.1
	 */
	public function loadLanguage($extension = '', $basePath = JPATH_BASE)
	{
		return parent::loadLanguage($extension, $basePath);
	}
}