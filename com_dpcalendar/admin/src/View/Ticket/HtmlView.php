<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\Ticket;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseView
{
	/** @var \stdClass */
	protected $ticket;

	/** @var Form */
	protected $form;

	protected function init(): void
	{
		$this->ticket = $this->get('Item');
		$this->form   = $this->get('Form');
	}

	protected function addToolbar(): void
	{
		$this->input->set('hidemainmenu', true);

		$canDo = ContentHelper::getActions('com_dpcalendar');

		if ($canDo->get('core.edit')) {
			ToolbarHelper::apply('ticket.apply');
			ToolbarHelper::save('ticket.save');
		}
		if (empty($this->ticket->id)) {
			ToolbarHelper::cancel('ticket.cancel');
		} else {
			ToolbarHelper::cancel('ticket.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		parent::addToolbar();
	}
}
