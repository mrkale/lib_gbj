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

extract($displayData);
$options = array();

// Create options from batch fields definition
if (is_array($list))
{
	foreach ($list as $listItem)
	{
		$option = new stdClass;
		$option->value = $listItem['value'];
		$option->text = JText::_($listItem['text']);
		$options[] = $option;
	}
}
else
{
	// Create options from codebook
	$app = JFactory::getApplication();
	$db = JFactory::getDbo();
	$query = $db->getQuery(true)
		->select(array($db->quoteName('a.id', 'value'), $db->quoteName('a.title', 'text')))
		->from($db->quoteName(Helper::getCodebookTable($field), 'a'))
		->where($db->quoteName('a.state') . '=' . (int) Helper::COMMON_STATE_PUBLISHED)
		->order($db->quoteName('a.title'));
	$db->setQuery($query);

	try
	{
		$options = $db->loadObjectList();
	}
	catch (RuntimeException $e)
	{
		$app->enqueueMessage($e->getMessage(), 'error');
	}
}

// Merge any additional options in the XML definition.
$unknown = new stdClass;
$unknown->value = '0';
$unknown->text = JText::_('JNONE');
array_unshift($options, $unknown);

$langLabel = strtoupper(Helper::getExtensionName() . '_BATCH_' . $field . '_LABEL');
?>
<label
	id="batch-<?php echo $field; ?>-lbl"
	for="batch-<?php echo $field; ?>-id"
	class="modalTooltip"
	title="<?php echo JHtml::_('tooltipText', $langLabel, 'LIB_GBJ_BATCH_DESC'); ?>">
	<?php echo JText::_($langLabel); ?>
</label>
<div id="batch-choose-action" class="control-group">
	<select name="batch[<?php echo $field; ?>_id]" class="inputbox" id="batch-<?php echo $field; ?>-id">
		<option value=""><?php echo JText::_('LIB_GBJ_BATCH_KEEP'); ?></option>
		<?php echo JHtml::_('select.options', $options); ?>
	</select>
</div>
