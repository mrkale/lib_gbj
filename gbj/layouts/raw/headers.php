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

// Output field header
$fieldData = trim(JText::_($field->getAttribute('label')));
$fieldData = '"' . $fieldData . '"' . Helper::COMMON_FILE_CSV_DELIMITER;
echo $fieldData;
