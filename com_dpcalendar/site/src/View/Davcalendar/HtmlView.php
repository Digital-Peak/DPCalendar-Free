<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Davcalendar;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Form\Form;

class HtmlView extends BaseView
{
	/** @var string */
	protected $returnPage;

	/** @var Form */
	protected $form;

	/** @var \stdClass */
	protected $item;

	protected function init(): void
	{
		$user = $this->getCurrentUser();

		$this->item       = $this->get('Item');
		$this->form       = $this->get('Form');
		$this->returnPage = $this->get('ReturnPage');

		$authorised = true;
		if ($this->item !== null && $this->item->id) {
			$authorised = $this->item->principaluri == 'principals/' . $user->username;
		}

		if (!$authorised) {
			throw new \Exception($this->translate('COM_DPCALENDAR_ALERT_NO_AUTH'));
		}

		$this->form->bind($this->item);

		parent::init();
	}
}
