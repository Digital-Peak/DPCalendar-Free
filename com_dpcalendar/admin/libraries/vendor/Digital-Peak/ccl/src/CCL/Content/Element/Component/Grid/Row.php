<?php

namespace CCL\Content\Element\Component\Grid;

use CCL\Content\Element\Basic\Container;

/**
 * Represents a row in a grid.
 *
 * @example // The following code snippet creates a row element.
 * $r = new Row('myrow');
 */
class Row extends Container
{
	/**
	 * Adds the given column to the internal child array and returns it for chaining.
	 *
	 * @param Column $column
	 *
	 * @return Column
	 */
	public function addColumn(Column $column)
	{
		return $this->addChild($column);
	}
}
