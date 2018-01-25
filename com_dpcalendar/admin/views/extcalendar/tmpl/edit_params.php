<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

$fieldSets = $this->form->getFieldsets();
foreach ($fieldSets as $name => $fieldSet)
{
	if ($name != 'params')
	{
		continue;
	}
	?>
	<div class="tab-pane" id="params-<?php echo $name;?>">
	<?php
	if (isset($fieldSet->description) && trim($fieldSet->description))
	{
		echo '<p class="alert alert-info">' . $this->escape(JText::_($fieldSet->description)) . '</p>';
	}
	?>
			<?php foreach ($this->form->getFieldset($name) as $field)
			{
				if (strtolower($field->__get('type')) == 'hidden')
				{
					echo $field->input;
				}
				else
				{?>
				<div class="control-group">
					<div class="control-label"><?php echo $field->label; ?></div>
					<div class="controls"><?php echo $field->input; ?></div>
				</div>
			<?php
				}
			} ?>
	</div>
<?php
}
