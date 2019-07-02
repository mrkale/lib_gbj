<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017-2018 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

$onclick = '';
$batchFields = array();

// Start with batch fields
if (is_array($this->batchFields))
{
	foreach ($this->batchFields as $fieldName => $fieldForms)
	{
		$batchFields[] = $fieldName;
	}
}

// Merge coded fields
foreach ($this->model->getCodedFields() as $fieldForms)
{
	$batchFields[] = $fieldForms['root'];
}

// Create reset statement
foreach ($batchFields as $fieldName)
{
	$onclick .= "document.getElementById('batch-" . $fieldName . "-id').value='';";
}
$batchEvent = Helper::singular($this->getName()) . '.batch';
?>
<button class="btn" type="button" onclick="<?php echo $onclick; ?>" data-dismiss="modal">
	<?php echo JText::_('JCANCEL'); ?>
</button>
<button class="btn btn-success" type="submit"
	onclick="Joomla.submitbutton('<?php echo $batchEvent; ?>');">
	<?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>
