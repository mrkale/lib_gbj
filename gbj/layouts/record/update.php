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

$layoutBasePath = Helper::getLayoutBase();
$tparams = $displayData->params;
$pageclass_sfx = htmlspecialchars($tparams->get('pageclass_sfx'));
$class = strtolower(Helper::getClassPrefix()). '_dl' . $pageclass_sfx;
?>
<dl class="<?php echo $class; ?>">
	<?php echo JLayoutHelper::render('record.field', $displayData, $layoutBasePath, array('field'=>'id')); ?>
	<?php echo JLayoutHelper::render('record.field', $displayData, $layoutBasePath, array('field'=>'modified')); ?>
	<?php echo JLayoutHelper::render('record.field', $displayData, $layoutBasePath, array('field'=>'modified_by')); ?>
	<?php echo JLayoutHelper::render('record.field', $displayData, $layoutBasePath, array('field'=>'created')); ?>
	<?php echo JLayoutHelper::render('record.field', $displayData, $layoutBasePath, array('field'=>'created_by')); ?>
</dl>
