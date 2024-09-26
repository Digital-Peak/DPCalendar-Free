<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\Location;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseView
{
	/** @var \stdClass */
	protected $location;

	/** @var Form */
	protected $form;

	protected function init(): void
	{
		$this->location = $this->get('Item');
		$this->form     = $this->get('Form');
	}

	protected function addToolbar(): void
	{
		$this->input->set('hidemainmenu', true);

		$isNew      = ($this->location->id == 0);
		$checkedOut = $this->location->checked_out != 0 && $this->location->checked_out != $this->user->id;
		$canDo      = ContentHelper::getActions('com_dpcalendar');

		if (!$checkedOut && $canDo->get('core.edit')) {
			ToolbarHelper::apply('location.apply');
			ToolbarHelper::save('location.save');
		}
		if (!$checkedOut && $canDo->get('core.create')) {
			ToolbarHelper::save2new('location.save2new');
		}
		if (!$isNew && $canDo->get('core.create')) {
			ToolbarHelper::save2copy('location.save2copy');
		}
		if (empty($this->location->id)) {
			ToolbarHelper::cancel('location.cancel');
		} else {
			ToolbarHelper::cancel('location.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		parent::addToolbar();
	}
}
