<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Invite;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryAwareInterface;
use Joomla\CMS\Form\FormFactoryAwareTrait;
use Joomla\CMS\HTML\HTMLHelper;

class HtmlView extends BaseView implements FormFactoryAwareInterface
{
	use FormFactoryAwareTrait;

	/** @var Form */
	protected $form;

	protected function init(): void
	{
		// Set the default model
		$this->setModel($this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Administrator'), true);

		$this->app->getLanguage()->load('', JPATH_ADMINISTRATOR);

		$event = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Administrator')->getItem($this->input->getInt('id', 0));
		if (!$event || !$event->id || $this->user->authorise('dpcalendar.invite', 'com_dpcalendar.category.' . $event->catid) !== true) {
			throw new \Exception($this->translate('COM_DPCALENDAR_ALERT_NO_AUTH'));
		}

		$this->form = $this->getFormFactory()->createForm('com_dpcalendar.invite', ['control' => 'jform']);
		$this->form->loadFile(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/forms/invite.xml');

		$this->form->setValue('event_id', null, $event->id);

		HTMLHelper::_('behavior.formvalidator');
	}
}
