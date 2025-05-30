<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

\defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

class EventField extends FormField
{
	protected $type = 'Event';

	protected function getInput(): string
	{
		$app = Factory::getApplication();
		if (!$app instanceof CMSWebApplicationInterface) {
			return '';
		}

		$allowNew    = ((string)$this->element['new'] === 'true');
		$allowEdit   = ((string)$this->element['edit'] === 'true');
		$allowClear  = ((string)$this->element['clear'] !== 'false');
		$allowSelect = ((string)$this->element['select'] !== 'false');
		LanguageHelper::getContentLanguages([0, 1], false);

		// Load language
		$app->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR);

		// The active article id field.
		$value = (int)$this->value;

		// Create the modal id.
		$modalId = 'Event_' . $this->id;

		$wa = $app->getDocument()->getWebAssetManager();

		// Add the modal field script to the document head.
		$wa->useScript('field.modal-fields');

		// Script to proxy the select modal function to the modal-fields.js file.
		if ($allowSelect) {
			static $scriptSelect = null;

			if (\is_null($scriptSelect)) {
				$scriptSelect = [];
			}

			if (!isset($scriptSelect[$this->id])) {
				$wa->addInlineScript("
				window.jSelectEvent_" . $this->id . " = function (id, title, catid, object, url, language) {
					window.processModalSelect('Event', '" . $this->id . "', id, title, catid, object, url, language);
					document.getElementById('" . $this->id . "_id').dispatchEvent(new Event('change'));
				}");

				Text::script('JGLOBAL_ASSOCIATIONS_PROPAGATE_FAILED');

				$scriptSelect[$this->id] = true;
			}
		}

		// Setup variables for display.
		$linkArticles = 'index.php?option=com_dpcalendar&amp;view=events&amp;layout=modal&amp;tmpl=component&amp;' . Session::getFormToken() . '=1';
		$linkArticle  = 'index.php?option=com_dpcalendar&amp;view=event&amp;layout=modal&amp;tmpl=component&amp;' . Session::getFormToken() . '=1';

		if (isset($this->element['language'])) {
			$linkArticles .= '&amp;forcedLanguage=' . $this->element['language'];
			$linkArticle .= '&amp;forcedLanguage=' . $this->element['language'];
			$modalTitle = Text::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT') . ' &#8212; ' . $this->element['label'];
		} else {
			$modalTitle = Text::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT');
		}

		$urlSelect = $linkArticles . '&amp;function=jSelectEvent_' . $this->id;
		$urlEdit   = $linkArticle . "&amp;task=event.edit&amp;id=' + document.getElementById(&quot;" . $this->id . "_id&quot;).value + '";
		$urlNew    = $linkArticle . '&amp;task=event.add';
		$db        = $this->getDatabase();
		$query     = $db->getQuery(true)
				->select('title')
				->from('#__dpcalendar_events')
				->where('id = ' . $value);
		$db->setQuery($query);
		try {
			$title = $db->loadResult();
		} catch (\RuntimeException $runtimeException) {
			$app->enqueueMessage($runtimeException->getMessage(), 'error');
		}

		$title = empty($title) ? Text::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT') : htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8');

		// The current article display field.
		$html = '';

		if ($allowSelect || $allowNew || $allowEdit || $allowClear) {
			$html .= '<span class="input-group">';
		}

		$html .= '<input class="form-control" id="' . $this->id . '_name" type="text" value="' . $title . '" readonly size="35">';

		// Select article button
		if ($allowSelect) {
			$html .= '<button class="btn btn-primary' . ($value !== 0 ? ' hidden' : '') . '"'
				. ' id="' . $this->id . '_select"'
				. ' data-bs-toggle="modal" data-toggle="modal"'
				. ' type="button"'
				. ' data-bs-target="#ModalSelect' . $modalId . '" data-target="#ModalSelect' . $modalId . '">'
				. '<span class="icon-file" aria-hidden="true"></span> ' . Text::_('JSELECT')
				. '</button>';
		}

		// New article button
		if ($allowNew) {
			$html .= '<button class="btn btn-secondary' . ($value !== 0 ? ' hidden' : '') . '"'
				. ' id="' . $this->id . '_new"'
				. ' data-bs-toggle="modal" data-toggle="modal"'
				. ' type="button"'
				. ' data-bs-target="#ModalNew' . $modalId . '" data-target="#ModalNew' . $modalId . '">'
				. '<span class="icon-plus" aria-hidden="true"></span> ' . Text::_('JACTION_CREATE')
				. '</button>';
		}

