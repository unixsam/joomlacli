<?php
/**
 * Table Format for cli
 * 
 *  @package joomlacli
 */
class KTable
{
	protected $columns;

	public function addRows(array $rows)
	{
		foreach ($rows as $rowNumber => $row)
		{
			foreach ($row as $columnNumber => $column)
			{
				$this->columns[$columnNumber][$rowNumber] = $column;
			}
		}
	}

	public function setColumns(array $columns)
	{
		$this->columns = $columns;
	}

	public function render(array $columns = array())
	{
		if (!empty($columns))
		{
			$this->columns = $columns;
		}
		
		//search maxlenght word from each collumn and add padding to all words
		foreach ($this->columns as $key => $column)
		{	
			$lengths = array_map('strlen', $column);
			$maxLenght = max($lengths);
			foreach ($column as $index => $string)
			{
				$rows[$index][$key] = str_pad($string, $maxLenght, ' ');
			}
		}
		
		foreach ($rows as $row)
		{
			JApplicationCli::getInstance()->out(implode('   ', $row));
		}
	}
}