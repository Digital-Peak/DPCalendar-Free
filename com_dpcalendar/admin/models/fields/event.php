<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die();

JLoader::import('joomla.form.formfield');
JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

class JFormFieldEvent extends JFormField
{
	protected $type = 'Event';

	protected function getInput()
	{
		if (\DPCalendar\Helper\DPCalendarHelper::isJoomlaVersion('4', '>=')) {
			return $this->getJ4Input();
		}

		return $this->getJ3Input();
	}

	protected function getJ3Input()
	{
		// Load modal behavior
		JHtml::_('behavior.modal', 'a.modal');

		// Build the script
		$script   = [];
		$script[] = '    function jSelectEvent_' . $this->id . '(id, title, object) {';
		$script[] = '        document.getElementById("' . $this->id . '_id").value = id;';
		$script[] = '        document.getElementById("' . $this->id . '_name").value = title;';
		$script[] = '        jQuery("#' . $this->id . '_id").trigger("change");';
		$script[] = '        SqueezeBox.close();';
		$script[] = '    }';

		// Add to document head
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

		// Setup variables for display
		$html = [];
		$link = 'index.php?option=com_dpcalendar&amp;view=events&amp;layout=modal' . '&amp;tmpl=component&amp;function=jSelectEvent_' . $this->id;

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('title');
		$query->from('#__dpcalendar_events');
		$query->where('id=' . (int)$this->value);
		$db->setQuery($query);

		if ($this->value && !$title = $db->loadResult()) {
			JFactory::getApplication()->enqueueMessage($db->getErrorMsg(), 500);

			return;
		}

		if (empty($title)) {
			$title = JText::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT');
		}
		$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

		// The current event input field

		$html[] = '  <div class="blank input-append">';
		$html[] = '  <input type="text" id="' . $this->id . '_name" value="' . $title . '" disabled="disabled" size="35" />';
		$html[] = '    <a class="modal btn btn-primary" title="' . JText::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT') . '" href="' . $link .
			'" rel="{handler: \'iframe\', size: {x:800, y:450}}">' . JText::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT_BUTTON') . '</a>';
		$html[] = '  </div>';

		// The active event id field
		if (0 == (int)$this->value) {
			$value = '';
		} else {
			$value = (int)$this->value;
		}

		$class = '';
		if ($this->required) {
			$class = ' class="required modal-value"';
		}

		$onchange = $this->onchange ? ' onchange="' . $this->onchange . '"' : '';

		$html[] = '<input type="hidden" id="' . $this->id . '_id"' . $class . ' name="' . $this->name . '" value="' . $value . '" ' . $onchange . '/>';

		return implode("\n", $html);
	}

	protected function getJ4Input()
	{
		$allowNew       = ((string)$this->element['new'] == 'true');
		$allowEdit      = ((string)$this->element['edit'] == 'true');
		$allowClear     = ((string)$this->element['clear'] != 'false');
		$allowSelect    = ((string)$this->element['select'] != 'false');
		$allowPropagate = ((string)$this->element['propagate'] == 'true');

		$languages = LanguageHelper::getContentLanguages(array(0, 1), false);

		// Load language
		Factory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR);

		// The active article id field.
		$value = (int)$this->value ?: '';

		// Create the modal id.
		$modalId = 'Event_' . $this->id;

		/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

		// Add the modal field script to the document head.
		$wa->useScript('field.modal-fields');

		// Script to proxy the select modal function to the modal-fields.js file.
		if ($allowSelect) {
			static $scriptSelect = null;

			if (is_null($scriptSelect)) {
				$scriptSelect = array();
			}

			if (!isset($scriptSelect[$this->id])) {
				$wa->addInlineScript("
				window.jSelectEvent_" . $this->id . " = function (id, title, catid, object, url, language) {
					window.processModalSelect('Event', '" . $this->id . "', id, title, catid, object, url, language);
				}",
					[],
					['type' => 'module']
				);

				Text::script('JGLOBAL_ASSOCIATIONS_PROPAGATE_FAILED');

				$scriptSelect[$this->id] = true;
			}
		}

		// Setup variables for display.
		$linkArticles = 'index.php?option=com_dpcalendar&amp;view=events&amp;layout=modal&amp;tmpl=component&amp;' . \Joomla\CMS\Session\Session::getFormToken() . '=1';
		$linkArticle  = 'index.php?option=com_dpcalendar&amp;view=event&amp;layout=modal&amp;tmpl=component&amp;' . \Joomla\CMS\Session\Session::getFormToken() . '=1';

		if (isset($this->element['language'])) {
			$linkArticles .= '&amp;forcedLanguage=' . $this->element['language'];
			$linkArticle  .= '&amp;forcedLanguage=' . $this->element['language'];
			$modalTitle   = Text::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT') . ' &#8212; ' . $this->element['label'];
		} else {
			$modalTitle = Text::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT');
		}

		$urlSelect = $linkArticles . '&amp;function=jSelectEvent_' . $this->id;
		$urlEdit   = $linkArticle . '&amp;task=event.edit&amp;id=\' + document.getElementById(&quot;' . $this->id . '_id&quot;).value + \'';
		$urlNew    = $linkArticle . '&amp;task=event.add';

		if ($value) {
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('title'))
				->from($db->quoteName('#__dpcalendar_events'))
				->where($db->quoteName('id') . ' = '.(int)$value);
			$db->setQuery($query);

