<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR)) {
	return;
}

class PlgSampledataDPCalendar extends JPlugin
{
	protected $db;
	protected $app;
	protected $autoloadLanguage = true;

	private static $lorem = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>';

	private static $europeanDateFormatLanguages = ['de-DE', 'fr-FR'];

	private $languageCache = [];

	public function onSampledataGetOverview()
	{
		$data              = new stdClass();
		$data->name        = $this->_name;
		$data->title       = JText::_('PLG_SAMPLEDATA_DPCALENDAR_OVERVIEW_TITLE');
		$data->description = JText::_('PLG_SAMPLEDATA_DPCALENDAR_OVERVIEW_DESC');
		$data->icon        = 'calendar';
		$data->steps       = 9;

		return $data;
	}

	public function onAjaxSampledataApplyStep1()
	{
		if ($this->app->input->get('type') != $this->_name) {
			return;
		}

		try {
			$this->setUp();

			if ($this->params->get('erase')) {
				$this->clearTable('locations');
				$this->clearTable('events');
				$this->clearTable('events_location');
				$this->clearTable('bookings');
				$this->clearTable('tickets');
				$this->clearTable('taxrates');
				$this->clearTable('caldav_calendars');
				$this->clearTable('caldav_calendarinstances');

				$this->db->setQuery("delete from #__dpcalendar_extcalendars where plugin = 'ical' or plugin = 'google'");
				$this->db->execute();

				$this->db->setQuery("delete from #__categories where extension = 'com_dpcalendar'");
				$this->db->execute();

				$this->db->setQuery("delete from #__fields_values where field_id in (select id from #__fields where context like 'com_dpcalendar.event')");
				$this->db->execute();
				$this->db->setQuery("delete from #__fields where context like 'com_dpcalendar.event'");
				$this->db->execute();

				$this->db->setQuery("delete from #__menu where link like '%com_dpcalendar%' and client_id = 0");
				$this->db->execute();

				$this->db->setQuery("delete from #__menu_types where menutype like 'dpcalendar-%' and client_id = 0");
				$this->db->execute();

				$this->db->setQuery("delete from #__modules where module like 'mod_dpcalendar%' or title like '%DPCalendar%'");
				$this->db->execute();
			}

			$this->app->setUserState('sampledata.dpcalendar.events', []);
			$this->app->setUserState('sampledata.dpcalendar.calendars', []);
			$this->app->setUserState('sampledata.dpcalendar.locations', []);

			$ids   = [];
			$ids[] = $this->createLocation([
				'title'     => 'Los  Angeles',
				'country'   => 'United States',
				'province'  => 'California',
				'city'      => 'Los Angeles',
				'zip'       => '90012',
				'street'    => 'North Alameda Street',
				'number'    => '301-399',
				'latitude'  => '34.05084950',
				'longitude' => '-118.23809670'
			]);
			$ids[] = $this->createLocation([
				'title'     => 'New York',
				'country'   => 'United States',
				'province'  => 'New York',
				'city'      => 'New York',
				'zip'       => '10007',
				'street'    => 'Broadway',
				'number'    => '230',
				'latitude'  => '40.71276550',
				'longitude' => '-74.00599370'
			]);
			$ids[] = $this->createLocation([
				'title'     => 'Washington',
				'country'   => 'United States',
				'province'  => 'District of Columbia',
				'city'      => 'Washington',
				'zip'       => '20502',
				'street'    => 'Ellipse Road Northwest',
				'latitude'  => '38.89519300',
				'longitude' => '-77.03662770'
			]);
			$ids[] = $this->createLocation([
				'title'     => 'Chicago',
				'country'   => 'United States',
				'province'  => 'Illinois',
				'city'      => 'Chicago',
				'zip'       => '60604',
				'street'    => 'West Jackson Boulevard',
				'number'    => '53',
				'latitude'  => '41.87797870',
				'longitude' => '-87.62956640'
			]);
			$ids[] = $this->createLocation([
				'title'     => 'Indianapolis',
				'country'   => 'United States',
				'province'  => 'Indiana',
				'city'      => 'Indianapolis',
				'zip'       => '46204',
				'street'    => 'Monument Circle',
				'number'    => '1861',
				'latitude'  => '39.76849080',
				'longitude' => '-86.15767950'
			]);
			$ids[] = $this->createLocation([
				'title'     => 'Rocky Mountain',
				'country'   => 'United States',
				'province'  => 'Oklahoma',
				'city'      => 'Rocky Mountain',
				'latitude'  => '35.80536630',
				'longitude' => '-94.76744860'
			]);
			$ids[] = $this->createLocation([
				'title'     => 'Event Venue',
				'country'   => 'United States',
				'province'  => 'New York',
				'city'      => 'New York',
				'zip'       => '10123',
				'street'    => 'Les Pilates',
				'number'    => '571',
				'rooms'     => '{"rooms0":{"id":"1","title":"Thor","description":""},"rooms2":{"id":"2","title":"Wolverine","description":""},"rooms3":{"id":"3","title":"Hulk","description":""}}',
				'latitude'  => '40.75200000',
				'longitude' => '-73.99300000',
			]);

			$ids[] = $this->createLocation([
				'title'     => 'London',
				'country'   => 'United Kingdom',
				'city'      => 'London',
				'street'    => 'South Carriage Drive',
				'latitude'  => '51.50288180',
				'longitude' => '-0.15714460'
			]);
			$ids[] = $this->createLocation([
				'title'     => 'Berlin',
				'country'   => 'Germany',
				'province'  => 'Berlin',
				'city'      => 'Berlin',
				'zip'       => '10178',
				'street'    => 'Tunnel Alexanderplatz',
				'number'    => '9',
				'latitude'  => '52.52248280',
				'longitude' => '13.41158260'
			]);
			$ids[] = $this->createLocation([
				'title'     => 'Mailand',
				'country'   => 'Italy',
				'province'  => 'Lombardia',
				'city'      => 'Milano',
				'zip'       => '20122',
				'street'    => 'Piazza del Duomo',
				'number'    => 16,
				'latitude'  => '45.46369490',
				'longitude' => '9.19220070'
			]);
			$ids[] = $this->createLocation([
				'title'     => 'Zurich, Switzerland',
				'country'   => 'Switzerland',
				'province'  => 'Zürich',
				'city'      => 'Zürich',
				'zip'       => '8001',
				'street'    => 'Bahnhofstrasse',
				'number'    => '9-11',
				'latitude'  => '47.36837190',
				'longitude' => '8.53981550'
			]);
			$ids[] = $this->createLocation([
				'title'     => 'Bern',
				'country'   => 'Switzerland',
				'province'  => 'Bern',
				'city'      => 'Bern',
				'zip'       => '3011',
				'street'    => 'Münsterplatz',
				'number'    => '1',
				'latitude'  => '46.94720200',
				'longitude' => '7.45121710'
			]);
			$ids[] = $this->createLocation([
				'title'     => 'Genf',
				'country'   => 'Switzerland',
				'province'  => 'Genève',
				'city'      => 'Genève',
				'zip'       => '1204',
				'street'    => 'Rue Guillaume-Farel',
				'number'    => '8',
				'latitude'  => '46.20078940',
				'longitude' => '6.14889350'
			]);
			$ids[] = $this->createLocation([
				'title'     => 'Lisbon, Portugal',
				'country'   => 'Portugal',
				'province'  => 'Lisbon',
				'city'      => 'Lisbon',
				'latitude'  => '38.72225240',
				'longitude' => '-9.13933660'
			]);

			$this->app->setUserState('sampledata.dpcalendar.locations', $ids);

			$response          = new stdClass();
			$response->success = true;
			$response->message = JText::_('PLG_SAMPLEDATA_DPCALENDAR_STEP1_SUCCESS');
		} catch (Exception $e) {
			$response            = [];
			$response['success'] = false;
			$response['message'] = JText::sprintf('PLG_SAMPLEDATA_DPCALENDAR_STEP_FAILED', 1, $e->getMessage());
		}

		return $response;

	}

