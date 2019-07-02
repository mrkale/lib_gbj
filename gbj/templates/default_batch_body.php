<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

$container = '';
$fieldNum = 0;
$rowClosed = true;
$batchFields = array();

// Start with batch fields
if (is_array($this->batchFields))
{
	foreach ($this->batchFields as $fieldName => $fieldForms)
	{
		$batchFields[$fieldName] = $fieldForms;
	}
}

// Merge coded fields
foreach ($this->model->getCodedFields() as $fieldName => $fieldForms)
{
	$batchFields[$fieldForms['root']] = $fieldForms;
}

// Create layout statement
foreach ($batchFields as $fieldName => $fieldForms)
{
	$controlGroup = '<div class="control-group span6"><div class="controls">';
	$controlGroup .= JLayoutHelper::render('grid.field',
		array('field' => $fieldName, 'list' => $fieldForms['options'] ?? null),
		Helper::getLayoutBase());
	$controlGroup .= '</div></div>';

	if ($fieldNum % 2 == 0)
	{
		$row = '<div class="row-fluid">';
		$rowClosed = false;
	}

	$row .= $controlGroup;

	if ($fieldNum % 2 > 0)
	{
		$row .= '</div>';
		$container .= $row;
		$rowClosed = true;
	}

	$fieldNum++;
}

// Close row and add it to the container
if (!$rowClosed)
{
	$row .= '</div>';
	$container .= $row;
	$rowClosed = true;
}
?>

<div class="container-fluid">
	<?php echo $container; ?>
</div>
