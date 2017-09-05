<?php

namespace CCL\Content\Element\Basic;

/**
 * Represents a heading element.
 *
 * @example // The following code snippet creates a heading with the size 3.
 * $h = new Heading('myheading', 3);
 */
class Heading extends Container
{

	/**
	 * The heading size 1-6.
	 *
	 * @var integer
	 */
	private $size = 1;

	public function __construct($id, $size, array $classes = [], array $attributes = [])
	{
		if ($size < 1) {
			$size = 1;
		}
		if ($size > 6) {
			$size = 6;
		}

		$this->size = $size;

		parent::__construct($id, $classes, $attributes);
	}

	/**
	 * The size of the heading, must be between 1 and 6.
	 *
	 * @return number
	 */
	public function getSize()
	{
		return $this->size;
	}
}