	public function onAjaxSampledataApplyStep2()
	{
		if ($this->app->input->get('type') != $this->_name) {
			return;
		}

		try {
			$this->setUp();

			$this->createCalendar('PLG_SAMPLEDATA_DPCALENDAR_CALENDAR_1_TITLE');
			$this->createCalendar('PLG_SAMPLEDATA_DPCALENDAR_CALENDAR_2_TITLE');
			$this->createCalendar('PLG_SAMPLEDATA_DPCALENDAR_CALENDAR_3_TITLE');

			if (!DPCalendarHelper::isFree()) {
				$this->db->setQuery("update #__extensions set enabled = 1 where name = 'plg_dpcalendar_ical' or name = 'plg_dpcalendar_google'");
				$this->db->execute();

				$this->createExternalCalendar([
					'title'  => 'PLG_SAMPLEDATA_DPCALENDAR_CALENDAR_ICAL_TITLE',
					'plugin' => 'ical',
					'color'  => 'c61111',
					'params' => ['uri' => 'plugins/dpcalendar/ical/examples/calendar.ics']
				]);

				$configFile = JPATH_ROOT . '/DPCalendarGoogleConfig.json';
				if (file_exists($configFile) && \Joomla\CMS\Plugin\PluginHelper::getPlugin('dpcalendar', 'google')) {
					$config = json_decode(file_get_contents($configFile));
					foreach ($config->calendars as $cal) {
						$this->createExternalCalendar([
							'title'  => 'PLG_SAMPLEDATA_DPCALENDAR_CALENDAR_GOOGLE_TITLE',
							'plugin' => 'google',
							'color'  => '1541ef',
							'params' => [
								'calendarId'    => $cal->calendarId,
								'refreshToken'  => $cal->refreshToken,
								'client-id'     => $cal->clientId,
								'client-secret' => $cal->clientSecret
							]
						]);
					}
				}

				$this->createPrivateCalendar([
					'displayname'   => 'PLG_SAMPLEDATA_DPCALENDAR_CALENDAR_PRIVATE_TITLE',
					'calendarcolor' => '1dc611'
				]);
			}

			$response          = new stdClass();
			$response->success = true;
			$response->message = JText::_('PLG_SAMPLEDATA_DPCALENDAR_STEP2_SUCCESS');
		} catch (Exception $e) {
			$response            = [];
			$response['success'] = false;
			$response['message'] = JText::sprintf('PLG_SAMPLEDATA_DPCALENDAR_STEP_FAILED', 2, $e->getMessage());
		}

		return $response;

	}

