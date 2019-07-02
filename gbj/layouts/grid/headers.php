<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

$fieldCount = 0;
$renderFields = array();
$tableTags = '';
$tableData = '';

$record = $displayData->item;
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
		// XML attribute - flag about sorting a grid column - default TRUE
		$gridSort = strtoupper($field->getAttribute('sorted') ?? 'TRUE') !== 'FALSE';

		$fieldTags  = $displayData->htmlAttribute('class', $field->getAttribute('labelclass'));
		$fieldTags .= $displayData->htmlAttribute('width', $field->getAttribute('width'));

		if ($field->getAttribute('type') === 'checkbox')
		{
			$fieldData = '<input type="checkbox" onclick="Joomla.checkAll(this)" value=""'
				. ' name="' . $fieldName . '"'
				. ' title="' . JText::_($field->getAttribute('label')) . '"'
				. '/>';
		}
		elseif ($gridSort)
		{
			$fieldData = JHtml::_('searchtools.sort', $field->getAttribute('label'), $fieldName, $displayData->listDirn, $displayData->listOrder);
		}
		else
		{
			$fieldData = JText::_($field->getAttribute('label'));
		}

		// Add field to list
		$renderFields[] = array('tag' => $fieldTags, 'data' => $fieldData);
		$fieldCount++;
		$displayData->columns++;

		// Only the first enabled header
		if ($options->get('onlyone'))
		{
			break;
		}
	}
}

// Prepare data for table
if ($fieldCount > 1)
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
elseif ($fieldCount == 1)
{
	$tableData = $renderFields[0]['data'];
}

$tableTags = $renderFields[0]['tag'];
?>

<?php if ($fieldCount) : ?>
<th<?php echo $tableTags; ?>><?php echo $tableData; ?></th>
<?php endif;
