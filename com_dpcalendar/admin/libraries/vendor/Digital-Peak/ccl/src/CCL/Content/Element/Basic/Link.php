<?php

namespace CCL\Content\Element\Basic;

/**
 * A link representation.
 */
class Link extends Container
{

	/**
	 * Defines the internal attributes structure with the given parameters.
	 *
	 * @param string $id         The id of the element, must be not empty
	 * @param string $link       The link of the element
	 * @param string $target     The target of the element
	 * @param array  $classes    The classes of the element
	 * @param array  $attributes Additional attributes
	 *
	 * @throws \Exception
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