	public function onAjaxSampledataApplyStep3()
	{
		if ($this->app->input->get('type') != $this->_name) {
			return;
		}

		try {
			$this->setUp();

			$locationIds = $this->app->getUserState('sampledata.dpcalendar.locations');

			// Weekly
			$this->createEvent([
				'catid'          => 0,
				'title'          => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_1_TITLE',
				'rrule'          => 'FREQ=WEEKLY;BYDAY=MO',
				'color'          => 'CC2B40',
				'url'            => 'http://www.digital-peak.com',
				'description'    => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_1_DESC',
				'access_content' => 3
			]);

			// Two days
			$start = \DPCalendar\Helper\DPCalendarHelper::getDate();
			if (!DPCalendarHelper::isFree()) {
				$start->modify('-1 month');
			}
			$start->setTime(13, 0, 0);

			$end = clone $start;
			$end->modify('+2 hours');
			$end->modify('+1 day');
			$this->createEvent([
				'catid'        => 0,
				'title'        => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_2_TITLE',
				'rrule'        => 'FREQ=WEEKLY;BYDAY=WE',
				'start_date'   => $start->toSql(),
				'end_date'     => $end->toSql(),
				'images'       => '{"image_full":"images\\/joomla_black.png","image_full_alt":"","image_full_caption":"","image_intro":"","image_intro_alt":"","image_intro_caption":""}',
				'description'  => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_2_DESC',
				'location_ids' => $locationIds[0]
			]);

			// Full day
			$this->createEvent([
				'catid'       => 0,
				'title'       => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_3_TITLE',
				'rrule'       => 'FREQ=WEEKLY;BYDAY=TH',
				'all_day'     => '1',
				'color'       => 'B310CC',
				'description' => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_3_DESC'
			]);

			// Multi day
			$this->createEvent([
				'catid'        => 0,
				'title'        => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_4_TITLE',
				'rrule'        => 'FREQ=WEEKLY;BYDAY=SA',
				'color'        => 'FF9442',
				'description'  => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_4_DESC',
				'location_ids' => [$locationIds[3], $locationIds[4]]
			]);

			$response          = new stdClass();
			$response->success = true;
			$response->message = JText::_('PLG_SAMPLEDATA_DPCALENDAR_STEP3_SUCCESS');
		} catch (Exception $e) {
			$response            = [];
			$response['success'] = false;
			$response['message'] = JText::sprintf('PLG_SAMPLEDATA_DPCALENDAR_STEP_FAILED', 3, $e->getMessage());
		}

		return $response;
	}

	public function onAjaxSampledataApplyStep4()
	{
		if ($this->app->input->get('type') != $this->_name) {
			return;
		}

		try {
			$this->setUp();

			$locationIds = $this->app->getUserState('sampledata.dpcalendar.locations');

			$this->createEvent([
				'catid'               => 1,
				'title'               => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_6_TITLE',
				'rrule'               => 'FREQ=WEEKLY;BYDAY=SA',
				'images'              => '{"image_intro":"media\\/plg_sampledata_dpcalendar\\/images\\/festival.jpg","image_intro_alt":"","image_intro_caption":"","image_full":"media\\/plg_sampledata_dpcalendar\\/images\\/festival.jpg","image_full_alt":"","image_full_caption":""}',
				'description'         => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_6_DESC',
				'capacity'            => '80',
				'max_tickets'         => 2,
				'price'               => '{"value":["45.00"],"label":[""],"description":[""]}',
				'booking_information' => '<p>Every attendee needs to bring his own clothes.</p>',
				'location_ids'        => $locationIds[4]
			]);
			$this->createEvent([
				'catid'        => 1,
				'title'        => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_7_TITLE',
				'rrule'        => 'FREQ=WEEKLY;BYDAY=TH',
				'images'       => '{"image_intro":"media\\/plg_sampledata_dpcalendar\\/images\\/hike.jpg","image_intro_alt":"","image_intro_caption":"","image_full":"media\\/plg_sampledata_dpcalendar\\/images\\/hike.jpg","image_full_alt":"","image_full_caption":""}',
				'description'  => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_7_DESC',
				'capacity'     => '20',
				'max_tickets'  => 4,
				'price'        => '{"value":["20.00"],"label":[""],"description":[""]}',
				'location_ids' => $locationIds[5]
			]);
			$this->createEvent([
				'catid'        => 1,
				'title'        => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_8_TITLE',
				'rrule'        => 'FREQ=WEEKLY;BYDAY=SA',
				'images'       => '{"image_intro":"media\\/plg_sampledata_dpcalendar\\/images\\/swimming.jpg","image_intro_alt":"","image_intro_caption":"","image_full":"media\\/plg_sampledata_dpcalendar\\/images\\/swimming.jpg","image_full_alt":"","image_full_caption":""}',
				'description'  => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_8_DESC',
				'schedule'     => '{"schedule0":{"title":"Intro","duration":"10","description":"Welcome the attendees."},"schedule1":{"title":"Warmup","duration":"20","description":"Making yourself ready."},"schedule2":{"title":"Exercise","duration":"60","description":"Training the different styles."},"schedule3":{"title":"Feedback","duration":"10","description":"Discussion round amongst the attendees."}}',
				'capacity'     => null,
				'max_tickets'  => 2,
				'location_ids' => $locationIds[0]
			]);
			$this->createEvent([
				'catid'           => 1,
				'title'           => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_9_TITLE',
				'rrule'           => 'FREQ=WEEKLY;BYDAY=SU',
				'images'          => '{"image_intro":"media\\/plg_sampledata_dpcalendar\\/images\\/basketball.jpg","image_intro_alt":"","image_intro_caption":"","image_full":"media\\/plg_sampledata_dpcalendar\\/images\\/basketball.jpg","image_full_alt":"","image_full_caption":""}',
				'description'     => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_9_DESC',
				'capacity'        => '10',
				'max_tickets'     => 2,
				'price'           => '{"value":["55","68","89"],"label":["Kids","Student","Adults"],"description":["Age: 1 - 5 years","Needs an ID","Age: Older than 6 years"]}',
				'earlybird'       => '{"value":["20"],"type":["percentage"],"date":["-2 days"],"label":["Early Bird Discount"],"description":[" Decide early, pay less"]}',
				'booking_options' => '{"booking_options0":{"price":"15","amount":"1","label":"Lunch box small","description":"A small snack"},"booking_options1":{"price":"25","amount":"1","label":"Lunch box big","description":"For the hungry ones"}}',
				'location_ids'    => $locationIds[1]
			]);

			$response          = new stdClass();
			$response->success = true;
			$response->message = JText::_('PLG_SAMPLEDATA_DPCALENDAR_STEP4_SUCCESS');
		} catch (Exception $e) {
			$response            = [];
			$response['success'] = false;
			$response['message'] = JText::sprintf('PLG_SAMPLEDATA_DPCALENDAR_STEP_FAILED', 4, $e->getMessage());
		}

		return $response;
	}

