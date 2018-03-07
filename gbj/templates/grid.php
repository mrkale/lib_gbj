<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017 Libor Gabaj. All rights reserved.
 * @license    GNU General Public License version 2 or later. See LICENSE.txt, LICENSE.php.
 * @since      3.7
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.html.html.bootstrap');

$componentName = Helper::getExtensionName();
$cparams = JComponentHelper::getParams($componentName);

// Configuration parameters
$tparams = $this->params;
$pageclass_sfx = htmlspecialchars($tparams->get('pageclass_sfx'));

// Data
$agenda = $this->getName();
$agendaTitle = JText::_(strtoupper($componentName) . '_SUBMENU_' . strtoupper($agenda));
$parent      = $this->model->parent;
$grandparent = $this->model->grandparent;
$grandparentTitle = is_object($grandparent) ? $grandparent->title : null;
$parentTitle      = is_object($parent) ? $parent->title : null;
$parentType = $this->model->parentType;
$parentView = Helper::plural($parentType);
$parentAgendaTitle = is_object($parent)
	? JText::_(strtoupper($componentName) . '_SUBMENU_' . strtoupper($parentView))
	: null;
$parentPrefix = $grandparentTitle
	?? $this->escape($tparams->get('page_heading', $parentAgendaTitle ?? $agendaTitle));
$agendaLink = JRoute::_(Helper::getUrlViewParentDel($parentView, $agenda));

if ($tparams->get('show_pagedescription'))
{
	$description = $this->isParent() ? $this->model->parent->description : $tparams->get('pagedescription_data');
}
?>
<div class="<?php echo Helper::getExtensionCore() . $pageclass_sfx; ?>">
	<h1>
		<?php if ($tparams->get('show_pageicon')) : ?>
		<span class="<?php echo $tparams->get('pageicon_class'); ?>"></span>
		<?php endif; ?>
	<?php if ($this->isParent()): ?>
		<a href="<?php echo $agendaLink; ?>"><?php echo $parentPrefix; ?></a>
		<?php echo JText::_('LIB_GBJ_TITLE_SEPARATOR'); ?>
		<?php echo $parentTitle; ?>
	<?php else: ?>
		<?php echo $parentPrefix; ?>
	<?php endif; ?>
	</h1>
	<?php if (!empty($description)): ?>
	<div>
		<?php echo $description; ?>
	</div>
	<?php endif; ?>
	<?php if ($this->isParent()): ?>
	<h2><?php echo $agendaTitle; ?></h2>
	<?php endif; ?>
	<?php echo $this->loadTemplate('items'); ?>
</div>
