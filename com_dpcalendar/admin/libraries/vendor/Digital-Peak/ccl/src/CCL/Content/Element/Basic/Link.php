<?php

namespace CCL\Content\Element\Basic;

/**
 * Represents a link element.
 *
 * @example // The following code snippet creates a link which should open in a new window.
 * $l = new Link('mylink', 'http://www.example.com', '_blank');
 */
class Link extends Container
{
	/**
	 * Constructor which sets the classes and attributes of the element.
	 * The link attribute can be a url where the link points to.
	 * The target attribute defines where the link should be opened.
	 *
	 * @param string $id         The id of the element, must be not empty
	 * @param string $link       The link of the element
	 * @param string $target     The target of the element
	 * @param array  $classes    The classes of the element
	 * @param array  $attributes Additional attributes for the element
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($id, $link, $target = null, array $classes = [], array $attributes = [])
	{
		parent::__construct($id, $classes, $attributes);

		$this->addAttribute('href', $link);

		if ($target) {
			$this->addAttribute('target', $target);
		}
	}
}