	public function onAjaxSampledataApplyStep5()
	{
		if ($this->app->input->get('type') != $this->_name) {
			return;
		}

		try {
			$this->setUp();

			$locationIds = $this->app->getUserState('sampledata.dpcalendar.locations');

			$start = \DPCalendar\Helper\DPCalendarHelper::getDate();
			$start->setTime(8, 0, 0);
			$end = clone $start;
			$end->modify('+2 hours');

			$this->createEvent([
				'catid'        => 2,
				'title'        => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_10_TITLE',
				'rrule'        => 'FREQ=DAILY',
				'start_date'   => $start->toSql(),
				'end_date'     => $end->toSql(),
				'description'  => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_10_DESC',
				'rooms'        => $locationIds[6] . '-1',
				'location_ids' => $locationIds[6]
			]);
			$this->createEvent([
				'catid'        => 2,
				'title'        => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_11_TITLE',
				'rrule'        => 'FREQ=DAILY',
				'start_date'   => $start->toSql(),
				'end_date'     => $end->toSql(),
				'color'        => 'FF4557',
				'description'  => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_11_DESC',
				'rooms'        => $locationIds[6] . '-2',
				'location_ids' => $locationIds[6]
			]);
			$this->createEvent([
				'catid'        => 2,
				'title'        => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_12_TITLE',
				'rrule'        => 'FREQ=DAILY',
				'start_date'   => $start->toSql(),
				'end_date'     => $end->toSql(),
				'color'        => '056625',
				'description'  => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_12_DESC',
				'rooms'        => $locationIds[6] . '-3',
				'location_ids' => $locationIds[6]
			]);

			$response          = new stdClass();
			$response->success = true;
			$response->message = JText::_('PLG_SAMPLEDATA_DPCALENDAR_STEP5_SUCCESS');
		} catch (Exception $e) {
			$response            = [];
			$response['success'] = false;
			$response['message'] = JText::sprintf('PLG_SAMPLEDATA_DPCALENDAR_STEP_FAILED', 5, $e->getMessage());
		}

		return $response;
	}

	public function onAjaxSampledataApplyStep6()
	{
		if ($this->app->input->get('type') != $this->_name) {
			return;
		}

		try {
			$this->setUp();

			$fields    = [];
			$fieldData = $this->createCustomField([
				'title'   => 'PLG_SAMPLEDATA_DPCALENDAR_FIELD_1_TITLE',
				'label'   => 'PLG_SAMPLEDATA_DPCALENDAR_FIELD_1_TITLE',
				'type'    => 'text',
				'context' => 'com_dpcalendar.event'
			]);

			foreach ($fieldData as $code => $name) {
				$fields[$code][] = ['name' => $name, 'value' => 'PLG_SAMPLEDATA_DPCALENDAR_FIELD_1_VALUE'];
			}

			$fieldData = $this->createCustomField([
				'title'   => 'PLG_SAMPLEDATA_DPCALENDAR_FIELD_2_TITLE',
				'label'   => 'PLG_SAMPLEDATA_DPCALENDAR_FIELD_2_TITLE',
				'type'    => 'media',
				'context' => 'com_dpcalendar.event'
			]);

			foreach ($fieldData as $code => $name) {
				$fields[$code][] = ['name' => $name, 'value' => 'images/powered_by.png'];
			}

			$fieldData = $this->createCustomField([
				'title'   => 'PLG_SAMPLEDATA_DPCALENDAR_FIELD_3_TITLE',
				'label'   => 'PLG_SAMPLEDATA_DPCALENDAR_FIELD_3_TITLE',
				'type'    => 'dpcalendar',
				'context' => 'com_dpcalendar.event'
			]);

			foreach ($fieldData as $code => $name) {
				$fields[$code][] = ['name' => $name, 'value' => $this->app->getUserState('sampledata.dpcalendar.calendars', [])[1][$code]];
			}

			// Custom fields
			$this->createEvent([
				'catid'       => 0,
				'title'       => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_5_TITLE',
				'rrule'       => 'FREQ=WEEKLY;BYDAY=SU',
				'description' => 'PLG_SAMPLEDATA_DPCALENDAR_EVENT_5_DESC',
				'fields'      => $fields
			]);

			$response          = new stdClass();
			$response->success = true;
			$response->message = JText::_('PLG_SAMPLEDATA_DPCALENDAR_STEP6_SUCCESS');
		} catch (Exception $e) {
			$response            = [];
			$response['success'] = false;
			$response['message'] = JText::sprintf('PLG_SAMPLEDATA_DPCALENDAR_STEP_FAILED', 6, $e->getMessage());
		}

		return $response;
	}

