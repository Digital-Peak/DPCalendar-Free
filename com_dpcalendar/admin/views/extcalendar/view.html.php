<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\View\BaseView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class DPCalendarViewExtcalendar extends BaseView
{
	protected $state;
	protected $item;
	protected $form;

	protected function init()
	{
		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');

		$this->form->removeField('alias');
		$this->form->removeField('ordering');
		$this->form->removeField('created');
		$this->form->removeField('created_by');
		$this->form->removeField('created_by_alias');
		$this->form->removeField('modified');
		$this->form->removeField('modified_by');
		$this->form->removeField('publish_up');
		$this->form->removeField('publish_down');
		$this->form->removeField('version');
		$this->form->removeField('asset_id');
		$this->form->removeField('sync_date');
		$this->form->removeField('sync_token');
	}

	protected function addToolbar()
	{
		$this->app->input->set('hidemainmenu', true);

		$isNew = ($this->item->id == 0);
		$canDo = DPCalendarHelper::getActions();

		if ($canDo->get('core.edit')) {
			ToolbarHelper::apply('extcalendar.apply');
			ToolbarHelper::save('extcalendar.save');
		}
		if ($canDo->get('core.create')) {
			ToolbarHelper::save2new('extcalendar.save2new');
		}
		if (!$isNew && $canDo->get('core.create')) {
			ToolbarHelper::save2copy('extcalendar.save2copy');
		}
		if (empty($this->item->id)) {
			ToolbarHelper::cancel('extcalendar.cancel');
		} else {
			ToolbarHelper::cancel('extcalendar.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();

		parent::addToolbar();
	}
}
