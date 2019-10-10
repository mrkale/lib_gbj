<?php
/**
 * @package     Joomla.Library
 * @subpackage  Layout
 * @copyright  (c) 2017-2019 Libor Gabaj
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @since       3.8
 */

// No direct access
defined('_JEXEC') or die;

$record = $displayData->item;

// Options
$options = $this->getOptions();
$field_name = $options->get('field');
$field_url = $options->get('url');

// The field not registered
if (!isset($displayData->gridFields[$field_name]))
{
	return;
}

$field = $displayData->gridFields[$field_name];

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
		$prefixValue = JText::_($gridPrefix);

		if ($prefixValue == $gridPrefix)	// No language constants
		{
			$prefixValue = $record->$gridPrefix ?? JText::_('LIB_GBJ_NONE_PREFIX');
		}

		$gridPrefix = ltrim($prefixValue . Helper::COMMON_HTML_SPACE);
	}

	// XML attribute - suffix for displaying value - default NULL
	$gridSuffix = $field->getAttribute('suffix');

	if ($gridSuffix)
	{
		$suffixValue = JText::_($gridSuffix);

		if ($suffixValue == $gridSuffix)	// No language constants
		{
			$suffixValue = $record->$gridSuffix ?? JText::_('LIB_GBJ_NONE_SUFFIX');
		}

		$gridSuffix = rtrim(Helper::COMMON_HTML_SPACE . $suffixValue);
	}

	// XML attribute - Force value from data field
	$fieldData = $field->getAttribute('datafield');
	$field_value = $record->$fieldData ?? $record->$field_name;

	$renderTag = $displayData->htmlAttribute('class', $field->getAttribute('labelclass'))
		. $displayData->htmlAttribute('width', $field->getAttribute('width'));

	$renderHeader = JText::_($field->getAttribute('label'));

	// If url option is true, replace it with field value
	if (is_bool($field_url) && $field_url)
	{
		$field_url = $field_value;
	}

	// Formatting by element type
	$field_type = $field->getAttribute('type');
	if (is_null($field_type) && Helper::isCodedField($field_name))
	{
		$field_type =  "code-value";
	}

	switch ($field_type)
	{
		case 'date':
			if (Helper::isEmptyDate($field_value))
			{
				$field_value = null;
			}
			elseif (isset($field_value))
			{
				if (!is_null($gridFormat))
				{
					$field_value = JHtml::_('date', $field_value, JText::_($gridFormat));
				}

				// Highlight future date
				$dateCompareFormat = "Ymd";

				if (JFactory::getDate($record->$field_name)->format($dateCompareFormat) > JFactory::getDate()->format($dateCompareFormat))
				{
					$cparams = JComponentHelper::getParams(Helper::getName());
					$renderTag = $displayData->htmlAttribute('class', $cparams->get('future_row_class'));
				}
			}

			break;

		case 'number':
			if (is_null($gridFormat))
			{
				$field_value = floatval($field_value);
			}
			else
			{
				$field_value = Helper::formatNumber(
					$field_value,
					JText::_($gridFormat)
				);
			}

			break;

		case 'user':
			$field_value = JFactory::getUser($field_value)->username;
			break;

		case 'code-value':
			$field_value = empty($field_value) ? null : $field_value;
			break;
	}

	// Construct element as the tooltip
	if (!empty($field_value) && is_string($field->getAttribute('tooltip')))
	{
		$field_title = $record->{$field->getAttribute('tooltip')};
		$field_value = '<span class="hasTooltip" title="' . $field_title . '">' . $field_value . '</span>';
	}

	// Suppress default value
	if (!$gridDefault)
	{
		$gridDefaultValue = null;
	}

	// Displayed value, prefix, and suffix
	$renderData = $gridDefaultValue;

	if (isset($field_value) && !is_null($field_value))
	{
		$renderData = $gridPrefix . $field_value . $gridSuffix;
	}

	// Construct element as the hypertext
	if (!is_null($field_url))
	{
		$renderData = '<a href="'
			. JRoute::_($field_url)
			. '">'
			. $renderData
			. '</a>';
	}
}
?>
<?php if (!$disabled) : ?>
<dt <?php echo $renderTag; ?>><?php echo $renderHeader; ?></dt>
<dd><?php echo $renderData; ?></dd>
<?php endif;
