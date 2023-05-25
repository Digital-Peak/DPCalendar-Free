<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use DPCalendar\Helper\Booking;
use DPCalendarModelBooking;
use DPCalendarModelCoupon;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\User\User;
use League\Pipeline\StageInterface;

class SetupForUpdate implements StageInterface
{
	/**
	 * @var CMSApplication
	 */
	private $application = null;

	/**
	 * @var User
	 */
	private $user = null;

	/**
	 * @var \DPCalendarModelBooking
	 */
	private $bookingModel = null;

	/**
	 * @var \DPCalendarModelCoupon
	 */
	private $couponModel = null;

	public function __construct(CMSApplication $application, User $user, DPCalendarModelBooking $bookingModel, DPCalendarModelCoupon $couponModel)
	{
		$this->application  = $application;
		$this->user         = $user;
		$this->bookingModel = $bookingModel;
		$this->couponModel  = $couponModel;
	}

	public function __invoke($payload)
	{
		// Unset some variables, that it can't be changed afterwards through some form hacking
		if ($this->application->isClient('site') && !$this->user->authorise('dpcalendar.admin.book', 'com_dpcalendar')) {
			unset($payload->data['price']);
		}

		if (!array_key_exists('coupon_id', $payload->data)) {
			$payload->data['coupon_id'] = 0;
		}

		$couponId = is_numeric($payload->data['coupon_id']) ? $payload->data['coupon_id'] : ['code' => $payload->data['coupon_id']];
		if (!empty($payload->data['coupon_id']) && $coupon = $this->couponModel->getItem($couponId)) {
			$payload->data['coupon_id'] = $coupon->id;
		} else {
			$payload->data['coupon_id'] = 0;
		}

		if ($payload->data['state'] == 6 && $payload->data['id']) {
			$booking = $this->bookingModel->getItem($payload->data['id']);
			if (!Booking::openForCancel($booking)) {
				throw new Exception('Booking can not be cancelled');
			}
		}
		return $payload;
	}
}
