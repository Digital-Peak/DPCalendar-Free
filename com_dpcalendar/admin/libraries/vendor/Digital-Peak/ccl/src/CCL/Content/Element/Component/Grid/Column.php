<?php

namespace CCL\Content\Element\Component\Grid;

use CCL\Content\Element\Basic\Container;

/**
 * Represents a column in a grid.
 *
 * @example // The following code snippet creates a column element which takes 20% of the width.
 * $c = new Column('mycolumn', 20);
 */
class Column extends Container
{

	/**
	 * The width of the column as percentage.
	 *
	 * @var integer
	 */
	private $width = 0;

	/**
	 * Constructor which sets the classes and attributes of the element.
	 * The width parameter defines the width in the grid as percentage.
	 *
	 * @param string  $id         The id of the element, must be not empty
	 * @param integer $width      The width in percentage
	 * @param array   $classes    The classes of the element
	 * @param array   $attributes Additional attributes for the element
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($id, $width, array $classes = [], array $attributes = [])
	{
		parent::__construct($id, $classes, $attributes);

		$this->setWidth($width);
	}

	/**
	 * Returns the width of a column in percentage.
	 *
	 * @return number
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * Sets the width of a column in percentage. It must be a value between 1 and 100.
	 *
	 * @return number
	 */
	public function setWidth($width)
	{
		if ($width > 100) {
			$width = 100;
		}

		if ($width < 1) {
			$width = 1;
		}

		$this->width = $width;
	}
}
