<?php

namespace CCL\Content\Element\Component;

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Basic\ListContainer;
use CCL\Content\Element\Basic\ListItem;
use CCL\Content\Element\Component\Panel\Body;
use CCL\Content\Element\Component\Panel\Image;
use CCL\Content\Element\Component\Panel\Title;

/**
 * A Panel representation.
 */
class Panel extends Container
{
	/**
	 * Adds the given title as child and returns it for chaining.
	 *
	 * @param Title $title
	 *
	 * @return Title
	 */
	public function addTitle(Title $title)
	{
		return $this->addChild($title);
	}

	/**
	 * Adds the given image as child and returns it for chaining.
	 *
	 * @param Image $image
	 *
	 * @return Image
	 */
	public function addImage(Image $image)
	{
		return $this->addChild($image);
	}

	/**
	 * Adds the given body as child and returns it for chaining.
	 *
	 * @param Body $body
	 *
	 * @return Body
	 */
	public function addBody(Body $body)
	{
		return $this->addChild($body);
	}
}
