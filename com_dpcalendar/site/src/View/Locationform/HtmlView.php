<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Locationform;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;

class HtmlView extends BaseView
{
	/** @var string */
	protected $location;

	/** @var string */
	protected $form;

	/** @var string */
	protected $returnPage;

	public function display($tpl = null): void
	{
		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Location', 'Administrator');
		$this->setModel($model, true);

		parent::display($tpl);
	}

	protected function init(): void
	{
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/forms');
		$this->app->getLanguage()->load('', JPATH_ADMINISTRATOR);

		$this->location   = $this->get('Item');
		$this->form       = $this->get('Form');
		$this->returnPage = $this->get('ReturnPage');

		if ($this->location->id && $this->location->params->get('access-edit')) {
			return;
		}

		if (!$this->user->authorise('core.create', 'com_dpcalendar')) {
			$this->handleNoAccess();
			return;
		}
	}
}
