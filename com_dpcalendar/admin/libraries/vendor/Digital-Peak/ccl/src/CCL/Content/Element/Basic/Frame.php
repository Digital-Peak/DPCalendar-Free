<?php

namespace CCL\Content\Element\Basic;

/**
 * Represents a frame element.
 *
 * @example // The following code snippet creates a frame.
 * $f = new Frame('myframe', 'url/to/embed.html');
 */
class Frame extends Container
{
	/**
	 * Constructor which sets the classes and attributes of the element.
	 * The src attribute can be a url which should be embedded by this frame.
	 *
	 * @param string $id         The id of the element, must be not empty
	 * @param string $src        The src url to embed
	 * @param array  $classes    The classes of the element
	 * @param array  $attributes Additional attributes for the element
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($id, $src, array $classes = [], array $attributes = [])
	{
		$attributes['src'] = $src;

		parent::__construct($id, $classes, $attributes);
	}
}
