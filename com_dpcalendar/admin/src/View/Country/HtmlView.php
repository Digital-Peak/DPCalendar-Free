<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\Country;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseView
{
	/** @var \stdClass */
	protected $country;

	/** @var Form */
	protected $form;

	protected function init(): void
	{
		$this->country = $this->get('Item');
		$this->form    = $this->get('Form');

		$this->app->getLanguage()->load('com_dpcalendar.countries', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
	}

	protected function addToolbar(): void
	{
		$this->input->set('hidemainmenu', true);

		$isNew      = ($this->country->id == 0);
		$checkedOut = $this->country->checked_out != 0 && $this->country->checked_out != $this->user->id;
		$canDo      = ContentHelper::getActions('com_dpcalendar');

		if (!$checkedOut && $canDo->get('core.edit')) {
			ToolbarHelper::apply('country.apply');
			ToolbarHelper::save('country.save');
		}
		if (!$checkedOut && $canDo->get('core.create')) {
			ToolbarHelper::save2new('country.save2new');
		}
		if (!$isNew && $canDo->get('core.create')) {
			ToolbarHelper::save2copy('country.save2copy');
		}
		if (empty($this->country->id)) {
			ToolbarHelper::cancel('country.cancel');
		} else {
			ToolbarHelper::cancel('country.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		parent::addToolbar();
	}
}
