<?php
/**
 * Base command plugin
 * 
 * @package joomlacli
 * @since	0.1
 */
class KPluginCommand extends KPlugin
{
	final public function onExecuteCommand($functions, $arguments)
	{
		$command 	 = $functions[0];
		$method_name = 'command'.$command;
		
		if (method_exists($this, $method_name))
		{
			array_shift($functions);
			$this->$method_name($functions, $arguments);
			
			return true;
		}
		
		return false;
	}
}