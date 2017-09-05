<?php

namespace CCL\Content\Element\Basic;

/**
 * Represents a meta element.
 *
 * @example // The following code snippet creates a meta item with the url property.
 * $m = new Meta('mymeta', 'url', 'http://www.example.com/item/74);
 */
class Meta extends Element
{

	/**
	 * Constructor which sets the classes and attributes of the element.
	 *
	 * @param string $id         The id of the element, must be not empty
	 * @param string $property   The property name
	 * @param string $content    The content of the element
	 * @param array  $classes    The classes of the element
	 * @param array  $attributes Additional attributes for the element
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($id, $property, $content, array $classes = [], array $attributes = [])
	{
		$attributes['itemprop'] = $property;
		$attributes['content']  = $content;

		parent::__construct($id, $classes, $attributes);
	}
}
