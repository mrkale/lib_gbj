<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017 Libor Gabaj. All rights reserved.
 * @license    GNU General Public License version 2 or later. See LICENSE.txt, LICENSE.php.
 * @since      3.7
 */

// No direct access
defined('_JEXEC') or die;

$fieldCount = 0;
$renderFields = array();
$tableTags = '';
$tableData = '';
$isButtons = false;
$record = $displayData->item;

// Access parameters
$viewName = $displayData->getName();
$user = JFactory::getUser();
$canCheckin = $user->authorise('core.manage', 'com_checkin')
			|| $record->checked_out == $user->get('id')
			|| $record->checked_out == 0;
$canChange = $user->authorise('core.edit.state', Helper::getName())
			&& $canCheckin;

// Options
$options = $this->getOptions();
$fieldList = $options->get('fields');

if (is_string($fieldList))
{
	$fieldList = explode(',', $fieldList);
}

foreach ($fieldList as $fieldName)
{
	$fieldName = trim($fieldName);
	$field = $displayData->gridFields[$fieldName];

	// XML attribute - flag about disabling an element - default FALSE
	$disabled = strtoupper($field->getAttribute('disabled') ?? 'FALSE') === 'TRUE';

	// Render field data in a grid
	if (!$disabled)
	{
		// XML attribute - flag about grid cell default value - default TRUE
		$gridDefault = strtoupper($field->getAttribute('defaulted') ?? 'TRUE') === 'TRUE';

		// XML attribute - default value for a field - default JNONE
		$gridDefaultValue = JText::_($field->getAttribute('default') ?? 'JNONE');

		// XML attribute - format for displaying value - default NULL
		$gridFormat = $field->getAttribute('format');

		$fieldTags = $displayData->htmlAttribute('class', $field->getAttribute('class'));
		$fieldValue = $record->$fieldName;
		$fieldUrl = $options->get('url');

		// If url option is true, replace it with field value
		if (is_bool($fieldUrl) && $fieldUrl)
		{
			$fieldUrl = $fieldValue;
		}

		switch ($field->getAttribute('type'))
		{
			case 'checkbox':
				$fieldValue = JHtml::_('grid.id', $fieldValue, $record->id);
				break;

			case 'seqno':
				$fieldValue = (string) ($fieldValue + 1) . '.';
				break;


			case 'date':
				if (!is_null($gridFormat))
				{
					$fieldValue = JHtml::_('date', $fieldValue, JText::_($gridFormat));
				}

				// Highlight future date
				$dateCompareFormat = "Ymd";

				if (JFactory::getDate($record->$fieldName)->format($dateCompareFormat) > JFactory::getDate()->format($dateCompareFormat))
				{
					$fieldValue = '<strong><em>' . $fieldValue . '</em></strong>';
				}

				break;

			case 'button-published':
				$fieldValue = JHtml::_('jgrid.published', $record->state, $record->sequence, $viewName . '.', $canChange, 'cb', $record->publish_up, $record->publish_down);
				$isButtons = true;
				break;

			case 'button-featured':
				$method = Helper::proper(Helper::getLibraryDir()) . '.html.featured';
				$fieldValue = JHtml::_($method, $record->sequence, $record->featured, $canChange, $viewName);
				$isButtons = true;
				break;

			case 'icon-value':
				$icon = $field->getAttribute('icon' . $fieldValue);
				$fieldValue = '<span class="' . $icon . '"</span>';
				break;
		}

		// Construct element as the tooltip
		if (!empty($fieldValue) && is_string($field->getAttribute('tooltip')))
		{
			$fieldTitle = $record->{$field->getAttribute('tooltip')};
			$fieldValue = '<span class="hasTooltip" title="' . $fieldTitle . '">' . $fieldValue . '</span>';
		}

		// Suppress default value
		if (!$gridDefault)
		{
			$gridDefaultValue = null;
		}

		// Displayed value
		$fieldData = empty($fieldValue) ? $gridDefaultValue : $fieldValue;

		// Construct element as the hypertext
		if (!is_null($fieldUrl))
		{
			$fieldData = '<a href="'
				. JRoute::_($fieldUrl)
				. '">'
				. $fieldData
				. '</a>';
		}

		// Add field to list
		$renderFields[] = array('tag' => $fieldTags, 'data' => $fieldData);
		$fieldCount++;
	}
}

// Prepare data for table
if ($fieldCount > 1)
{
	if ($isButtons)
	{
		$tableData = '<div class="btn-group">';

		foreach ($renderFields as $key => $renderField)
		{
			$tableData .= $renderField['data'];
		}

		$tableData .= '</div>';
	}
	else
	{
		foreach ($renderFields as $key => $renderField)
		{
			if ($key > 0)
			{
				$tableData .= '<br />';
			}

			$tableData .= '<span' . $renderField['tag'] . '>' . $renderField['data'] . '</span>';
		}
	}
}
elseif ($fieldCount == 1)
{
	$tableTags = $renderFields[0]['tag'];
	$tableData = $renderFields[0]['data'];
}
?>

<?php if ($fieldCount) : ?>
<td <?php echo $tableTags; ?>><?php echo $tableData; ?></td>
<?php endif;
