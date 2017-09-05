<?php

namespace CCL\Content\Element\Basic\Form;

use CCL\Content\Element\Basic\Element;

/**
 * Represents a select option element.
 *
 * @example // The following code snippet creates a select option element with a value.
 * $o = new Option('myselect', 'myvalue');
 */
class Option extends Element
{

	/**
	 * Constructor which sets the classes and attributes of the element.
	 *
	 * @param string $id         The id of the element, must be not empty
	 * @param string $value      The value of the option
	 * @param array  $classes    The classes of the element
	 * @param array  $attributes Additional attributes for the element
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($id, $value, array $classes = [], array $attributes = [])
	{
		$attributes['value'] = $value;
		parent::__construct($id, $classes, $attributes);
	}
}
