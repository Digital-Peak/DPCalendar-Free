<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\TCPDF;

use Joomla\Registry\Registry;

// TCPDF variables
define('K_TCPDF_EXTERNAL_CONFIG', true);
define('K_TCPDF_THROW_EXCEPTION_ERROR', true);

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
			$this->params->get('event_date_format', 'm.d.Y') . ' ' . $this->params->get('event_time_format', 'g:i a'));
		$this->Cell(30, 0, $date, 'T', false, 'L', 0, '', 0, false, 'T', 'M');
		$this->Cell(120, 0, $this->params->get('invoice_footer'), 'T', false, 'C', 0, '', 0, false, 'T', 'C');
		$this->Cell(0, 0, $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 'T', false, 'R', 0, '', 0, false, 'T', 'M');
	}
}
