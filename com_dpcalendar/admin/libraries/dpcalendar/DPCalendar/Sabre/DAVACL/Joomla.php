<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2013 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace DPCalendar\Sabre\DAVACL;

use Sabre\DAVACL\Plugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Joomla extends Plugin
{
	function beforeMethod(RequestInterface $request, ResponseInterface $response)
	{
		$method = $request->getMethod();
		$path   = $request->getPath();

		$exists = $this->server->tree->nodeExists($path);

		if (!$exists) {
			return;
		}

		if ($method == 'REPORT') {
			$this->checkPrivileges($path, '{DAV:}read');
		}
	}
}