	public function onAjaxSampledataApplyStep7()
	{
		if ($this->app->input->get('type') != $this->_name) {
			return;
		}

		try {
			$this->setUp();

			// Create tax rate
			$model = \JModelLegacy::getInstance('Country', 'DPCalendarModel', ['ignore_request' => true]);
			\JModelLegacy::getInstance('Taxrate', 'DPCalendarModel', ['ignore_request' => true])->save([
				'title'     => 'VAT',
				'rate'      => 10,
				'state'     => 1,
				'countries' => '{"countries0":{"country":"' . $model->getItem(['short_code' => 'US'])->id . '"}}'
			]);

			// Festival
			$this->createBooking([
				'name'      => 'Chuck Norris',
				'email'     => 'chuck@example.com',
				'country'   => 'US',
				'city'      => 'Texas',
				'latitude'  => 31.81603810,
				'longitude' => -99.51209860,
				'event_id'  => [4 => ['tickets' => [0 => 1]]]
			]);

			// Hike
			$this->createBooking([
				'name'      => 'Arnold Schwarzenegger',
				'email'     => 'arnold@example.com',
				'country'   => 'US',
				'city'      => 'Texas',
				'latitude'  => 31.81603810,
				'longitude' => -99.51209860,
				'event_id'  => [5 => ['tickets' => [0 => 2]]]
			]);

			// Swimming
			$this->createBooking([
				'name'      => 'Jean-Claude van Damme',
				'email'     => 'jean@example.com',
				'country'   => 'BE',
				'city'      => 'Sint-Agatha-Berchem',
				'latitude'  => 50.86492310,
				'longitude' => 4.29467340,
				'event_id'  => [6 => ['tickets' => [0 => 1]]]
			]);

			// Basketball
			$this->createBooking([
				'name'      => 'Bruce Lee',
				'email'     => 'lee@example.com',
				'country'   => 'US',
				'city'      => 'New York',
				'latitude'  => 40.71272810,
				'longitude' => -74.00601520,
				'event_id'  => [7 => ['tickets' => [0 => 2, 1 => 0, 2 => 1]]]
			]);

			$response            = [];
			$response['success'] = true;
			$response['message'] = JText::_('PLG_SAMPLEDATA_DPCALENDAR_STEP7_SUCCESS');
		} catch (Exception $e) {
			$response            = [];
			$response['success'] = false;
			$response['message'] = JText::sprintf('PLG_SAMPLEDATA_DPCALENDAR_STEP_FAILED', 7, $e->getMessage());
		}

		return $response;
	}

	public function onAjaxSampledataApplyStep8()
	{
		if ($this->app->input->get('type') != $this->_name) {
			return;
		}

		if (!JComponentHelper::isEnabled('com_menus')) {
			$response            = [];
			$response['success'] = true;
			$response['message'] = JText::sprintf('PLG_SAMPLEDATA_DPCALENDAR_STEP_SKIPPED', 8, 'com_menus');

			return $response;
		}

		try {
			$this->setUp();

			if (count($this->languageCache) > 1) {
				foreach ($this->languageCache as $code => $language) {
					$menuTable = JTable::getInstance('Type', 'JTableMenu');

					$menuTable->bind([
						'menutype'    => 'dpcalendar-' . $code,
						'title'       => 'DPCalendar ' . $code,
						'description' => ''
					]);

					$menuTable->store();
				}
			}

			$locationIds = $this->app->getUserState('sampledata.dpcalendar.locations');

			$this->createMenuItem([
				'title'  => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_1_TITLE',
				'link'   => 'view=calendar',
				'home'   => count($this->languageCache) > 1 ? 1 : 0,
				'params' => ['ids' => [0]]
			]);
			$this->createMenuItem([
				'title' => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_2_TITLE',
				'link'  => 'view=event&id=',
				'id'    => 4
			]);

			if (!DPCalendarHelper::isFree()) {
				$this->createMenuItem([
					'title'  => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_3_TITLE',
					'link'   => 'view=list',
					'params' => ['ids' => [1]]
				]);
				$this->createMenuItem([
					'title'  => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_4_TITLE',
					'link'   => 'view=list&layout=blog',
					'params' => ['ids' => [1]]
				]);
				$this->createMenuItem([
					'title'  => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_5_TITLE',
					'link'   => 'view=list&layout=timeline',
					'params' => ['ids' => [1]]
				]);
				$this->createMenuItem([
					'title'  => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_6_TITLE',
					'link'   => 'view=map',
					'params' => ['ids' => [0], 'map_view_radius' => '-1']
				]);
			}
			$this->createMenuItem([
				'title'  => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_7_TITLE',
				'link'   => 'view=locations',
				'params' => ['ids' => [$locationIds[0], $locationIds[1]]]
			]);
			$this->createMenuItem([
				'title' => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_8_TITLE',
				'link'  => 'view=location&id=' . $locationIds[6]
			]);

			if (!DPCalendarHelper::isFree()) {
				$this->createMenuItem([
					'title'  => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_9_TITLE',
					'link'   => 'view=calendar',
					'params' => [
						'ids'                       => [2],
						'show_selection'            => 0,
						'default_view'              => 'day',
						'header_show_month'         => 0,
						'header_show_week'          => 0,
						'header_show_day'           => 0,
						'header_show_list'          => 0,
						'calendar_filter_locations' => [$locationIds[6]],
						'calendar_resource_views'   => ['day']
					]
				]);
				$this->createMenuItem([
					'title' => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_10_TITLE',
					'link'  => 'view=bookings'
				]);
				$this->createMenuItem([
					'title' => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_11_TITLE',
					'link'  => 'view=tickets'
				]);
				$this->createMenuItem([
					'title'  => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_12_TITLE',
					'link'   => 'view=calendar',
					'params' => [
						'ids'            => [3, 4],
						'textbefore'     => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_12_TEXT',
						'show_selection' => 3,
						'map_zoom'       => 3
					]
				]);
				$this->createMenuItem([
					'title' => 'PLG_SAMPLEDATA_DPCALENDAR_MENU_ITEM_13_TITLE',
					'link'  => 'view=profile'
				]);
			}

			$response            = [];
			$response['success'] = true;
			$response['message'] = JText::_('PLG_SAMPLEDATA_DPCALENDAR_STEP8_SUCCESS');
		} catch (Exception $e) {
			$response            = [];
			$response['success'] = false;
			$response['message'] = JText::sprintf('PLG_SAMPLEDATA_DPCALENDAR_STEP_FAILED', 8, $e->getMessage());
		}

		return $response;
	}

