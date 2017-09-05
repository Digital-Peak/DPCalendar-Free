<?php

namespace CCL\Content\Element\Basic;

/**
 * Represents a list element.
 *
 * @example // The following code snippet creates an unordered list with one item.
 * $l = new ListContainer('mylist', ListContainer::UNORDERED);
 * $l->addListItem(new ListItem('myitem'));
 */
class ListContainer extends Container
{

	/**
	 * The ordered type.
	 *
	 * @var string
	 */
	const ORDERED = 'ordered';

	/**
	 * The unordered type.
	 *
	 * @var string
	 */
	const UNORDERED = 'unordered';

	/**
	 * The type.
	 *
	 * @var string
	 */
	private $type = self::UNORDERED;

	/**
	 * Constructor which sets the classes and attributes of the element.
	 * The type defines if the list is ordered or not.
	 *
	 * @param string $id         The id of the element, must be not empty
	 * @param string $type       The type of the list
	 * @param array  $classes    The classes of the element
	 * @param array  $attributes Additional attributes for the element
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($id, $type, array $classes = [], array $attributes = [])
	{
		parent::__construct($id, $classes, $attributes);

		if (!in_array($type, [self::UNORDERED, self::ORDERED])) {
			$type = self::UNORDERED;
		}
		$this->type = $type;
	}

	/**
	 * Returns the type of the list.
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Adds the given list item to the children and returns it for chaining.
	 *
	 * @param ListItem $listItem
	 *
	 * @return ListItem
	 */
	public function addListItem(ListItem $listItem)
	{
		return $this->addChild($listItem);
	}
}
