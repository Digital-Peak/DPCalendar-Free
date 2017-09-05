<?php

namespace CCL\Content\Element\Basic\Form;

use CCL\Content\Element\Basic\Container;

/**
 * Represents a label element.
 *
 * @example // The following code snippet creates a text area input.
 * $i = new Label('myinput', 'idofinput');
 */
class Label extends Container
{
	/**
	 * Constructor which sets the classes and attributes of the element.
	 *
	 * @param string $id         The id of the element, must be not empty
	 * @param string $forId      The id the label belongs to
	 * @param array  $classes    The classes of the element
	 * @param array  $attributes Additional attributes for the element
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($id, $forId, array $classes = [], array $attributes = [])
	{
		$attributes['for'] = $forId;
		parent::__construct($id, $classes, $attributes);
	}
}
