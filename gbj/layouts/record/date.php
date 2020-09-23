<?php
/**
 * @package     Joomla.Library
 * @subpackage  Layout
 * @copyright  (c) 2019-2020 Libor Gabaj
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @since       3.8
 */

// No direct access
defined('_JEXEC') or die;

$form = $displayData->getForm();

$fields = array('date_on');

// Remove disabled and unknown fields
foreach ($fields as $fieldName)
{
	$field = $form->getField($fieldName);

	if (!is_object($field)
		|| strtoupper($field->getAttribute('disabled') ?? 'FALSE') === 'TRUE')
	{
		$fields = array_diff($fields, array($fieldName));
	}
}
?>
<div class="form-inline form-inline-header">
<?php
foreach ($fields as $fieldName)
{
	echo $form->renderField($fieldName);
}
?>
</div>
