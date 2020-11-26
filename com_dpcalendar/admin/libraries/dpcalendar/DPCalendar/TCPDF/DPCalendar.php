<?php
/**
 * @package    DPCalendar
 * @copyright  Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
namespace DPCalendar\TCPDF;

use Joomla\Registry\Registry;

// TCPDF variables
define('K_TCPDF_EXTERNAL_CONFIG', true);
define('K_TCPDF_THROW_EXCEPTION_ERROR', true);
define('PDF_FONT_NAME_MAIN', 'dejavusans');

class DPCalendar extends \TCPDF
{
	private $params;

	public function __construct(Registry $params)
	{
		parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		$this->params = $params;
	}

	// Page header
	public function Header()
	{
		$this->Cell(0, 0, $this->params->get('invoice_header'), 'B', false, 'L', 0, '', 0, false, 'M', 'M');
	}

	// Page footer
	public function Footer()
	{
		$date = \DPCalendarHelper::getDate()->format(
			$this->params->get('event_date_format', 'd.m.Y') . ' ' . $this->params->get('event_time_format', 'H:i')
		);
		$this->Cell(45, 0, $date, 'T', 0, 'L', 0, '', 0, false, 'T', 'M');
		$this->Cell(125, 0, $this->params->get('invoice_footer'), 'T', 0, 'C', 0, '', 0, false, 'T', 'M');
		$this->Cell(8, 0, $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 'T', 0, 'L', 0, '', 0, false, 'T', 'M');
	}
}
