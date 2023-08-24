<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

if (version_compare(JVERSION, 4, '<') && !class_exists('\\Joomla\\CMS\\Form\\Field\\ListField', false)) {
	FormHelper::loadFieldClass('list');
	class_alias('JFormFieldList', '\\Joomla\\CMS\\Form\\Field\\ListField');
}

class JFormFieldDPPaymentprovider extends ListField
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
