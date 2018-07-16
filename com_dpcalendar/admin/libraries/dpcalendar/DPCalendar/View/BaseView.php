<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace DPCalendar\View;

defined('_JEXEC') or die();

use Joomla\Registry\Registry;

class BaseView extends \JViewLegacy
{
	protected $state;
	protected $params;
	protected $input = null;

	/**
	 * @var \JApplicationCms
	 */
	protected $app = null;

	/**
	 * @var \JUser
	 */
	protected $user = null;

	public function display($tpl = null)
	{
		$this->app   = \JFactory::getApplication();
		$this->input = $this->app->input;
		$this->user  = \JFactory::getUser();

		$state = $this->get('State');

		if ($state === null) {
			$state = new Registry();
		}

		$tmp = clone $state->get('params', new Registry());
		if (method_exists($this->app, 'getParams')) {
			$tmp->merge($this->app->getParams('com_dpcalendar'));
		} else {
			$tmp->merge(\JComponentHelper::getParams('com_dpcalendar'));
		}

		$this->state  = $state;
		$this->params = $tmp;

		$this->dpdocument   = new \DPCalendar\HTML\Document\HtmlDocument();
		$this->dateHelper   = new \DPCalendar\Helper\DateHelper();
		$this->layoutHelper = new \DPCalendar\Helper\LayoutHelper();
		$this->userHelper   = new \DPCalendar\Helper\UserHelper();
		$this->router       = new \DPCalendar\Router\Router();
		$this->translator   = new \DPCalendar\Translator\Translator();
		$this->input        = $this->app->input;

		// The display data
		$this->displayData = [
			'document'     => $this->dpdocument,
			'layoutHelper' => $this->layoutHelper,
			'userHelper'   => $this->userHelper,
			'dateHelper'   => $this->dateHelper,
			'translator'   => $this->translator,
			'router'       => $this->router,
			'input'        => $this->input,
			'params'       => $this->params
		];

		try {
			$this->init();
		} catch (\Exception $e) {
			$this->app->enqueueMessage($e->getMessage(), 'error');
			if ($e->getCode()) {
				$this->app->setHeader('status', $e->getCode(), true);
			}

			return false;
		}

		if ($errors = $this->getErrors()) {
			\JError::raiseError(500, implode("\n", $errors));

			return false;
		}

		if ($this->app->isSite()) {
			$this->prepareDocument();
		} else {
			$this->addToolbar();

			// Only render the sidebar when we are not editing a form
			if (!($this->getModel() instanceof \JModelAdmin) && $this->input->get('tmpl') != 'component') {
				$this->sidebar       = \JHtmlSidebar::render();
				$this->filterForm    = $this->get('FilterForm');
				$this->activeFilters = $this->get('ActiveFilters');
			}
		}

		parent::display($tpl);
	}

	protected function prepareDocument()
	{
		$menus = $this->app->getMenu();
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();
		if ($menu) {
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		} else {
			$this->params->def('page_heading', \JText::_('COM_DPCALENDAR_DEFAULT_PAGE_TITLE'));
		}

		$title = $this->params->get('page_title', '');
		if (empty($title)) {
			$title = $this->app->getCfg('sitename');
		} else {
			if ($this->app->getCfg('sitename_pagetitles', 0) == 1) {
				$title = \JText::sprintf('JPAGETITLE', $this->app->getCfg('sitename'), $title);
			} else {
				if ($this->app->getCfg('sitename_pagetitles', 0) == 2) {
					$title = \JText::sprintf('JPAGETITLE', $title, $this->app->getCfg('sitename'));
				}
			}
		}
		$this->document->setTitle($title);

		if ($this->params->get('menu-meta_description')) {
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords')) {
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots')) {
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}

		// Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx'));
	}


	protected function addToolbar()
	{
		$canDo = \DPCalendarHelper::getActions();

		if (empty($this->title)) {
			$this->title = 'COM_DPCALENDAR_MANAGER_' . strtoupper($this->getName());
		}
		if (empty($this->icon)) {
			$this->icon = strtolower($this->getName());
		}
		\JToolbarHelper::title(\JText::_($this->title), $this->icon);
		\JFactory::getDocument()->addStyleDeclaration(
			'.icon-48-' . $this->icon .
			' {background-image: url(../media/com_dpcalendar/images/admin/48-' . $this->icon . '.png);background-repeat: no-repeat;}'
		);

		if ($canDo->get('core.admin', 'com_dpcalendar')) {
			\JToolbarHelper::preferences('com_dpcalendar');
			\JToolbarHelper::divider();
		}
	}

	/**
	 * Function to initialize the view. Can throw an Exception to abort the display.
	 *
	 * @throws \Exception
	 */
	protected function init()
	{
	}

	protected function translate($key)
	{
		return $this->translator->translate($key);
	}
}