	public function onAjaxSampledataApplyStep9()
	{
		if ($this->app->input->get('type') != $this->_name) {
			return;
		}

		if (!JComponentHelper::isEnabled('com_modules')) {
			$response            = array();
			$response['success'] = true;
			$response['message'] = JText::sprintf('PLG_SAMPLEDATA_DPCALENDAR_STEP_SKIPPED', 9, 'com_modules');

			return $response;
		}

		try {
			$this->setUp();

			if (count($this->languageCache) > 1) {
				// Menu module
				$this->createModule([
					'title'    => 'DPCalendar',
					'ordering' => 1,
					'module'   => 'mod_menu'
				]);
			}

			$this->createModule([
				'title'    => 'PLG_SAMPLEDATA_DPCALENDAR_MODULE_1_TITLE',
				'ordering' => 2,
				'module'   => 'mod_dpcalendar_counter',
				'params'   => ['ids' => [0], 'description_length' => '0']
			]);
			$this->createModule([
				'title'    => 'PLG_SAMPLEDATA_DPCALENDAR_MODULE_2_TITLE',
				'ordering' => 3,
				'module'   => 'mod_dpcalendar_upcoming',
				'params'   => ['ids' => [1], 'description_length' => '0']
			]);
			$this->createModule([
				'title'    => 'PLG_SAMPLEDATA_DPCALENDAR_MODULE_3_TITLE',
				'ordering' => 4,
				'module'   => 'mod_dpcalendar_map',
				'params'   => ['ids' => [1], 'radius' => '-1']
			]);
			$this->createModule([
				'title'    => 'PLG_SAMPLEDATA_DPCALENDAR_MODULE_4_TITLE',
				'ordering' => 5,
				'module'   => 'mod_dpcalendar_mini',
				'params'   => [
					'ids'               => [0],
					'header_show_month' => 0,
					'header_show_week'  => 0,
					'header_show_day'   => 0,
					'header_show_list'  => 0,
				]
			]);

			$response            = [];
			$response['success'] = true;
			$response['message'] = JText::_('PLG_SAMPLEDATA_DPCALENDAR_STEP9_SUCCESS');
		} catch (Exception $e) {
			$response            = [];
			$response['success'] = false;
			$response['message'] = JText::sprintf('PLG_SAMPLEDATA_DPCALENDAR_STEP_FAILED', 9, $e->getMessage());
		}

		return $response;
	}

	private function createCustomField($originalData)
	{
		$newIds = [];
		foreach ($this->languageCache as $code => $language) {
			$data             = $originalData;
			$data['id']       = 0;
			$data['name']     = strtolower('field-' . $code . '-' . preg_replace('/[^0-9,.]/', '', $data['title']));
			$data['title']    = $language->_($data['title']);
			$data['label']    = $language->_($data['label']);
			$data['language'] = count($this->languageCache) > 1 ? $code : '*';
			$data['state']    = 1;
			$data['access']   = (int)$this->app->get('access', 1);

			$model = JModelLegacy::getInstance('Field', 'FieldsModel');
			if (!$model->save($data)) {
				JFactory::getLanguage()->load('com_fields');
				throw new Exception(JText::_($model->getError()));
			}
			$newIds[$code] = $data['name'];
		}

		return $newIds;
	}

	private function createModule($originalData)
	{
		$calendarIds = $this->app->getUserState('sampledata.dpcalendar.calendars', []);
		foreach ($this->languageCache as $code => $language) {
			$data = $originalData;
			if (!empty($data['params']) && !empty($data['params']['ids'])) {
				foreach ($data['params']['ids'] as $index => $id) {
					$data['params']['ids'][$index] = $calendarIds[$id][$code];
				}
			}

			if (in_array($code, self::$europeanDateFormatLanguages)) {
				switch ($data['module']) {
					case 'mod_dpcalendar_map':
					case 'mod_dpcalendar_upcoming':
						$data['params']['date_format'] = 'd.m.Y';
						$data['params']['time_format'] = 'H:i';
						break;
					case 'mod_dpcalendar_upcoming':
						$data['params']['timeformat_month'] = 'H:i';
						$data['params']['timeformat_week']  = 'H:i';
						$data['params']['timeformat_day']   = 'H:i';
						$data['params']['timeformat_list']  = 'H:i';
						break;
				}
			}

			if ($data['module'] == 'mod_menu') {
				$data['params'] = ['menutype' => 'dpcalendar-' . $code];
			}

			$data['id']         = 0;
			$data['title']      = $language->_($data['title']);
			$data['asset_id']   = 0;
			$data['language']   = count($this->languageCache) > 1 ? $code : '*';
			$data['note']       = '';
			$data['published']  = 1;
			$data['assignment'] = 0;
			$data['access']     = (int)$this->app->get('access', 1);
			$data['client_id']  = 0;
			$data['position']   = 'position-7';

			$model = JModelLegacy::getInstance('Module', 'ModulesModel');
			if (!$model->save($data)) {
				JFactory::getLanguage()->load('com_modules');
				throw new Exception(JText::_($model->getError()));
			}
		}
	}

