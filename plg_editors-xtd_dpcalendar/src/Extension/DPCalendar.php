<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2026 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\EditorsXtd\DPCalendar\Extension;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Document\HtmlDocument;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Editor\Button\Button;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

class DPCalendar extends CMSPlugin implements SubscriberInterface
{
	protected $autoloadLanguage = true;

	public static function getSubscribedEvents(): array
	{
		return ['onEditorButtonsSetup' => 'onEditorButtonsSetup'];
	}

	public function onEditorButtonsSetup(EditorButtonsSetupEvent $event): void
	{
		$subject  = $event->getButtonsRegistry();
		$disabled = $event->getDisabledButtons();

		if (\in_array($this->_name, $disabled)) {
			return;
		}

		if ($button = $this->onDisplay($event->getEditorId())) {
			$subject->add($button);
		}
	}

	public function onDisplay(string $name): mixed
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSWebApplicationInterface) {
			return null;
		}

		$doc = new HtmlDocument($app);
		$doc->loadScriptFile('default.js', 'plg_editors-xtd_dpcalendar');
		$doc->addScriptOptions('editor.button', $name);

		$props = [
			'modal'   => true,
			'link'    => 'index.php?option=com_dpcalendar&view=events&layout=modal&tmpl=component&' . $app->getFormToken() . '=1',
			'text'    => $app->getLanguage()->_('PLG_EDITORS-XTD_DPCALENDAR_BUTTON_TEXT'),
			'name'    => $this->_type . '_' . $this->_name,
			'icon'    => 'calendar fa fa-calendar-alt',
			'iconSVG' => $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Layout', 'Administrator')
							->renderLayout('block.icon', ['icon' => Icon::CALENDAR, 'raw' => true])
		];

		$options = [
			'height'     => '400px',
			'width'      => '800px',
			'bodyHeight' => '70',
			'modalWidth' => '80'
		];

		if (version_compare(JVERSION, '5.0.0', 'lt')) {
			// @phpstan-ignore-next-line
			return new CMSObject(array_merge($props, ['options' => $options]));
		}

		return new Button($this->_name, $props, $options);
	}
}
