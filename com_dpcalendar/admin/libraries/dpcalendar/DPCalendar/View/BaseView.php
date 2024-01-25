<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\View;

defined('_JEXEC') or die();

use DPCalendar\Helper\DateHelper;
use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\Helper\LayoutHelper;
use DPCalendar\Helper\UserHelper;
use DPCalendar\HTML\Document\HtmlDocument;
use DPCalendar\Router\Router;
use DPCalendar\Translator\Translator;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
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
	/** @var string */
	public $tmpl;

	/** @var HtmlDocument */
	public $dpdocument;

	/** @var LayoutHelper */
	public $layoutHelper;

	/** @var UserHelper */
	public $userHelper;

	/** @var array */
	public $displayData;

	public $document;
	public $sidebar;

	/** @var Form $filterForm */
	public $filterForm;

	public $activeFilters;

	/** @var string */
	public $pageclass_sfx;
	public $title;
	public $icon;
	public $_charset;
	protected $state;
	protected $params;

	/** @var Input */
	protected $input;

	/** @var CMSApplication */
	protected $app;

	/** @var User */
	protected $user;

	/** @var Translator */
	protected $translator;

	/** @var Router */
	protected $router;

	/** @var DateHelper */
	protected $dateHelper;

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

		$this->dpdocument = new HtmlDocument();
		$this->translator = new Translator();
		$this->dateHelper = new DateHelper();
		$this->dateHelper->setTranslator($this->translator);

		$this->layoutHelper = new LayoutHelper();
		$this->userHelper   = new UserHelper();
		$this->router       = new Router();
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

		if ($this->app instanceof AdministratorApplication) {
			$this->filterForm    = $this->get('FilterForm');
			$this->activeFilters = $this->get('ActiveFilters');
		}

		try {
			$this->init();
		} catch (\Exception $exception) {
			$this->app->enqueueMessage($exception->getMessage(), 'error');
			if ($exception->getCode()) {
				$this->app->setHeader('status', $exception->getCode(), true);
			}

			return false;
		}

		if ($errors = $this->getErrors()) {
			throw new \Exception(implode("\n", $errors), 500);
		}

		if ($this->getModel() instanceof FormModel) {
			HTMLHelper::_('behavior.keepalive');
			HTMLHelper::_('behavior.formvalidator');

			if ($this->params->get('save_history') && DPCalendarHelper::isJoomlaVersion('4', '<')) {
				HTMLHelper::_('behavior.modal', 'a.modal_jform_contenthistory');
			}

			if ($this->app->isClient('administrator')) {
				if (DPCalendarHelper::isJoomlaVersion('4', '<')) {
					HTMLHelper::_('behavior.tabstate');
				} else {
					HTMLHelper::_('jquery.framework');
					HTMLHelper::_('behavior.polyfill', ['filter', 'xpath']);
					HTMLHelper::_('script', 'legacy/tabs-state.js', ['version' => 'auto', 'relative' => true]);
				}
			}
		}

		if ($this->app->isClient('site')) {
			$this->prepareDocument();
		} else {
			$this->addToolbar();

			// Add colum select scripts on Joomla 4 lists
			if (DPCalendarHelper::isJoomlaVersion('4', '>=')) {
				$this->document->getWebAssetManager()->useScript('table.columns');
			}

			// Only render the sidebar when we are not editing a form, modal or Joomla 4
			if (!($this->getModel() instanceof AdminModel)
				&& $this->input->get('tmpl') != 'component'
				&& DPCalendarHelper::isJoomlaVersion('4', '<')) {
				$this->sidebar = Sidebar::render();
			} else {
				$this->sidebar = null;
			}
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
			$title = Text::sprintf('JPAGETITLE', $this->app->get('sitename'), $title);
		}
		if ($this->app->get('sitename_pagetitles', 0) == 2) {
			$title = Text::sprintf('JPAGETITLE', $title, $this->app->get('sitename'));
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
		$this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx', ''));
	}

	/**
	 * Adds some default actions to the toolbar in the back end.
	 *
	 * @throws \Exception
	 */
	protected function addToolbar()
	{
		if (DPCalendarHelper::isJoomlaVersion('4', '>=') && $this->getModel() instanceof AdminModel) {
			Toolbar::getInstance()->inlinehelp();
		}

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

	public function escape($var)
	{
		if ($var === null) {
			return '';
		}

		return htmlspecialchars($var, ENT_QUOTES, $this->_charset);
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
			return $this->app->get('sitename');
		}

		return $title;
	}
}
