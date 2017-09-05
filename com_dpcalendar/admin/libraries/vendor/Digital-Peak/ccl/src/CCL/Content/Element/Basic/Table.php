<?php

namespace CCL\Content\Element\Basic;

use CCL\Content\Element\Basic\Table\Row;
use CCL\Content\Element\Basic\Table\Head;
use CCL\Content\Element\Basic\Table\Body;
use CCL\Content\Element\Basic\Table\Footer;
use CCL\Content\Element\Basic\Table\HeadCell;

/**
 * Represents a table.
 *
 * @example // The following code snippet creates a table and a row with a cell.
 * $t = new Table('mytable', ['Col A', 'Col B']);
 * $r = $t->addRow(new Table\Row('myrow'));
 * $c = $r->addCell(new Table\Cell('mycell'));
 * $c->setContent('The content of the cell in the table');
 */
class Table extends Container
{
	private $head = null;
	private $body = null;
	private $footer = null;

	/**
	 * Constructor which sets the classes and attributes of the element.
	 * The columns is an array of strings which do represent the columns of the table.
	 *
	 * @param string   $id         The id of the element, must be not empty
	 * @param string[] $columns    The columns
	 * @param array    $classes    The classes of the element
	 * @param array    $attributes Additional attributes for the element
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($id, array $columns, array $classes = [], array $attributes = [])
	{
		parent::__construct($id, $classes, $attributes);

		$this->head   = $this->addChild(new Head('head'));
		$this->body   = $this->addChild(new Body('body'));
		$this->footer = $this->addChild(new Footer('footer'));

		$row = $this->head->addChild(new Row('row'));

		foreach ($columns as $index => $column) {
			$row->addChild(new HeadCell('cell-' . $index))->setContent($column);
		}
	}

	/**
	 * Adds the given row to the internal body elements children and returns it for chaining.
	 *
	 * @param Row $row
	 *
	 * @return Row
	 */
	public function addRow(Row $row)
	{
		return $this->body->addChild($row);
	}

	/**
	 * Adds the given row to the internal footer elements children and returns it for chaining.
	 *
	 * @param Row $row
	 *
	 * @return Row
	 */
	public function addFooterRow(Row $row)
	{
		return $this->footer->addChild($row);
	}
}
