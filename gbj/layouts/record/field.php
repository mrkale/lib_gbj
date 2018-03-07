<?php
/**
 * @package     Joomla.Library
 * @subpackage  Layout
 * @copyright   (c) 2017 Libor Gabaj. All rights reserved.
 * @license     GNU General Public License version 2 or later. See LICENSE.txt, LICENSE.php.
 * @since       3.7
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

// XML attribute - flag about grid cell default value - default TRUE
$gridDefault = strtoupper($field->getAttribute('defaulted') ?? 'TRUE') === 'TRUE';

// XML attribute - default value for a field - default JNONE
$gridDefaultValue = JText::_($field->getAttribute('default') ?? 'JNONE');

// XML attribute - format for displaying value - default NULL
$gridFormat = $field->getAttribute('format');

// XML attribute - suffix for displaying value - default NULL
$gridSuffix = JText::_($field->getAttribute('suffix'));

// Render field in a detail page
if (!$disabled)
{
	$field_value = $record->$field_name;

	$renderTag = $displayData->htmlAttribute('class', $field->getAttribute('labelclass'))
		. $displayData->htmlAttribute('width', $field->getAttribute('width'));

	$renderHeader = JText::_($field->getAttribute('label'));

	// If url option is true, replace it with field value
	if (is_bool($field_url) && $field_url)
	{
		$field_url = $field_value;
	}

	// Formatting by element type
	switch ($field->getAttribute('type'))
	{
		case 'date':

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

			break;

		case 'user':
			$field_value = JFactory::getUser($field_value)->username;
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
		unset($gridDefaultValue);
	}

	// Displayed value
	$renderData = (empty($field_value) ? $gridDefaultValue : $field_value);

	// Add suffix
	$renderData .= $gridSuffix;

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