	private function createMenuItem($originalData)
	{
		$this->db->setQuery("select extension_id from #__extensions where name ='com_dpcalendar' and type = 'component'");
		$componentId = $this->db->loadResult();
		$calendarIds = $this->app->getUserState('sampledata.dpcalendar.calendars', []);
		$eventIds    = $this->app->getUserState('sampledata.dpcalendar.events', []);

		foreach ($this->languageCache as $code => $language) {
			$data = $originalData;
			if (!empty($data['params']) && !empty($data['params']['ids']) && $originalData['link'] != 'view=locations') {
				foreach ($data['params']['ids'] as $index => $id) {
					$data['params']['ids'][$index] = $calendarIds[$id][$code];
				}
			}
			if (!empty($data['params']) && !empty($data['params']['textbefore'])) {
				$data['params']['textbefore'] = $language->_($data['params']['textbefore']);
			}

			if (in_array($code, self::$europeanDateFormatLanguages)) {
				switch ($data['link']) {
					case 'view=calendar':
						$data['params']['timeformat_month'] = 'H:i';
						$data['params']['timeformat_week']  = 'H:i';
						$data['params']['timeformat_day']   = 'H:i';
						$data['params']['timeformat_list']  = 'H:i';
						$data['params']['weekstart']        = 1;
					case 'view=list':
					case 'view=list&layout=blog':
					case 'view=list&layout=timeline':
						$data['params']['list_title_format'] = 'd.m.Y';
					case 'view=map':
						$data['params']['map_date_format'] = 'H:i';
					case 'view=event&id=':
						$data['params']['event_date_format'] = 'd.m.Y';
						$data['params']['event_time_format'] = 'H:i';
						break;
				}
			}

			if (!empty($data['id'])) {
				$data['link'] .= $eventIds[$data['id']][$code];
				unset($data['id']);
			}

			// Set values which are always the same
			$data['id']              = 0;
			$data['title']           = $language->_($data['title']);
			$data['created_user_id'] = JFactory::getUser()->id;
			$data['alias']           = JApplicationHelper::stringURLSafe($data['title']);
			$data['link']            = 'index.php?option=com_dpcalendar&' . $data['link'];
			$data['menutype']        = count($this->languageCache) > 1 ? 'dpcalendar-' . $code : 'mainmenu';
			$data['component_id']    = $componentId;
			$data['language']        = count($this->languageCache) > 1 ? $code : '*';

			// Set unicodeslugs if alias is empty
			if (trim(str_replace('-', '', $data['alias']) == '')) {
				$unicode       = JFactory::getConfig()->set('unicodeslugs', 1);
				$data['alias'] = JApplicationHelper::stringURLSafe($data['title']);
				JFactory::getConfig()->set('unicodeslugs', $unicode);
			}

			$data['published']         = 1;
			$data['note']              = '';
			$data['img']               = '';
			$data['associations']      = [];
			$data['client_id']         = 0;
			$data['level']             = 1;
			$data['browserNav']        = 0;
			$data['access']            = (int)$this->app->get('access', 1);
			$data['type']              = 'component';
			$data['template_style_id'] = 0;
			$data['parent_id']         = 1;

			$model = JModelLegacy::getInstance('Item', 'MenusModel', ['ignore_request' => true]);
			if (!$model->save($data)) {
				// When not fully translated we can have duplicates on the alias
				$data['alias'] = $data['alias'] . '-' . $code;
				if (!$model->save($data)) {
					throw new Exception($data['title'] . ' => ' . $data['alias'] . ' : ' . $model->getError());
				}
			}
		}
	}

	private function createBooking($originalData)
	{
		if (DPCalendarHelper::isFree()) {
			return;
		}

		$eventIds = $this->app->getUserState('sampledata.dpcalendar.events', []);

		$originalData['user_id'] = JFactory::getUser()->id;
		foreach ($this->languageCache as $code => $language) {
			$data = $originalData;
			foreach ($data['event_id'] as $eventId => $tickets) {
				$data['event_id'][$this->getEvent($eventIds[$eventId][$code])->id] = $tickets;
				unset($data['event_id'][$eventId]);
			}

			$model           = \JModelLegacy::getInstance('Country', 'DPCalendarModel', ['ignore_request' => true]);
			$data['country'] = $model->getItem(['short_code' => $data['country']])->id;

			$model = JModelLegacy::getInstance('Booking', 'DPCalendarModel', ['ignore_request' => true]);

			if (!$model->save($data)) {
				throw new Exception(JText::_($model->getError()));
			}

			// Create only one booking
			break;
		}
	}

	private function createEvent($originalData)
	{
		if (!empty($originalData['location_ids']) && !is_array($originalData['location_ids'])) {
			$originalData['location_ids'] = [$originalData['location_ids']];
		}
		$originalData['state']  = 1;
		$originalData['access'] = (int)$this->app->get('access', 1);

		if (!isset($originalData['all_day'])) {
			$originalData['all_day'] = 0;
		}

		if (!array_key_exists('capacity', $originalData)) {
			$originalData['capacity'] = 0;
		}

		if (DPCalendarHelper::isFree() && !empty($originalData['rrule'])) {
			unset($originalData['rrule']);
		}

		if (!empty($originalData['rrule'])) {
			$until = \DPCalendar\Helper\DPCalendarHelper::getDate();
			$until->modify('+' . $this->params->get('until', '6 month'));
			$originalData['rrule'] .= ';UNTIL=' . $until->format('Ymd\T235900');
		}

		if (empty($originalData['start_date'])) {
			$start = \DPCalendar\Helper\DPCalendarHelper::getDate();
			if (!DPCalendarHelper::isFree()) {
				$start->modify('-1 month');
			} else {
				$start->modify('+' . rand(1, 7) . ' day');
			}
			$start->modify('+1 hour');
			$start->setTime($start->format('H'), 0, 0);

			$end = clone $start;
			$end->modify('+2 hours');

			$originalData['start_date'] = $start->toSql();
			$originalData['end_date']   = $end->toSql();
		}

		$newIds      = [];
		$calendarIds = $this->app->getUserState('sampledata.dpcalendar.calendars', []);
		foreach ($this->languageCache as $code => $language) {
			$data                = $originalData;
			$data['catid']       = $calendarIds[$originalData['catid']][$code];
			$data['title']       = $language->_($data['title']);
			$data['description'] = '<p>' . $language->_($data['description']) . '</p>';
			$data['description'] .= self::$lorem;

			$data['language'] = count($this->languageCache) > 1 ? $code : '*';

			if (!empty($data['fields'])) {
				$data['com_fields'] = [];
				foreach ($data['fields'] as $fieldCode => $fields) {
					foreach ($fields as $field) {
						if ($fieldCode != $code) {
							continue;
						}

						$data['com_fields'][$field['name']] = $language->_($field['value']);
					}
				}
				unset($data['fields']);
			}

			$model = JModelLegacy::getInstance('AdminEvent', 'DPCalendarModel', ['ignore_request' => true]);
			if (!$model->save($data)) {
				throw new Exception(JText::_($model->getError()));
			}

			$newIds[$code] = $model->getItem()->id;
		}

		$ids   = $this->app->getUserState('sampledata.dpcalendar.events', []);
		$ids[] = $newIds;
		$this->app->setUserState('sampledata.dpcalendar.events', $ids);
	}

