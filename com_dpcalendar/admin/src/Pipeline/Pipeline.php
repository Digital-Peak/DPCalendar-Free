<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Pipeline;

\defined('_JEXEC') or die();

class Pipeline
{
	private array $stages = [];

	/**
	 * Adds a stage to the pipeline.
	 */
	public function add(callable $stage): void
	{
		$this->stages[] = $stage;
	}

	/**
	 * Processes the pipeline
	 */
	public function process(mixed $payload): mixed
	{
		foreach ($this->stages as $stage) {
			$payload = $stage($payload);
		}

		return $payload;
	}
}
