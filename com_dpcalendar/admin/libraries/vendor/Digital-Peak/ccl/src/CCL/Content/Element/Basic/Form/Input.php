<?php

namespace CCL\Content\Element\Basic\Form;

use CCL\Content\Element\Basic\Element;

/**
 * Represents a input element.
 *
 * @example // The following code snippet creates a text area input.
 * $i = new Input('myinput', 'textarea', 'nameofinput', 'Default Text');
 */
class Input extends Element
{
	/**
	 * Constructor which sets the classes and attributes of the element.
	 * The type defines what this input is and the name must be unique in the form.
	 * The value defines what this input contains for data.
	 *
	 * @param string $id         The id of the element, must be not empty
	 * @param string $type       The type of the input
	 * @param string $name       The name of the input
	 * @param string $value      The value of the input
	 * @param array  $classes    The classes of the element
	 * @param array  $attributes Additional attributes for the element
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($id, $type, $name, $value = '', array $classes = [], array $attributes = [])
	{
		$attributes['type']  = $type;
		$attributes['name']  = $name;
		$attributes['value'] = $value;

		parent::__construct($id, $classes, $attributes);
	}
}
