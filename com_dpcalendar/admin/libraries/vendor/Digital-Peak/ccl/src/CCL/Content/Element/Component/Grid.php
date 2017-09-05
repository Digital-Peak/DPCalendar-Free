<?php

namespace CCL\Content\Element\Component;

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Component\Grid\Row;

/**
 * Represents a grid.
 *
 * @example // The following code snippet creates a grid with a row and different columns.
 * $t = new Grid('mygrid');
 * $r = $t->addRow(new Grid\Row('myrow'));
 * $c = $r->addColumn(new Grid\Column('mycol'));
 * $c->setContent('The content of the column in the grid row');
 */
class Grid extends Container
{

	/**
	 * Adds the given row to the internal child array and returns it for chaining.
	 *
	 * @param Row $row
	 *
	 * @return Row
	 */
	public function addRow(Row $row)
	{
		return $this->addChild($row);
	}
}
