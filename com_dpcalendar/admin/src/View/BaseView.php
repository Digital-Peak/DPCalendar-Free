<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DateHelper;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Document\HtmlDocument;
use DigitalPeak\Component\DPCalendar\Administrator\Model\LayoutModel;
use DigitalPeak\Component\DPCalendar\Administrator\Router\Router;
use DigitalPeak\Component\DPCalendar\Administrator\Translator\Translator;
use Exception;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
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
	/** @var Form $filterForm */
	public $filterForm;

	/** @var array */
	public $activeFilters;

	/** @var string */
	protected $title;

	/** @var string */
	protected $icon;

	/** @var string */
	protected $tmpl;

	/** @var HtmlDocument */
	protected $dpdocument;

	/** @var LayoutModel */
	protected $layoutHelper;

	/** @var array */
	protected $displayData;

	/** @var string */
	protected $pageclass_sfx;

	/** @var Registry */
	protected $state;

	/** @var Registry */
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

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		if (!$app instanceof CMSApplication) {
			throw new \Exception('Invalid app defined');
		}

		$this->app   = $app;
		$this->input = $this->app->getInput();
		$this->user  = $this->getCurrentUser();
		$this->tmpl  = $this->input->get('tmpl') ? '&tmpl=' . $this->input->get('tmpl', '') : '';

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

		$this->layoutHelper = $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Layout', 'Administrator');
		$this->router       = new Router();
		$this->input        = $this->app->getInput();

		// The display data
		$this->displayData = [
			'document'     => $this->dpdocument,
			'layoutHelper' => $this->layoutHelper,
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

			return;
		}

		// @phpstan-ignore-next-line
		if ($errors = $this->getErrors()) {
			throw new \Exception(implode("\n", $errors), 500);
		}

		if ($this->getModel() instanceof FormModel) {
			HTMLHelper::_('behavior.keepalive');
			HTMLHelper::_('behavior.formvalidator');


			if ($this->app->isClient('administrator')) {
				HTMLHelper::_('jquery.framework');
				HTMLHelper::_('behavior.polyfill', ['filter', 'xpath']);
				HTMLHelper::_('script', 'legacy/tabs-state.js', ['version' => 'auto', 'relative' => true]);
			}
		}

		if ($this->app->isClient('site')) {
			$this->prepareDocument();
		} else {
			$this->addToolbar();
			$this->getDocument()->getWebAssetManager()->useScript('table.columns');
		}

		parent::display($tpl);
	}

	/**
	 * Prepares the document by adding some meta information and defining some view specific values.
	 */
	protected function prepareDocument(): void
	{
		// Because the application sets a default page title, we need to get it from the menu item itself
		$active = $this->app->getMenu()->getActive();
		$this->params->def(
			'page_heading',
			$active === null ? $this->translate('COM_DPCALENDAR_DEFAULT_PAGE_TITLE') : $this->params->get('page_title', $active->title)
		);

		// Check for empty title and add site name if param is set
		$title = $this->getDocumentTitle();
		if ($this->app->get('sitename_pagetitles', 0) == 1) {
			$title = Text::sprintf('JPAGETITLE', $this->app->get('sitename'), $title);
		}
		if ($this->app->get('sitename_pagetitles', 0) == 2) {
			$title = Text::sprintf('JPAGETITLE', $title, $this->app->get('sitename'));
		}

		$this->getDocument()->setTitle($title);

		if ($this->params->get('menu-meta_description')) {
			$this->getDocument()->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords')) {
			$this->getDocument()->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots')) {
			$this->getDocument()->setMetadata('robots', $this->params->get('robots'));
		}

		// Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars((string)$this->params->get('pageclass_sfx', ''));
	}

	/**
	 * Adds some default actions to the toolbar in the back end.
	 *
	 * @throws \Exception
	 */
	protected function addToolbar(): void
	{
		// @phpstan-ignore-next-line
		$toolbar = Toolbar::getInstance('toolbar');

		if ($this->getModel() instanceof AdminModel) {
			$toolbar->inlinehelp();
		}

		$canDo = ContentHelper::getActions('com_dpcalendar');

		if (empty($this->title)) {
			$this->title = 'COM_DPCALENDAR_MANAGER_' . strtoupper($this->getName());
		}
		if (empty($this->icon)) {
			$this->icon = strtolower($this->getName());
		}
		ToolbarHelper::title($this->translate($this->title), $this->icon);
		$this->getDocument()->getWebAssetManager()->addInlineStyle(
			'.icon-48-' . $this->icon .
			' {background-image: url(../media/com_dpcalendar/images/admin/48-' . $this->icon . '.png);background-repeat: no-repeat;}'
		);

		if ($canDo->get('core.admin', 'com_dpcalendar') && !($this->getModel() instanceof FormModel)) {
			ToolbarHelper::preferences('com_dpcalendar');
			ToolbarHelper::divider();
		}

		PluginHelper::importPlugin('dpcalendar');
		$this->app->triggerEvent('onDPCalendarAfterToolbar', [$this->getName(), $toolbar]);
	}

	/**
	 * Function to initialize the view. Can throw an Exception to abort the display.
	 *
	 * @throws \Exception
	 */
	protected function init(): void
	{
	}

	/**
	 * Translate the given key.
	 */
	protected function translate(string $key): string
	{
		return $this->translator->translate($key);
	}

	/**
	 * Performs some checks when no access is detected by the view.
	 */
	protected function handleNoAccess(): bool
	{
		if ($this->user->guest === 0) {
			throw new \Exception($this->translate('COM_DPCALENDAR_ALERT_NO_AUTH'), 403);
		}

		$active = $this->app->getMenu()->getActive();
		if ($active === null) {
			return false;
		}

		$link = new Uri(Route::_('index.php?option=com_users&view=login&Itemid=' . $active->id, false));
		$link->setVar('return', base64_encode('index.php?Itemid=' . $active->id));

		$this->app->enqueueMessage($this->translate('COM_DPCALENDAR_NOT_LOGGED_IN'), 'warning');
		$this->app->redirect(Route::_($link));

		return false;
	}

	protected function getDocumentTitle(): string
	{
		$title = $this->params->get('page_title', '');

		if (empty($title)) {
			return $this->app->get('sitename');
		}

		return $title;
	}
}
