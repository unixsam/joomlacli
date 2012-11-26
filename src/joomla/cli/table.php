<?php
/**
 * @package     Joomla.Cli
 * @subpackage  Table
 * @copyright   Copyright 2012 joomlacli. All rights re-served.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Table class
 *
 * @package     Joomla.Platform
 * @subpackage  cli
 * 
 * @since       1.6
 */
class JCliTable
{
	/**
     * Human readable name
     *
     * @var    array
     * @since  1.6
     */
	protected $headers = array();

	/**
     * Human readable name
     *
     * @var    array
     * @since  1.6
     */
	protected $rows = array();

	/**
     * Method to get the name of the class.
     *
     * @param   array  $headers  Optionally return in upper/lower case.
     * @param   array  $rows     Optionally return in upper/lower case.
     *
     * @since   1.6
     */
	public function __construct(array $headers = null, array $rows = null)
	{
		if (!empty($headers))
		{
			// If all the rows is given in $headers we use the keys from the
			// first row for the header values
			if (empty($rows))
			{
				$rows = $headers;
				$keys = array_keys(array_shift($headers));
				$headers = array();

				foreach ($keys as $header)
				{
					$headers[$header] = $header;
				}
			}

			$this->setHeaders($headers);
			$this->setRows($rows);
		}
	}

	/**
	 * Sort the table by a column. Must be called before 'display'
	 * 
	 * @param   int  $column  The index of the column to sort by.
	 * 
	 * @return  void
	 * 
	 * @since   1.6
	 */
	public function sort($column)
	{
		if (!isset($this->headers[$column]))
		{
			trigger_error('No column with index ' . $column, E_USER_NOTICE);
			return;
		}
	}

	/**
	 * Set the headers of the table.
	 * 
	 * @param   int  $headers  An array of strings containing column header names.
	 * 
	 * @return  void
	 * 
	 * @since   1.6
	 */
	public function setHeaders(array $headers)
	{
		$this->headers = $this->checkRow($headers);
	}

	/**
	 * Add a row to the table.
	 * 
	 * @param   int  $row  The row data.
	 * 
	 * @return  void
	 * 
	 * @since   1.6
	 */
	public function addRow(array $row)
	{
		$this->rows[] = $row;
	}

	/**
	 * Clears all previous rows and adds the given rows.
	 * 
	 * @param   int  $rows  A 2-dimensional array of row data.
	 * 
	 * @return  void
	 * 
	 * @since   1.6
	 */
	public function setRows(array $rows)
	{
		$this->rows = array();
		foreach ($rows as $row)
		{
			$this->addRow($row);
		}
	}

	/**
	 * Display data table
	 * 
	 * @return  void
	 * 
	 * @since   1.6
	 */
	public function display()
	{
		$rows = array_merge($this->headers, $this->rows);

		// Convert rows to columns
		foreach ($rows as $rowNumber => $row)
		{
			foreach ($row as $columnNumber => $column)
			{
				$columns[$columnNumber][$rowNumber] = $column;
			}
		}

		// Search maxlenght word from each collumn and add padding to all words
		foreach ($columns as $key => $column)
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
