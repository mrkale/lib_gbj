<?php

/**
 * @package    Joomla.Library
 * @copyright  (c) 2017 Libor Gabaj. All rights reserved.
 * @license    GNU General Public License version 2 or later. See LICENSE.txt, LICENSE.php.
 * @since      3.7
 */
// No direct access
defined('_JEXEC') or die;

$form = $displayData->getForm();
$options = $this->getOptions();
$fieldName = $options->get('field');
$field = $form->getField($fieldName);

if ($displayData->isParent() && is_object($field))
{
	$class = $field->getAttribute('class');
	$class = str_replace('inputbox', 'readonly', $class);
	$form->setFieldAttribute($fieldName, 'class', $class);
	$form->setFieldAttribute($fieldName, 'readonly', 'true');
}
?>
<?php echo $field ? $form->renderField($fieldName) : ''; ?>
