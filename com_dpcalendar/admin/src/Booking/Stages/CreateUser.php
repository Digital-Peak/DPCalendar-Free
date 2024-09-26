<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\User\UserHelper;
use Joomla\Component\Users\Site\Model\RegistrationModel;
use Joomla\Database\DatabaseInterface;
use League\Pipeline\StageInterface;

class CreateUser implements StageInterface
{
	public function __construct(
		private readonly CMSApplicationInterface $application,
		private readonly DatabaseInterface $db,
		private readonly RegistrationModel $model
	) {
	}

	public function __invoke($payload)
	{
		$data = [];
		// Do not create when state is not active and previous state was active as well
		if ($payload->item->state != 1 || ($payload->oldItem && $payload->oldItem->state == 1)) {
			return $payload;
		}

		// Only create the user when respective setting is set and user_id is not set
		if (ComponentHelper::getParams('com_dpcalendar')->get('booking_registration', 1) != 2 || $payload->data['user_id']) {
			return $payload;
		}

		// Check if there is a user with the email already
		$query = $this->db->getQuery(true)
			->select('id')->from('#__users')
			->where('email = ' . $this->db->quote($payload->data['email']));
		$this->db->setQuery($query, 0, 1);

		if ($this->db->loadResult()) {
			return $payload;
		}

		$this->application->getLanguage()->load('com_users', JPATH_SITE);
		Form::addFormPath(JPATH_SITE . '/components/com_users/forms');

		$data['name']         = $payload->data['name'];
		$data['email1']       = $payload->data['email'];
		$data['username']     = $payload->data['email'];
		$data['password1']    = UserHelper::genRandomPassword();
		$data['requireReset'] = 1;

		if (!$this->model->register($data)) {
			$this->application->enqueueMessage($this->model->getError(), 'warning');
		}

		$this->db->setQuery($query, 0, 1);
		$payload->item->user_id = $this->db->loadResult();

		return $payload;
	}
}
