<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

\defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

class DppaymentproviderField extends ListField
{
	public $type = 'DPPaymentprovider';

	protected function getOptions()
	{
		$options = parent::getOptions();

		PluginHelper::importPlugin('dpcalendarpay');

		foreach (Factory::getApplication()->triggerEvent('onDPPaymentProviders') as $pluginProviders) {
			foreach ($pluginProviders as $provider) {
				$options[] = HTMLHelper::_(
					'select.option',
					$provider->id,
					Text::_('PLG_' . strtoupper($provider->plugin_type . '_' . $provider->plugin_name) . '_TITLE')
					. ' - ' . Text::_($provider->title)
				);
			}
		}

		return $options;
	}
}
