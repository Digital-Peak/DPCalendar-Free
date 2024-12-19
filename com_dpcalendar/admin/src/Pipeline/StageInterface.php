<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Pipeline;

\defined('_JEXEC') or die();

interface StageInterface
{
	/**
	 * Process the payload.
	 */
	public function __invoke(\stdClass $payload): \stdClass;
}
