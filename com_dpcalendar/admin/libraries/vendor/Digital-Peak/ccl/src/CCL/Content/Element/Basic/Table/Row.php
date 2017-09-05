<?php

namespace CCL\Content\Element\Basic\Table;

use CCL\Content\Element\Basic\Container;

/**
 * Represents a table head row element.
 *
 * @example // The following code snippet creates a table head row element.
 * $r = new Row('myrow');
 */
class Row extends Container
{

	/**
	 * Adds the given cell to the internal child collection.
	 *
	 * @param Cell $cell
	 *
	 * @return Cell
	 */
	public function addCell(Cell $cell)
	{
		return $this->addChild($cell);
	}
}
