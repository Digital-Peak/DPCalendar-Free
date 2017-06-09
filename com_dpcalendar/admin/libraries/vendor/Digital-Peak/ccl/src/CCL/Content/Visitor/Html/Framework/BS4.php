<?php

namespace CCL\Content\Visitor\Html\Framework;

/**
 * The Bootstrap 4 framework visitor.
 */
class BS4 extends BS3
{
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
}
