<?php

namespace CCL\Content\Visitor\Html\Framework;

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\ListContainer;
use CCL\Content\Element\Basic\ListItem;
use CCL\Content\Element\Component\Alert;
use CCL\Content\Element\Component\Badge;
use CCL\Content\Element\Basic\Button;
use CCL\Content\Element\Basic\DescriptionListHorizontal;
use CCL\Content\Element\Basic\Form;
use CCL\Content\Element\Component\Dropdown;
use CCL\Content\Element\Component\Grid\Column;
use CCL\Content\Element\Component\Grid\Row;
use CCL\Content\Element\Component\TabContainer;
use CCL\Content\Element\Basic\Table;
use CCL\Content\Visitor\AbstractElementVisitor;

/**
 * The Uikit 2 framework visitor.
 */
class UIkit2 extends AbstractElementVisitor
{
	/**
	 * The alert mappings.
	 *
	 * @var array
	 */
	protected $alertTypes = [
		Alert::INFO    => 'info',
		Alert::SUCCESS => 'success',
		Alert::WARNING => 'warning',
		Alert::DANGER  => 'danger'
	];

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitAlert()
	 */
	public function visitAlert(Alert $alert)
	{
		$alert->addClass('uk-alert', true);
		$alert->addClass('uk-alert-' . $this->alertTypes[$alert->getType()], true);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitBadge()
	 */
	public function visitBadge(Badge $badge)
	{
		$badge->addClass('uk-badge', true);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitButton()
	 */
	public function visitButton(Button $button)
	{
		$button->addClass('uk-button', true);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitDescriptionListHorizontal()
	 */
	public function visitDescriptionListHorizontal(DescriptionListHorizontal $descriptionListHorizontal)
	{
		$descriptionListHorizontal->addClass('uk-description-list-horizontal', true);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitDropdown()
	 */
	public function visitDropdown(\CCL\Content\Element\Component\Dropdown $dropdown)
	{
		// Prepare for restructure
		$trigger  = $dropdown->getTriggerElement();
		$elements = $dropdown->clearChildren();

		// Configure the drop down
		$dropdown->addClass('uk-button-dropdown', true);
		$dropdown->addAttribute('data-uk-dropdown', "{mode:'click'}");
		$dropdown->setTriggerElement($trigger);

		// Configure the trigger element
		$trigger->addClass('dropdown-toggle', true);

		// Set up a list
		$c = $dropdown->addChild(new Container('container'));
		$c->addClass('uk-dropdown', true);
		$c->addClass('uk-dropdown-bottom', true);

		$l = $c->addChild(new ListContainer('list', ListContainer::UNORDERED));
		$l->addClass('uk-nav', true);
		$l->addClass('uk-nav-dropdown', true);
		$l->addAttribute('role', 'menu');

		// Add the elements as items again
		foreach ($elements as $index => $element) {
			if ($element == $trigger) {
				continue;
			}

			$li = $l->addListItem(new ListItem($index + 1));
			$li->addChild($element);
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitForm()
	 */
	public function visitForm(Form $form)
	{
		$form->addClass('uk-form', true);
		$form->addClass('uk-form-horizontal', true);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitFormLabel()
	 */
	public function visitFormLabel(\CCL\Content\Element\Basic\Form\Label $formLabel)
	{
		$formLabel->addClass('uk-form-label', true);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\Basic\ElementVisitorInterface::visitGridColumn()
	 */
	public function visitGridColumn(Column $gridColumn)
	{
		$width = (10 / 100) * $gridColumn->getWidth();
		$width = round($width);

		if ($width < 1) {
			$width = 1;
		}
		if ($width > 10) {
			$width = 10;
		}

		$gridColumn->addClass('uk-width-' . $width . '-10', true);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitGridRow()
	 */
	public function visitGridRow(Row $gridRow)
	{
		$gridRow->addClass('uk-grid', true);
		$gridRow->addClass('uk-grid-collapse', true);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitListContainer()
	 */
	public function visitListContainer(\CCL\Content\Element\Basic\ListContainer $listContainer)
	{
		if ($listContainer->getParent() instanceof TabContainer) {
			return;
		}
		if ($listContainer->getParent() instanceof Dropdown) {
			return;
		}

		$listContainer->addClass('uk-list', true);
		$listContainer->addClass('uk-list-striped', true);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitPanel()
	 */
	public function visitPanel(\CCL\Content\Element\Component\Panel $panel)
	{
		$panel->addClass('uk-card', true);
		$panel->addClass('uk-card-default', true);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitPanelBody()
	 */
	public function visitPanelBody(\CCL\Content\Element\Component\Panel\Body $panelBody)
	{
		$panelBody->addClass('uk-card-body', true);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitPanelImage()
	 */
	public function visitPanelImage(\CCL\Content\Element\Component\Panel\Image $panelImage)
	{
		$panelImage->addClass('uk-card-media-top', true);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitPanelTitle()
	 */
	public function visitPanelTitle(\CCL\Content\Element\Component\Panel\Title $panelTitle)
	{
		$panelTitle->addClass('uk-card-title', true);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitTabContainer()
	 */
	public function visitTabContainer(TabContainer $tabContainer)
	{
		// Set up the tab links
		$tabLinks = $tabContainer->getTabLinks();
		$tabLinks->addClass('uk-tab', true);
		$tabLinks->addAttribute('data-uk-tab', '{connect:"#' . $tabContainer->getTabs()->getId() . '"}');

		// Set the first one as active
		foreach ($tabLinks->getChildren() as $index => $link) {
			if ($index == 0) {
				$link->addClass('uk-active', true);
				break;
			}
		}

		// Set up the tab content
		$tabContainer->getTabs()->addClass('uk-switcher', true);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitTable()
	 */
	public function visitTable(Table $table)
	{
		$table->addClass('uk-table', true);
		$table->addClass('uk-table-stripped', true);
	}
}