			try {
				$title = $db->loadResult();
			} catch (\RuntimeException $e) {
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			}
		}

		$title = empty($title) ? Text::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT') : htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

		// The current article display field.
		$html = '';

		if ($allowSelect || $allowNew || $allowEdit || $allowClear) {
			$html .= '<span class="input-group">';
		}

		$html .= '<input class="form-control" id="' . $this->id . '_name" type="text" value="' . $title . '" readonly size="35">';

		if ($allowSelect || $allowNew || $allowEdit || $allowClear) {
			$html .= '<span class="input-group-append">';
		}

		// Select article button
		if ($allowSelect) {
			$html .= '<button'
				. ' class="btn btn-primary' . ($value ? ' hidden' : '') . '"'
				. ' id="' . $this->id . '_select"'
				. ' data-toggle="modal"'
				. ' type="button"'
				. ' data-target="#ModalSelect' . $modalId . '">'
				. '<span class="fas fa-file" aria-hidden="true"></span> ' . Text::_('JSELECT')
				. '</button>';
		}

		// New article button
		if ($allowNew) {
			$html .= '<button'
				. ' class="btn btn-secondary' . ($value ? ' hidden' : '') . '"'
				. ' id="' . $this->id . '_new"'
				. ' data-toggle="modal"'
				. ' type="button"'
				. ' data-target="#ModalNew' . $modalId . '">'
				. '<span class="fas fa-plus" aria-hidden="true"></span> ' . Text::_('JACTION_CREATE')
				. '</button>';
		}

		// Edit article button
		if ($allowEdit) {
			$html .= '<button'
				. ' class="btn btn-secondary' . ($value ? '' : ' hidden') . '"'
				. ' id="' . $this->id . '_edit"'
				. ' data-toggle="modal"'
				. ' type="button"'
				. ' data-target="#ModalEdit' . $modalId . '">'
				. '<span class="fas fa-pen-square" aria-hidden="true"></span> ' . Text::_('JACTION_EDIT')
				. '</button>';
		}

		// Clear article button
		if ($allowClear) {
			$html .= '<button'
				. ' class="btn btn-secondary' . ($value ? '' : ' hidden') . '"'
				. ' id="' . $this->id . '_clear"'
				. ' type="button"'
				. ' onclick="window.processModalParent(\'' . $this->id . '\'); return false;">'
				. '<span class="fas fa-times" aria-hidden="true"></span> ' . Text::_('JCLEAR')
				. '</button>';
		}

		if ($allowSelect || $allowNew || $allowEdit || $allowClear) {
			$html .= '</span></span>';
		}

		// Select article modal
		if ($allowSelect) {
			$html .= HTMLHelper::_(
				'bootstrap.renderModal',
				'ModalSelect' . $modalId,
				array(
					'title'      => $modalTitle,
					'url'        => $urlSelect,
					'height'     => '400px',
					'width'      => '800px',
					'bodyHeight' => 70,
					'modalWidth' => 80,
					'footer'     => '<button type="button" class="btn btn-secondary" data-dismiss="modal">'
						. Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
				)
			);
		}

		// New article modal
		if ($allowNew) {
			$html .= HTMLHelper::_(
				'bootstrap.renderModal',
				'ModalNew' . $modalId,
				array(
					'title'       => Text::_('COM_CONTENT_NEW_ARTICLE'),
					'backdrop'    => 'static',
					'keyboard'    => false,
					'closeButton' => false,
					'url'         => $urlNew,
					'height'      => '400px',
					'width'       => '800px',
					'bodyHeight'  => 70,
					'modalWidth'  => 80,
					'footer'      => '<button type="button" class="btn btn-secondary"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'event\', \'cancel\', \'item-form\'); return false;">'
						. Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
						. '<button type="button" class="btn btn-primary"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'event\', \'save\', \'item-form\'); return false;">'
						. Text::_('JSAVE') . '</button>'
						. '<button type="button" class="btn btn-success"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'event\', \'apply\', \'item-form\'); return false;">'
						. Text::_('JAPPLY') . '</button>',
				)
			);
		}

		// Edit article modal
		if ($allowEdit) {
			$html .= HTMLHelper::_(
				'bootstrap.renderModal',
				'ModalEdit' . $modalId,
				array(
					'title'       => Text::_('COM_CONTENT_EDIT_ARTICLE'),
					'backdrop'    => 'static',
					'keyboard'    => false,
					'closeButton' => false,
					'url'         => $urlEdit,
					'height'      => '400px',
					'width'       => '800px',
					'bodyHeight'  => 70,
					'modalWidth'  => 80,
					'footer'      => '<button type="button" class="btn btn-secondary"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'event\', \'cancel\', \'item-form\'); return false;">'
						. Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
						. '<button type="button" class="btn btn-primary"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'event\', \'save\', \'item-form\'); return false;">'
						. Text::_('JSAVE') . '</button>'
						. '<button type="button" class="btn btn-success"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'article\', \'apply\', \'item-form\'); return false;">'
						. Text::_('JAPPLY') . '</button>',
				)
			);
		}

		// Note: class='required' for client side validation.
		$class = $this->required ? ' class="required modal-value"' : '';

		$html .= '<input type="hidden" id="' . $this->id . '_id" ' . $class . ' data-required="' . (int)$this->required . '" name="' . $this->name
			. '" data-text="' . htmlspecialchars(Text::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT'), ENT_COMPAT, 'UTF-8') . '" value="' . $value . '">';

		return $html;
	}

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 *
	 * @since   3.4
	 */
	protected function getLabel()
	{
		return str_replace($this->id, $this->id . '_name', parent::getLabel());
	}
}
