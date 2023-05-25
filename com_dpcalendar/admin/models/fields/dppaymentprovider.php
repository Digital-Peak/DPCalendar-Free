<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JFormHelper::loadFieldClass('list');

class JFormFieldDPPaymentprovider extends JFormFieldList
{
	public $type = 'DPPaymentprovider';

	protected function getOptions()
	{
		$options = parent::getOptions();

		\JPluginHelper::importPlugin('dpcalendarpay');

		foreach (\JFactory::getApplication()->triggerEvent('onDPPaymentProviders') as $pluginProviders) {
			foreach ($pluginProviders as $provider) {
				$options[] = \Joomla\CMS\HTML\HTMLHelper::_(
					'select.option',
					$provider->id,
					\Joomla\CMS\Language\Text::_('PLG_' . strtoupper($provider->plugin_type . '_' . $provider->plugin_name) . '_TITLE')
					. ' - ' . \Joomla\CMS\Language\Text::_($provider->title)
				);
			}
		}

		return $options;
	}
}
