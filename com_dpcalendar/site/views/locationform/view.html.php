<?php

use DPCalendar\View\BaseView;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewLocationForm extends BaseView
{
	public $location;
	public $form;
	public $returnPage;
	public function display($tpl = null)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
		$model = BaseDatabaseModel::getInstance('Location', 'DPCalendarModel');
		$this->setModel($model, true);

		return parent::display($tpl);
	}

	protected function init()
	{
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');
		$this->app->getLanguage()->load('', JPATH_ADMINISTRATOR);

		$this->location   = $this->get('Item');
		$this->form       = $this->get('Form');
		$this->returnPage = $this->get('ReturnPage');

		if ($this->location->id && $this->location->params->get('access-edit')) {
			return;
		}

		if (!$this->user->authorise('core.create', 'com_dpcalendar')) {
			return $this->handleNoAccess();
		}
	}
}
