<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Form\Form;
use Joomla\CMS\User\UserHelper;
use League\Pipeline\StageInterface;

class CreateUser implements StageInterface
{
	/**
	 * @var CMSApplication
	 */
	private $application = null;

	/**
	 * @var \JDatabaseDriver
	 */
	private $db = null;

	/**
	 * @var \UsersModelRegistration
	 */
	private $model = null;

	public function __construct(CMSApplication $application, \JDatabaseDriver $db, \UsersModelRegistration $model)
	{
		$this->application = $application;
		$this->db          = $db;
		$this->model       = $model;
	}

	public function __invoke($payload)
	{
		// Do not create when state is not active and previous state was active as well
		if ($payload->item->state != 1 || ($payload->oldItem && $payload->oldItem->state == 1)) {
			return $payload;
		}

		// Only create the user when respective setting is set and user_id is not set
		if (\JComponentHelper::getParams('com_dpcalendar')->get('booking_registration', 1) != 2 || $payload->data['user_id']) {
			return $payload;
		}

		// Check if there is a user with the email already
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__users'))
			->where($this->db->quoteName('email') . ' = ' . $this->db->quote($payload->data['email']));
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
