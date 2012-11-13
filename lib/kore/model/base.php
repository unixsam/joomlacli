<?php
/**
 * Base Model for cli
 * 
 * @package joomlacli
 * @since	0.1
 */
class KModelBase extends JModelBase
{
	/**
	 * Command list
	 * 
	 * @var array
	 */
	protected $command;

	/**
	 * Arguments list
	 * 
	 * @var array
	 */
	protected $arguments;

	/**
	 * Path to current folder
	 * 
	 * @var string
	 */
	protected $_source_path;

	public function __construct(JRegistry $config = null)
	{
		// Setup the model.
		$this->state = isset($config) ? $config : $this->loadState();
		
		$this->command = $this->state->get('command');
		$this->arguments = $this->state->get('arguments');
		
		if (!empty($this->arguments['d']))
		{
			$this->_source_path = $this->arguments['d'];
		} else {
			//get current directory path
			$this->_source_path = getcwd();
		}
	}
}