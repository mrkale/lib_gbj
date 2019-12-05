<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017-2019 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
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
$flagNoDesc = boolval($options->get('nodesc'));

if (is_string($fieldList))
{
	$fieldList = explode(',', $fieldList);
}

foreach ($fieldList as $fieldIdx => $fieldName)
{
	$flagFutureDate = false;

	$fieldName = trim($fieldName);
	$field = $displayData->gridFields[$fieldName];

	// XML attribute - flag about disabling an element - default FALSE
	$disabled = strtoupper($field->getAttribute('disabled') ?? 'FALSE') === 'TRUE';

	// Render field data in a grid
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

		$fieldTags = '';
		$fieldTags .= $displayData->htmlAttribute('class', $field->getAttribute('class'));
		$fieldTags .= $displayData->htmlAttribute('style', $field->getAttribute('style'));

		// XML attribute - Force value from data field
		$fieldData = $field->getAttribute('datafield');

		// XML attribute - field variants - default NULL
		$fieldVariants = $field->getAttribute('variants') ?? '#';

		// Process field variants
		unset($variantFields);
		unset($variantFieldsShowEmpty);
		unset($fieldValue);
		$variants = explode(',', $fieldVariants);

		foreach ($variants as $variantNum => $variant)
		{
			$variantFieldsShowEmpty[$variantNum] = true;

			if ($variant == '#')
			{
				$variantFields[$variantNum] = $fieldName;
			}
			else
			{
				if (substr($variant, 0, 1) == '#')
				{
					$variant = substr($variant, 1);
					$variantFieldsShowEmpty[$variantNum] = false;
				}

				$variantFields[$variantNum] = $fieldName . $variant;
			}
		}

		$allEmptyVariants = true;

		foreach ($variantFields as $variantNum => $variantFieldName)
		{
			if (!isset($record->$variantFieldName))
			{
				continue;
			}

			$variantFieldValue = $record->$fieldData ?? $record->$variantFieldName;

			// If url option is true, replace it with field value
			$fieldUrl = $options->get('url');

			if (is_bool($fieldUrl) && $fieldUrl)
			{
				$fieldUrl = $variantFieldValue;
			}

			// Format field value
			$fieldType = $field->getAttribute('type');

			if (is_null($fieldType) && Helper::isCodedField($variantFieldName))
			{
				$fieldType = "code-value";
			}

			switch ($fieldType)
			{
				case 'checkbox':
					$variantFieldValue = JHtml::_('grid.id', $variantFieldValue, $record->id);
					break;

				case 'seqno':
					$variantFieldValue = (string) ($variantFieldValue + 1) . '.';

					if ($record->state == Helper::COMMON_STATE_ARCHIVED)
					{
						$variantFieldValue .= JText::_('LIB_GBJ_RECORD_FLAG_ARCHIVED');
					}
					break;

				case 'date':
					if (Helper::isEmptyDate($record->$fieldName))
					{
						$variantFieldValue = null;
					}
					elseif (isset($variantFieldValue))
					{
						if (!is_null($gridFormat))
						{
							$variantFieldValue = JHtml::_('date', $variantFieldValue, JText::_($gridFormat));
						}

						// Highlight future date
						$dateCompareFormat = "Ymd";

						if (JFactory::getDate($record->$fieldName)->format($dateCompareFormat) > JFactory::getDate()->format($dateCompareFormat))
						{
							$flagFutureDate = true;
						}
					}

					break;

				case 'number':
					if (is_null($gridFormat))
					{
						$variantFieldValue = floatval($variantFieldValue);
					}
					else
					{
						$variantFieldValue = Helper::formatNumber(
							$variantFieldValue,
							JText::_($gridFormat)
						);
					}

					break;

				case 'button-published':
					$variantFieldValue = JHtml::_(
						'jgrid.published',
						$record->state,
						$record->sequence,
						$viewName . '.', $canChange,
						'cb',
						$record->publish_up,
						$record->publish_down
					);
					$isButtons = true;
					break;

				case 'button-featured':
					$method = Helper::proper(Helper::getLibraryDir()) . '.html.featured';
					$variantFieldValue = JHtml::_($method, $record->sequence, $record->featured, $canChange, $viewName);
					$isButtons = true;
					break;

				case 'icon-value':
					$icon = $field->getAttribute('icon' . $variantFieldValue);
					$variantFieldValue = '<span class="' . $icon . '"</span>';
					break;

				case 'code-value':
					$variantFieldValue = empty($variantFieldValue) ? null : $variantFieldValue;
					break;
			}

			// Concatenate field variant
			if ($variantNum == 0)
			{
				if (!empty($variantFieldValue) || $variantFieldsShowEmpty[$variantNum])
				{
					$fieldValue = $variantFieldValue;
				}
				else
				{
					$fieldValue = '';
				}
			}
			else
			{
				if (isset($fieldValue)
					&& (!empty($variantFieldValue) || $variantFieldsShowEmpty[$variantNum]))
				{
					$fieldValue .= JText::_('LIB_GBJ_FIELD_RATIO') . $variantFieldValue;
				}
			}

			if (!empty($variantFieldValue))
			{
				$allEmptyVariants = false;
			}
		}

		if ($allEmptyVariants)
		{
			$fieldValue = null;
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
		$fieldData = $gridDefaultValue;

		if (isset($fieldValue) && !is_null($fieldValue))
		{
			$fieldData = $gridPrefix . $fieldValue . $gridSuffix;
		}

		// Render for hyperlink to detail - Only the first field in a list
		if ($fieldIdx == 0 && isset($fieldUrl) && !is_null($fieldUrl))
		{
			$fieldData = '<a href="'
				. JRoute::_($fieldUrl)
				. '">'
				. $fieldData
				. '</a>';
		}

		// Render for future date
		if ($flagFutureDate)
		{
			$fieldData = Helper::htmlRenderBold($fieldData);
		}

		// Render for empty description - Only the first field in a list
		if ($flagNoDesc)
		{
			$fieldData = Helper::htmlRenderItalic($fieldData);
			$flagNoDesc = false;
		}

		// Add field to list
		$renderFields[] = array('tag' => $fieldTags, 'data' => $fieldData);
		$fieldCount++;
	}
}

// Prepare data for table
if ($fieldCount)
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
			if ($key)
			{
				$tableData .= '<br />';
			}
			else
			{
				$tableTags = $renderField['tag'];
			}

			$tableData .= $renderField['data'];
		}
	}
}
else
{
	$tableTags = $renderFields[0]['tag'];
	$tableData = $renderFields[0]['data'];
}

if ($fieldCount)
{
	echo '<td' . $tableTags . '>' . $tableData . '</td>';
}
