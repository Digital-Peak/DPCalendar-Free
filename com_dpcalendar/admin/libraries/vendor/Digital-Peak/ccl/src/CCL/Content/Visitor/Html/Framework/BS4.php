<?php

namespace CCL\Content\Visitor\Html\Framework;

use CCL\Content\Element\Basic\Element;
use CCL\Content\Element\Component\TabContainer;

/**
 * The Bootstrap 4 framework visitor.
 */
class BS4 extends BS3
{
	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitLink()
	 */
	public function visitLink(\CCL\Content\Element\Basic\Link $link)
	{
		if ($this->searchForClass(TabContainer::class, $link)) {
			$link->addClass('nav-link', true);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitListItem()
	 */
	public function visitListItem(\CCL\Content\Element\Basic\ListItem $listItem)
	{
		if ($this->searchForClass(TabContainer::class, $listItem)) {
			$listItem->addClass('nav-item', true);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitPanel()
	 */
	public function visitPanel(\CCL\Content\Element\Component\Panel $panel)
	{
		$panel->addClass('card', true);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitPanelBody()
	 */
	public function visitPanelBody(\CCL\Content\Element\Component\Panel\Body $panelBody)
	{
		$panelBody->addClass('card-text', true);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitPanelImage()
	 */
	public function visitPanelImage(\CCL\Content\Element\Component\Panel\Image $panelImage)
	{
		$panelImage->addClass('card-img-top', true);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitPanelTitle()
	 */
	public function visitPanelTitle(\CCL\Content\Element\Component\Panel\Title $panelTitle)
	{
		$panelTitle->addClass('card-title', true);
	}

	private function searchForClass($class, Element $element)
	{
		if (get_class($element) == $class) {
			return $element;
		}

		if ($element->getParent()) {
			return $this->searchForClass($class, $element->getParent());
		}

		return null;
	}
}
