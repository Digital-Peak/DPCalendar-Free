<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Input\Input;
use Joomla\Utilities\ArrayHelper;

class EventsController extends AdminController implements CurrentUserInterface
{
	use CurrentUserTrait;

	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);
		$this->registerTask('unfeatured', 'featured');
	}

	public function getModel($name = 'Event', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function featured(): void
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$user   = $this->getCurrentUser();
		$ids    = $this->input->get('cid', [], 'array');
		$values = [
			'featured'   => 1,
			'unfeatured' => 0
		];
		$task  = $this->getTask();
		$value = ArrayHelper::getValue($values, $task, 0, 'int');
		ArrayHelper::toInteger($ids);

		$model = $this->getModel('Events');
		$model->setState('filter.ids', $ids);

		// Access checks.
		foreach ($model->getItems() as $i => $event) {
			if (!$user->authorise('core.edit.state', 'com_dpcalendar.category.' . (int)$event->catid)) {
				// Prune items that you can't change.
				unset($ids[$i]);
				$this->app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'warning');
			}
		}

		if (empty($ids)) {
			$this->app->enqueueMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
		} else {
			// Get the model.
			$model = $this->getModel('Event');

			// Publish the items.
			if (!$model->featured($ids, $value)) {
				$this->app->enqueueMessage($model->getError(), 'warning');
			}
		}

		$this->setRedirect('index.php?option=com_dpcalendar&view=events');
	}
}
