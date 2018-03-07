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

$form = $displayData->getForm();

$fields = array('title', 'date_on');

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
