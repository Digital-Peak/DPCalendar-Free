<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Serializer\JoomlaSerializer;
use Joomla\CMS\Uri\Uri;
use Tobscure\JsonApi\Relationship;
use Tobscure\JsonApi\Resource;

class DPCalendarSerializer extends JoomlaSerializer
{
	public $type;
	public function calendar($model)
	{
		$serializer = new JoomlaSerializer('calendars');

		$resource = (new Resource($model->catid, $serializer))
			->addLink('self', Route::link('site', Uri::root() . 'api/index.php/v1/dpcalendar/calendars/' . $model->catid));

		return new Relationship($resource);
	}

	public function createdBy($model)
	{
		$serializer = new JoomlaSerializer('users');

		$resource = (new Resource($model->created_by, $serializer))
			->addLink('self', Route::link('site', Uri::root() . 'api/index.php/v1/users/' . $model->created_by));

		return new Relationship($resource);
	}

	public function modifiedBy($model)
	{
		$serializer = new JoomlaSerializer('users');

		$resource = (new Resource($model->modified_by, $serializer))
			->addLink('self', Route::link('site', Uri::root() . 'api/index.php/v1/users/' . $model->modified_by));

		return new Relationship($resource);
	}

	public function getType($model)
	{
		return $this->type;
	}
}
