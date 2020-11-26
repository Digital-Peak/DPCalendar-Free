<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use League\Pipeline\StageInterface;

class SetupForUpdate implements StageInterface
{
	/**
	 * @var \JApplicationCms
	 */
	private $application = null;

	/**
	 * @var \JUser
	 */
	private $user = null;

	/**
	 * @var \DPCalendarModelCoupon
	 */
	private $couponModel = null;

	public function __construct(\JApplicationCms $application, \JUser $user, \DPCalendarModelCoupon $couponModel)
	{
		$this->application = $application;
		$this->user        = $user;
		$this->couponModel = $couponModel;
	}

	public function __invoke($payload)
	{
		// Unset some variables, that it can't be changed afterwards through some form hacking
		if ($this->application->isClient('site') && !$this->user->authorise('dpcalendar.admin.book', 'com_dpcalendar')) {
			unset($payload->data['price']);
		}

		if (!empty($payload->data['coupon_id']) && $coupon = $this->couponModel->getItem(['code' => $payload->data['coupon_id']])) {
			$payload->data['coupon_id'] = $coupon->id;
		}

		return $payload;
	}
}
