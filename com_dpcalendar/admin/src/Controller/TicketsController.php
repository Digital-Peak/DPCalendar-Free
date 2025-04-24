<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

\defined('_JEXEC') or die();

use Joomla\CMS\MVC\Controller\AdminController;

class TicketsController extends AdminController
{
	protected $text_prefix = 'COM_DPCALENDAR_TICKET';

	public function firstname(): void
	{
		$fieldId = $this->input->getInt('id', 0);
		if (!$fieldId) {
			throw new \Exception('Custom field id not set!');
		}

		$count = $this->getModel('Tickets')->updateFirstNameFromField($fieldId);
		$this->setRedirect('index.php?option=com_dpcalendar&view=tickets', 'Successfully moved ' . $count . ' first_names from custom field to ticket!');
	}

	public function getModel($name = 'Ticket', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
