<?php

namespace CCL\Content\Element\Basic\Form;

use CCL\Content\Element\Basic\Container;

/**
 * Represents a select element.
 *
 * @example // The following code snippet creates a select element which allows to select multiple options.
 *          // The option 2 is pre selected.
 * $s = new Select('myselect', 'myname', true);
 * s->addOption(1, 'Option 1', false);
 * s->addOption(2, 'Option 2', true);
 */
class Select extends Container
{
	private $optionCounter = 1;

	/**
	 * Constructor which sets the classes and attributes of the element.
	 *
	 * @param string  $id         The id of the element, must be not empty
	 * @param string  $name       The name of the select
	 * @param boolean $multiple   If it is allowed to select multiple elements
	 * @param array   $classes    The classes of the element
	 * @param array   $attributes Additional attributes for the element
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($id, $name, $multiple = false, array $classes = [], array $attributes = [])
	{
		$attributes['name'] = $name;

		if ($multiple) {
			$attributes['multiple'] = 'multiple';
		}
		parent::__construct($id, $classes, $attributes);
	}

	/**
	 * Adds an option with the text and value, if selected is true, it gets the selected property.
	 *
	 * @param string  $text
	 * @param string  $value
	 * @param boolean $selected
	 *
	 * @return \CCL\Content\Element\Basic\Element
	 */
	public function addOption($text, $value, $selected = false)
	{
		$attributes = [];

		if ($selected) {
			$attributes['selected'] = 'selected';
		}
		$option = $this->addChild(new Option($this->getId() . '-' . $this->optionCounter, $value, array(), $attributes));
		$option->setContent($text);

		return $option;
	}
}