	private function createLocation($data)
	{
		$model = JModelLegacy::getInstance('Location', 'DPCalendarModel');

		$data['state']    = 1;
		$data['access']   = (int)$this->app->get('access', 1);
		$data['language'] = '*';

		if (!$model->save($data)) {
			throw new Exception(JText::_($model->getError()));
		}

		return $model->getItem()->id;
	}

	private function createCalendar($calendarTitle)
	{
		$newIds = [];
		foreach ($this->languageCache as $code => $language) {
			$title = $language->_($calendarTitle);
			$alias = JApplicationHelper::stringURLSafe($title);

			// Set unicodeslugs if alias is empty
			if (trim(str_replace('-', '', $alias) == '')) {
				$unicode = JFactory::getConfig()->set('unicodeslugs', 1);
				$alias   = JApplicationHelper::stringURLSafe($title);
				JFactory::getConfig()->set('unicodeslugs', $unicode);
			}

			$calendar = [
				'title'           => $title,
				'parent_id'       => 1,
				'id'              => 0,
				'published'       => 1,
				'access'          => (int)$this->app->get('access', 1),
				'created_user_id' => JFactory::getUser()->id,
				'extension'       => 'com_dpcalendar',
				'level'           => 1,
				'alias'           => $code . '-' . $alias,
				'associations'    => [],
				'description'     => '',
				'language'        => count($this->languageCache) > 1 ? $code : '*',
				'params'          => '',
			];

			$model = JModelLegacy::getInstance('Category', 'CategoriesModel');

			if (!$model->save($calendar)) {
				throw new Exception($model->getError());
			}

			$newIds[$code] = $model->getItem()->id;
		}

		$ids   = $this->app->getUserState('sampledata.dpcalendar.calendars', []);
		$ids[] = $newIds;
		$this->app->setUserState('sampledata.dpcalendar.calendars', $ids);
	}

	private function createExternalCalendar($originalData)
	{
		$newIds = [];
		foreach ($this->languageCache as $code => $language) {
			$data = $originalData;

			$data['title']    = $language->_($data['title']) . (count($this->languageCache) > 1 ? ' (' . $code . ')' : '');
			$data['language'] = count($this->languageCache) > 1 ? $code : '*';
			$data['state']    = 1;

			$model = JModelLegacy::getInstance('Extcalendar', 'DPCalendarModel', ['ignore_request' => true]);
			if (!$model->save($data)) {
				throw new Exception(JText::_($model->getError()));
			}

			$newIds[$code] = ($originalData['plugin'] == 'ical' ? 'i-' : 'g-') . $model->getItem()->id;
		}

		$ids   = $this->app->getUserState('sampledata.dpcalendar.calendars', []);
		$ids[] = $newIds;
		$this->app->setUserState('sampledata.dpcalendar.calendars', $ids);
	}

	private function createPrivateCalendar($originalData)
	{
		$data = $originalData;

		// We use here JText because we create only one calendar
		$data['displayname'] = JText::_($data['displayname']);

		$model = JModelLegacy::getInstance('Davcalendar', 'DPCalendarModel', ['ignore_request' => true]);
		if (!$model->save($data)) {
			throw new Exception(JText::_($model->getError()));
		}

		$id = $model->getItem()->id;

		$newIds = [];
		foreach ($this->languageCache as $code => $language) {
			$newIds[$code] = 'cd-' . $id;
		}

		$ids   = $this->app->getUserState('sampledata.dpcalendar.calendars', []);
		$ids[] = $newIds;
		$this->app->setUserState('sampledata.dpcalendar.calendars', $ids);
	}

	private function getEvent($id)
	{
		$start = \DPCalendar\Helper\DPCalendarHelper::getDate('+1 day');
		$model = JModelLegacy::getInstance('Adminevents', 'DPCalendarModel', ['ignore_request' => true]);
		$model->setState('filter.children', $id);
		$model->setState('filter.search_start', $start->format(DPCalendarHelper::getComponentParameter('event_form_date_format', 'm.d.Y')));
		$model->setState('list.limit', 1);

		$events = $model->getItems();
		if (!$events) {
			throw new Exception('No event found!');
		}

		return reset($events);
	}

	private function setUp()
	{
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/', 'DPCalendarModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables/');
		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models/', 'DPCalendarModel');
		JTable::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/tables/');

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models/', 'CategoriesModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/tables/');

		JLoader::register('MenusHelper', JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php');
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_menus/models/', 'MenusModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_menus/tables/');

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_modules/models/', 'ModulesModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_modules/tables/');

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fields/models/', 'FieldsModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fields/tables/');

		JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
		foreach (JLanguageHelper::getContentLanguages() as $language) {
			$lang = JLanguage::getInstance($language->lang_code);
			$lang->load('plg_sampledata_' . $this->_name, JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name);

			$this->languageCache[$language->lang_code] = $lang;
		}

		// Disable mail
		JFactory::getConfig()->set('mailonline', false);
	}

	private function clearTable($name)
	{
		$this->db->setQuery('truncate #__dpcalendar_' . $name);
		$this->db->execute();
	}
}
