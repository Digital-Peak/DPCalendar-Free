<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\View;

defined('_JEXEC') or die();

use DPCalendar\Router\Router;
use DPCalendar\Translator\Translator;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Input\Input;
use Joomla\Registry\Registry;

class BaseView extends HtmlView
{
	protected $state;
	protected $params;

	/** @var Input */
	protected $input = null;

	/** @var CMSApplication */
	protected $app = null;

	/** @var User */
	protected $user = null;

	/** @var Translator */
	protected $translator = null;

	/** @var Router */
	protected $router = null;

	public function display($tpl = null)
	{
		$this->app   = Factory::getApplication();
		$this->input = $this->app->input;
		$this->user  = Factory::getUser();
		$this->tmpl  = $this->input->getCmd('tmpl') ? '&tmpl=' . $this->input->getCmd('tmpl') : '';

		$state = $this->get('State');

		if ($state === null) {
			$state = new Registry();
		}

		$this->state  = $state;
		$this->params = $state->get('params', new Registry());

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
			throw new \Exception(implode("\n", $errors), 500);
		}

		if ($this->getModel() instanceof FormModel) {
			HTMLHelper::_('behavior.keepalive');
			HTMLHelper::_('behavior.formvalidator');

			if ($this->params->get('save_history') && \DPCalendar\Helper\DPCalendarHelper::isJoomlaVersion('4', '<')) {
				HTMLHelper::_('behavior.modal', 'a.modal_jform_contenthistory');
			}

			if ($this->app->isClient('administrator') && \DPCalendar\Helper\DPCalendarHelper::isJoomlaVersion('4', '<')) {
				HTMLHelper::_('behavior.tabstate');
			}
		}

		if ($this->app->isClient('site')) {
			$this->prepareDocument();
		} else {
			$this->addToolbar();

			// Only render the sidebar when we are not editing a form, modal or Joomla 4
			if (!($this->getModel() instanceof AdminModel)
				&& $this->input->get('tmpl') != 'component'
				&& \DPCalendar\Helper\DPCalendarHelper::isJoomlaVersion('4', '<')) {
				$this->sidebar = \JHtmlSidebar::render();
			} else {
				$this->sidebar = null;
			}
			$this->filterForm    = $this->get('FilterForm');
			$this->activeFilters = $this->get('ActiveFilters');
		}

		parent::display($tpl);
	}

	/**
	 * Prepares the document by adding some meta information and defining some view specific values.
	 */
	protected function prepareDocument()
	{
		$menus = $this->app->getMenu();

		// Because the application sets a default page title, we need to get it from the menu item itself
		$menu = $menus->getActive();
		if ($menu) {
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		} else {
			$this->params->def('page_heading', $this->translate('COM_DPCALENDAR_DEFAULT_PAGE_TITLE'));
		}

		// Check for empty title and add site name if param is set
		$title = $this->getDocumentTitle();
		if ($this->app->get('sitename_pagetitles', 0) == 1) {
			$title = \JText::sprintf('JPAGETITLE', $this->app->get('sitename'), $title);
		}
		if ($this->app->get('sitename_pagetitles', 0) == 2) {
			$title = \JText::sprintf('JPAGETITLE', $title, $this->app->get('sitename'));
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

	/**
	 * Adds some default actions to the toolbar in the back end.
	 *
	 * @throws \Exception
	 */
	protected function addToolbar()
	{
		$canDo = \DPCalendarHelper::getActions();

		if (empty($this->title)) {
			$this->title = 'COM_DPCALENDAR_MANAGER_' . strtoupper($this->getName());
		}
		if (empty($this->icon)) {
			$this->icon = strtolower($this->getName());
		}
		ToolbarHelper::title($this->translate($this->title), $this->icon);
		$this->document->addStyleDeclaration(
			'.icon-48-' . $this->icon .
			' {background-image: url(../media/com_dpcalendar/images/admin/48-' . $this->icon . '.png);background-repeat: no-repeat;}'
		);

		if ($canDo->get('core.admin', 'com_dpcalendar') && !($this->getModel() instanceof FormModel)) {
			ToolbarHelper::preferences('com_dpcalendar');
			ToolbarHelper::divider();
		}

		PluginHelper::importPlugin('dpcalendar');
		$this->app->triggerEvent('onDPCalendarAfterToolbar', [$this->getName(), Toolbar::getInstance('toolbar')]);
	}

	/**
	 * Function to initialize the view. Can throw an Exception to abort the display.
	 *
	 * @throws \Exception
	 */
	protected function init()
	{
	}

	/**
	 * Translate the given key.
	 *
	 * @param $key
	 *
	 * @return string
	 */
	protected function translate($key)
	{
		return $this->translator->translate($key);
	}

	/**
	 * Performs some checks when no access is detected by the view.
	 *
	 * @return bool
	 */
	protected function handleNoAccess()
	{
		if (!$this->user->guest) {
			throw new \Exception($this->translate('COM_DPCALENDAR_ALERT_NO_AUTH'), 403);
		}

		$active = $this->app->getMenu()->getActive();
		$link   = new Uri(Route::_('index.php?option=com_users&view=login&Itemid=' . $active->id, false));
		$link->setVar('return', base64_encode('index.php?Itemid=' . $active->id));

		$this->app->enqueueMessage($this->translate('COM_DPCALENDAR_NOT_LOGGED_IN'), 'warning');
		$this->app->redirect(Route::_($link));

		return false;
	}

	protected function getDocumentTitle()
	{
		$title = $this->params->get('page_title', '');

		if (empty($title)) {
			$title = $this->app->get('sitename');
		}

		return $title;
	}
}
