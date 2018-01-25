<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\CCL\Visitor;

defined('_JEXEC') or die();

class InlineStyleVisitor extends \CCL\Content\Visitor\AbstractElementVisitor
{
	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitHeading()
	 */
	public function visitHeading(\CCL\Content\Element\Basic\Heading $heading)
	{
		$heading->addAttribute('style', 'border-bottom: 1px solid #eee');
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitTable()
	 */
	public function visitTable(\CCL\Content\Element\Basic\Table $table)
	{
		$table->addAttribute('style', 'width:100%');
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitTableCell()
	 */
	public function visitTableCell(\CCL\Content\Element\Basic\Table\Cell $tableCell)
	{
		if (strpos($tableCell->getId(), '-content') !== false) {
			$tableCell->addAttribute('style', 'width:70%');
		}
		if (strpos($tableCell->getId(), '-label') !== false) {
			$tableCell->addAttribute('style', 'width:30%');
		}
		if (strpos($tableCell->getId(), '-ticket-uid') !== false) {
			$tableCell->addAttribute('style', 'width:50%');
		}
		if (strpos($tableCell->getId(), '-ticket-name') !== false) {
			$tableCell->addAttribute('style', 'width:30%');
		}
		if (strpos($tableCell->getId(), '-ticket-price') !== false) {
			$tableCell->addAttribute('style', 'width:13%');
		}
		if (strpos($tableCell->getId(), '-ticket-seat') !== false) {
			$tableCell->addAttribute('style', 'width:7%');
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \CCL\Content\Visitor\ElementVisitorInterface::visitTableHeadCell()
	 */
	public function visitTableHeadCell(\CCL\Content\Element\Basic\Table\HeadCell $tableHeadCell)
	{
		if (strpos($tableHeadCell->getId(), 'ticket-details-head-row-cell-0') !== false) {
			$tableHeadCell->addAttribute('style', 'width:50%');
		}
		if (strpos($tableHeadCell->getId(), 'ticket-details-head-row-cell-1') !== false) {
			$tableHeadCell->addAttribute('style', 'width:30%');
		}
		if (strpos($tableHeadCell->getId(), 'ticket-details-head-row-cell-2') !== false) {
			$tableHeadCell->addAttribute('style', 'width:13%');
		}
		if (strpos($tableHeadCell->getId(), 'ticket-details-head-row-cell-3') !== false) {
			$tableHeadCell->addAttribute('style', 'width:7%');
		}
	}
}
