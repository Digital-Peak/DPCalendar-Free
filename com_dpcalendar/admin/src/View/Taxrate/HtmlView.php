<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\Taxrate;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseView
{
	/** @var \stdClass */
	protected $taxrate;

	/** @var Form */
	protected $form;

	protected function init(): void
	{
		$this->taxrate = $this->get('Item');
		$this->form    = $this->get('Form');
	}

	protected function addToolbar(): void
	{
		$this->input->set('hidemainmenu', true);

		$isNew      = ($this->taxrate->id == 0);
		$checkedOut = $this->taxrate->checked_out != 0 && $this->taxrate->checked_out != $this->user->id;
		$canDo      = ContentHelper::getActions('com_dpcalendar');

		if (!$checkedOut && $canDo->get('core.edit')) {
			ToolbarHelper::apply('taxrate.apply');
			ToolbarHelper::save('taxrate.save');
		}
		if (!$checkedOut && $canDo->get('core.create')) {
			ToolbarHelper::save2new('taxrate.save2new');
		}
		if (!$isNew && $canDo->get('core.create')) {
			ToolbarHelper::save2copy('taxrate.save2copy');
		}
		if (empty($this->taxrate->id)) {
			ToolbarHelper::cancel('taxrate.cancel');
		} else {
			ToolbarHelper::cancel('taxrate.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		parent::addToolbar();
	}
}
