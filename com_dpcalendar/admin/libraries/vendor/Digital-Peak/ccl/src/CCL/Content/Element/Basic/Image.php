<?php

namespace CCL\Content\Element\Basic;

/**
 * Represents an image element.
 *
 * @example // The following code snippet creates an image with an alternative text.
 * $i = new Image('myimage', 'url/to/image.jpg', 'Alte text');
 */
class Image extends Element
{
	/**
	 * Constructor which sets the classes and attributes of the element.
	 * The src attribute can be a url which represents this image.
	 *
	 * @param string $id         The id of the element, must be not empty
	 * @param string $src        The src url of the image
	 * @param string $alt        The alternative text of the image
	 * @param array  $classes    The classes of the element
	 * @param array  $attributes Additional attributes for the element
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($id, $src, $alt = '', array $classes = [], array $attributes = [])
	{
		$attributes['src'] = $src;
		$attributes['alt'] = $alt;

		parent::__construct($id, $classes, $attributes);
	}
}
