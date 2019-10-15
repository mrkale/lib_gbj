<?php
/**
 * @package     Joomla.Library
 * @subpackage  Layout
 * @copyright  (c) 2019 Libor Gabaj
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @since       3.8
 */

// No direct access
defined('_JEXEC') or die;

$form = $displayData->getForm();

$fields = array(array('title'), array('date_on', 'date_off', 'date_out'));

// Remove disabled and unknown fields
foreach ($fields as $i => $fieldList)
{
	foreach ($fieldList as $fieldName)
	{
		$field = $form->getField($fieldName);
		if (!is_object($field)
		|| strtoupper($field->getAttribute('disabled') ?? 'FALSE') === 'TRUE')
		{
			$fields[$i] = array_diff($fields[$i], array($fieldName));
		}
	}
}
?>
<?php foreach ($fields as $fieldList): ?>
<div class="form-inline form-inline-header">
	<?php
	foreach ($fieldList as $fieldName)
	{
		echo $form->renderField($fieldName);
	}
	?>
</div>
<?php endforeach; ?>
