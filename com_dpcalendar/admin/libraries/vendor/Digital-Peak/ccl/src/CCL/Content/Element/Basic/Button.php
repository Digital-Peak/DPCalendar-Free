<?php

namespace CCL\Content\Element\Basic;

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Component\Icon;

/**
 * Represents a button element.
 *
 * @example // The following code snippet creates a button which has a text and ok icon.
 * $b = new Button('myb', 'Demo Button', new Icon('icon', Icon::OK));
 */
class Button extends Container
{
	/**
	 * Constructor which sets the classes and attributes of the element. Additionally you can define
	 * a text and Icon for the button.
	 * The id parameter must be set, otherwise an InvalidArgumentException is thrown.
	 *
	 * @param string $id         The id of the element, must be not empty
	 * @param string $text       Optional text of the button
	 * @param Icon   $icon       Optional icon of the button
	 * @param array  $classes    The classes of the element
	 * @param array  $attributes Additional attributes for the element
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($id, $text = '', Icon $icon = null, array $classes = [], array $attributes = [])
	{
		parent::__construct($id, $classes, $attributes);

		if ($icon) {
			$this->addChild($icon);
		}
		if ($text) {
			$this->addChild(new TextBlock('text'))->setContent($text);
		}

		// Set the type otherwise it will act as submit button
		$this->addAttribute('type', 'button');
	}
}
