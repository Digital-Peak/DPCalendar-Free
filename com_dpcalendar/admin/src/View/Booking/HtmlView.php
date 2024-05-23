<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\Booking;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseView
{
	/** @var \stdClass */
	protected $booking;

	/** @var Form */
	protected $form;

	/** @var array */
	protected $tickets;

	protected function init(): void
	{
		$this->booking = $this->get('Item');
		$this->form    = $this->get('Form');
		$this->tickets = [];

		$this->form->removeField('options');
		$this->form->removeField('series');
		if ($this->booking && !empty($this->booking->id)) {
			$this->form->bind($this->booking);

			$this->form->removeField('event_id');
			$this->form->removeField('amount');

			return;
		}

		$this->form->setFieldAttribute('event_id', 'required', 'true');
	}

	protected function addToolbar(): void
	{
		$this->input->set('hidemainmenu', true);

		$canDo = ContentHelper::getActions('com_dpcalendar');
		if ($canDo->get('core.edit')) {
			ToolbarHelper::apply('booking.apply');
			ToolbarHelper::save('booking.save');
		}
		if (empty($this->booking->id)) {
			ToolbarHelper::cancel('booking.cancel');
		} else {
			ToolbarHelper::cancel('booking.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		parent::addToolbar();
	}
}
