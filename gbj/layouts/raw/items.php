<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2020 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

$options = $this->getOptions();
$fieldName = trim($options->get('field'));
$field = $displayData->gridFields[$fieldName];
$fieldType = $field->getAttribute('type');
$record = $displayData->item;

// Excluded field types
if (strtoupper($field->getAttribute('disabled') ?? 'FALSE') === 'TRUE'
	|| $fieldType === 'checkbox'
	|| $fieldType === 'button-published'
	|| $fieldType === 'button-featured'
	|| $fieldType === 'icon-value'
	|| $fieldType === 'seqno'
)
{
	return;
}

// XML attribute - format for displaying value - default NULL
$gridFormat = $field->getAttribute('format');

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

	// Format field value
	if (is_null($fieldType) && Helper::isCodedField($variantFieldName))
	{
		$fieldType = "code-value";

		// Force code title instead its alias
		if ($displayData->flagCodeTitle)
		{
			$codeTitle = str_replace('_alias', '_title', $fieldData ?? $variantFieldName);
			$variantFieldValue = $record->$codeTitle ?? $variantFieldValue;
		}
	}

	switch ($fieldType)
	{
		case 'date':
			if (Helper::isEmptyDate($record->$fieldName))
			{
				$variantFieldValue = null;
			}
			elseif (isset($variantFieldValue))
			{
				// Force short date format
				$gridFormat = $gridFormat ?? 'LIB_GBJ_FORMAT_DATE_SHORT';
				$gridFormat = str_replace(
					array('LIB_GBJ_FORMAT_DATE_LONG', 'LIB_GBJ_FORMAT_DATE_SHORT_DAY'),
					'LIB_GBJ_FORMAT_DATE_SHORT',
					$gridFormat
				);
				$variantFieldValue = JHtml::_('date', $variantFieldValue, JText::_($gridFormat));
			}

			break;

		case 'number':
			$variantFieldValue = floatval($variantFieldValue);
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

echo $displayData->sanitize($fieldValue);
