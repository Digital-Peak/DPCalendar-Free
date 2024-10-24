<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;

if (!$this->terms) {
	return;
}
?>
<div class="com-dpcalendar-booking__terms">
	<?php foreach ($this->terms as $article) { ?>
		<div class="dp-term dp-term-<?php echo $article->id; ?>">
			<input type="checkbox" id="dp-term-input-<?php echo $article->id; ?>" name="terms"
				   class="dp-input dp-input-checkbox dp-input-term dp-term__input">
			<label class="dp-term__text" for="dp-term-input-<?php echo $article->id; ?>">
				<?php echo Text::sprintf('COM_DPCALENDAR_VIEW_BOOKINGFORM_TERMS_TEXT', $article->dp_terms_link); ?>
			</label>
		</div>
	<?php } ?>
</div>
