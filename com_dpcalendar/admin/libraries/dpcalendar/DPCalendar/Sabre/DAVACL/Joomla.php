<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Sabre\DAVACL;

use Sabre\DAVACL\Plugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Joomla extends Plugin
{
	public function beforeMethod(RequestInterface $request, ResponseInterface $response)
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
