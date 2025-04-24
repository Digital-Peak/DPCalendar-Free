<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Model\BookingModel;
use DigitalPeak\Component\DPCalendar\Administrator\Model\CouponModel;
use DigitalPeak\Component\DPCalendar\Administrator\Pipeline\StageInterface;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\User\User;

class SetupForUpdate implements StageInterface
{
	public function __construct(
		private readonly CMSApplicationInterface $application,
		private readonly User $user,
		private readonly BookingModel $bookingModel,
		private readonly CouponModel $couponModel
	) {
	}

	public function __invoke(\stdClass $payload): \stdClass
	{
		// Unset some variables, that it can't be changed afterwards through some form hacking
		if ($this->application->isClient('site') && !$this->user->authorise('dpcalendar.admin.book', 'com_dpcalendar')) {
			unset($payload->data['price']);
		}

		if (!\array_key_exists('coupon_id', $payload->data)) {
			$payload->data['coupon_id'] = 0;
		}

		$couponId = is_numeric($payload->data['coupon_id']) ? $payload->data['coupon_id'] : ['code' => $payload->data['coupon_id']];
		if (!empty($payload->data['coupon_id']) && $coupon = $this->couponModel->getItem($couponId)) {
			$payload->data['coupon_id'] = $coupon->id;
		} else {
			$payload->data['coupon_id'] = 0;
		}

		if (!empty($payload->data['state']) && $payload->data['state'] == 6 && $payload->data['id']) {
			$booking = $this->bookingModel->getItem($payload->data['id']);
			if (!$booking || !Booking::openForCancel($booking)) {
				throw new \Exception('Booking can not be cancelled');
			}
		}

		return $payload;
	}
}
