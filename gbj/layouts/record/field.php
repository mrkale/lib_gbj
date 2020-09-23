<?php
/**
 * @package     Joomla.Library
 * @subpackage  Layout
 * @copyright  (c) 2017-2020 Libor Gabaj
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @since       3.8
 */

// No direct access
defined('_JEXEC') or die;

$record = $displayData->item;

// Options
$options = $this->getOptions();
$fieldName = $options->get('field');
$fieldUrl = $options->get('url');

// The field not registered
if (!isset($displayData->gridFields[$fieldName]))
{
	return;
}

$field = $displayData->gridFields[$fieldName];

// XML attribute - flag about disabling an element - default FALSE
$disabled = strtoupper($field->getAttribute('disabled') ?? 'FALSE') === 'TRUE';

// Render field in a detail page
if (!$disabled)
{
	// XML attribute - flag about grid cell default value - default TRUE
	$gridDefault = strtoupper($field->getAttribute('defaulted') ?? 'TRUE') === 'TRUE';

	// XML attribute - default value for a field - default NONE
	$gridDefaultValue = JText::_($field->getAttribute('default') ?? 'LIB_GBJ_NONE_VALUE');

	// XML attribute - format for displaying value - default NULL
	$gridFormat = $field->getAttribute('format');

	// XML attribute - prefix for displaying value - default NULL
	$gridPrefix = $field->getAttribute('prefix');

	if ($gridPrefix)
	{
		$prefixList = explode(';', $gridPrefix);

		foreach ($prefixList as $i => $prefixItem)
		{
			$prefixValue = JText::_($prefixItem);

			if ($prefixValue == $prefixItem)	// No language constants
			{
				$prefixValue = $record->$prefixItem ?? JText::_('LIB_GBJ_NONE_PREFIX');
			}

			$prefixList[$i] = $prefixValue;
		}

		$gridPrefix = implode(Helper::COMMON_HTML_SPACE, $prefixList)
			. Helper::COMMON_HTML_SPACE;
	}

	// XML attribute - suffix for displaying value - default NULL
	$gridSuffix = $field->getAttribute('suffix');

	if ($gridSuffix)
	{
		$suffixList = explode(';', $gridSuffix);

		foreach ($suffixList as $i => $suffixItem)
		{
			$suffixValue = JText::_($suffixItem);

			if ($suffixValue == $suffixItem)	// No language constants
			{
				$suffixValue = $record->$suffixItem ?? JText::_('LIB_GBJ_NONE_SUFFIX');
			}

			$suffixList[$i] = $suffixValue;
		}

		$gridSuffix = Helper::COMMON_HTML_SPACE
			. implode(Helper::COMMON_HTML_SPACE, $suffixList);
	}

	// XML attribute - Force value from data field
	$fieldData = $field->getAttribute('datafield');
	$fieldValue = $record->$fieldData ?? $record->$fieldName;

	$renderTag = $displayData->htmlAttribute('class', $field->getAttribute('labelclass'))
		. $displayData->htmlAttribute('width', $field->getAttribute('width'));

	$renderHeader = JText::_($field->getAttribute('label'));

	// If url option is true, replace it with field value
	if (is_bool($fieldUrl) && $fieldUrl)
	{
		$fieldUrl = $fieldValue;
	}

	// Formatting by element type
	$fieldType = $field->getAttribute('type');

	if (is_null($fieldType) && Helper::isCodedField($fieldName))
	{
		$fieldType = "code-value";
	}

	switch ($fieldType)
	{
		case 'date':
			if (Helper::isEmptyDate($fieldValue))
			{
				$fieldValue = null;
			}
			elseif (isset($fieldValue))
			{
				if (!is_null($gridFormat))
				{
					$fieldValue = JHtml::_('date', $fieldValue, JText::_($gridFormat));
				}

				// Highlight future date
				$dateCompareFormat = "Ymd";

				if (JFactory::getDate($record->$fieldName)->format($dateCompareFormat) > JFactory::getDate()->format($dateCompareFormat))
				{
					$cparams = JComponentHelper::getParams(Helper::getName());
					$renderTag = $displayData->htmlAttribute('class', $cparams->get('future_row_class'));
				}
			}

			break;

		case 'number':
			if (is_null($gridFormat))
			{
				$fieldValue = is_null($fieldValue) ? null : floatval($fieldValue);
			}
			else
			{
				$fieldValue = Helper::formatNumber(
					$fieldValue,
					JText::_($gridFormat)
				);
			}

			break;

		case 'user':
			$fieldValue = JFactory::getUser($fieldValue)->username;
			break;

		case 'code-value':
		default:
			$fieldValue = empty($fieldValue) ? null : $fieldValue;
			break;
	}

	// Construct element as the tooltip
	if (!empty($fieldValue))
	{
		$isTooltip = false;

		if ($fieldType == 'date')
		{
			$now = new JDate;
			$now = $now->toSQL();
			$start = $record->$fieldName;
			$fieldTitle = Helper::formatPeriodDates($start, $now);
			$isTooltip = !empty($fieldTitle);
		}
		elseif (is_string($field->getAttribute('tooltip')))
		{
			$fieldTitle = $record->{$field->getAttribute('tooltip')};
			$isTooltip = true;
		}

		if ($isTooltip)
		{
			$fieldValue = '<span class="hasTooltip" title="' . $fieldTitle . '">' . $fieldValue . '</span>';
		}
	}

	// Suppress default value
	if (!$gridDefault)
	{
		$gridDefaultValue = null;
	}

	// Displayed value, prefix, and suffix
	$renderData = $gridDefaultValue;

	if (isset($fieldValue) && !is_null($fieldValue))
	{
		$renderData = $gridPrefix . $fieldValue . $gridSuffix;
	}

	// Construct element as the hypertext
	if (!is_null($fieldUrl))
	{
		$renderData = '<a href="'
			. JRoute::_($fieldUrl)
			. '">'
			. $renderData
			. '</a>';
	}
}
?>
<?php if (!$disabled)
:
?>
<dt <?php echo $renderTag; ?>><?php echo $renderHeader; ?></dt>
<dd><?php echo $renderData; ?></dd>
<?php endif;
