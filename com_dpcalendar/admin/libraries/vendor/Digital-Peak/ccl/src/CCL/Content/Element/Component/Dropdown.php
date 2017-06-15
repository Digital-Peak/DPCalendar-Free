<?php

namespace CCL\Content\Element\Component;

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Element;

/**
 * A dropdown representation.
 */
class Dropdown extends Container
{
	private $trigger = null;

	/**
	 * Returns the trigger element.
	 *
	 * @return Element
	 */
	public function getTriggerElement()
	{
		return $this->trigger;
	}

	/**
	 * Adds the given element as trigger in the dropdown.
	 *
	 * @param Element $trigger
	 *
	 * @return Element
	 */
	public function setTriggerElement(Element $trigger)
	{
		$this->trigger = $trigger;

		return $this->addChild($trigger, true);
	}

	/**
	 * Adds the given element as entry in the dropdown.
	 *
	 * @param Element $element
	 *
	 * @return Element
	 */
	public function addElement(Element $element)
	{
		return $this->addChild($element);
	}

	/**
	 * {@inheritdoc}
	 */
	public function clearChildren()
	{
		$this->trigger = null;

		return parent::clearChildren();
	}
}
