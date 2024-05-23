<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Ticketform;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;

class HtmlView extends BaseView
{
	/** @var object */
	protected $ticket;

	/** @var Form */
	protected $form;

	/** @var string */
	protected $returnPage;

	public function display($tpl = null): void
	{
		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Ticket', 'Administrator');
		$this->setModel($model, true);

		parent::display($tpl);
	}

	protected function init(): void
	{
		$this->app->getLanguage()->load('', JPATH_ADMINISTRATOR);

		$this->ticket     = $this->get('Item');
		$this->form       = $this->get('Form');
		$this->returnPage = $this->get('ReturnPage');

		if (!$this->ticket->id) {
			$this->handleNoAccess();
			return;
		}

		$this->form->setFieldAttribute('id', 'type', 'hidden');
	}
}