		// Edit article button
		if ($allowEdit) {
			$html .= '<button class="btn btn-secondary' . ($value !== 0 ? '' : ' hidden') . '"'
				. ' id="' . $this->id . '_edit"'
				. ' data-bs-toggle="modal" data-toggle="modal"'
				. ' type="button"'
				. ' data-bs-target="#ModalEdit' . $modalId . '" data-target="#ModalEdit' . $modalId . '">'
				. '<span class="icon-pen-square" aria-hidden="true"></span> ' . Text::_('JACTION_EDIT')
				. '</button>';
		}

		// Clear article button
		if ($allowClear) {
			$html .= '<button class="btn btn-secondary' . ($value !== 0 ? '' : ' hidden') . '"'
				. ' id="' . $this->id . '_clear"'
				. ' type="button"'
				. ' onclick="window.processModalParent(\'' . $this->id . "'); document.getElementById('" . $this->id . '_id\').dispatchEvent(new Event(\'change\')); return false;">'
				. '<span class="fas fa-times" aria-hidden="true"></span> ' . Text::_('JCLEAR')
				. '</button>';
		}

		if ($allowSelect || $allowNew || $allowEdit || $allowClear) {
			$html .= '</span>';
		}

		// Select article modal
		if ($allowSelect) {
			$html .= HTMLHelper::_(
				'bootstrap.renderModal',
				'ModalSelect' . $modalId,
				[
					'title'      => $modalTitle,
					'url'        => $urlSelect,
					'height'     => '400px',
					'width'      => '800px',
					'bodyHeight' => 70,
					'modalWidth' => 80,
					'footer'     => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'
						. Text::_('COM_DPCALENDAR_CLOSE') . '</button>',
				]
			);
		}

		// New article modal
		if ($allowNew) {
			$html .= HTMLHelper::_(
				'bootstrap.renderModal',
				'ModalNew' . $modalId,
				[
					'title'       => Text::_('COM_CONTENT_NEW_ARTICLE'),
					'backdrop'    => 'static',
					'keyboard'    => false,
					'closeButton' => false,
					'url'         => $urlNew,
					'height'      => '400px',
					'width'       => '800px',
					'bodyHeight'  => 70,
					'modalWidth'  => 80,
					'footer'      => '<button type="button" class="btn btn-secondary" onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'event\', \'cancel\', \'item-form\'); return false;">'
						. Text::_('COM_DPCALENDAR_CLOSE') . '</button>'
						. '<button type="button" class="btn btn-primary"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'event\', \'save\', \'item-form\'); return false;">'
						. Text::_('JSAVE') . '</button>'
						. '<button type="button" class="btn btn-success"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'event\', \'apply\', \'item-form\'); return false;">'
						. Text::_('JAPPLY') . '</button>',
				]
			);
		}

		// Edit article modal
		if ($allowEdit) {
			$html .= HTMLHelper::_(
				'bootstrap.renderModal',
				'ModalEdit' . $modalId,
				[
					'title'       => Text::_('COM_CONTENT_EDIT_ARTICLE'),
					'backdrop'    => 'static',
					'keyboard'    => false,
					'closeButton' => false,
					'url'         => $urlEdit,
					'height'      => '400px',
					'width'       => '800px',
					'bodyHeight'  => 70,
					'modalWidth'  => 80,
					'footer'      => '<button type="button" class="btn btn-secondary" onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'event\', \'cancel\', \'item-form\'); return false;">'
						. Text::_('COM_DPCALENDAR_CLOSE') . '</button>'
						. '<button type="button" class="btn btn-primary"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'event\', \'save\', \'item-form\'); return false;">'
						. Text::_('JSAVE') . '</button>'
						. '<button type="button" class="btn btn-success"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'article\', \'apply\', \'item-form\'); return false;">'
						. Text::_('JAPPLY') . '</button>',
				]
			);
		}

		// Note: class='required' for client side validation.
		$class = $this->required ? ' class="required modal-value"' : '';

		return $html . ('<input type="hidden" id="' . $this->id . '_id" ' . $class . ' data-required="' . (int)$this->required . '" name="' . $this->name
			. '" data-text="' . htmlspecialchars(Text::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT'), ENT_COMPAT, 'UTF-8') . '" value="' . ($value !== 0 ? $value : '') . '">');
	}

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 *
	 * @since   3.4
	 */
	protected function getLabel(): string
	{
		return str_replace($this->id, $this->id . '_name', parent::getLabel());
	}
}
