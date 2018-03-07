<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017 Libor Gabaj. All rights reserved.
 * @license    GNU General Public License version 2 or later. See LICENSE.txt, LICENSE.php.
 * @since      3.7
 */

// No direct access
defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.caption');
jimport('joomla.html.html.bootstrap');

// Configuration parameters
$componentName = Helper::getExtensionName();
$cparams = JComponentHelper::getParams($componentName);
$tparams = $this->params;

// Data
$agenda = $this->getName();
$viewList = Helper::plural($agenda);
$pageclass_sfx = htmlspecialchars($tparams->get('pageclass_sfx'));
$agendaTitle = JText::_(strtoupper($componentName) . '_SUBMENU_' . strtoupper(Helper::plural($agenda)));
$parentTitle = (is_object($this->model->parent) ? $this->model->parent->title : null);
$parentPrefix = $this->escape($parentTitle ?? $tparams->get('page_heading') ?? $agendaTitle);

if ($tparams->get('show_pagedescription'))
{
	$description = $this->item->description;
}
?>
<div class="<?php echo Helper::getExtensionCore() . $pageclass_sfx; ?>">
	<h1>
		<?php if ($tparams->get('show_pageicon')) : ?>
		<span class="<?php echo $tparams->get('pageicon_class'); ?>"></span>
		<?php endif; ?>
		<a href="<?php echo JRoute::_(Helper::getUrlView($viewList)); ?>">
			<?php echo $parentPrefix; ?>
		</a>
		<?php echo JText::_('LIB_GBJ_TITLE_SEPARATOR'); ?>
		<?php echo $this->item->title; ?>
	</h1>
<?php if (!empty($description)): ?>
	<div>
		<?php echo $description; ?>
	</div>
<?php endif; ?>
	<?php echo JHtml::_('bootstrap.startAccordion', 'slide-agenda', array('active' => 'record', 'toggle' => true)); ?>

	<?php echo JHtml::_('bootstrap.addSlide', 'slide-agenda', JText::_('LIB_GBJ_SLIDE_UPDATE'), 'update'); ?>
	<?php echo JLayoutHelper::render('record.update', $this, Helper::getLayoutBase()); ?>
	<?php echo JHtml::_('bootstrap.endSlide'); ?>

	<?php echo JHtml::_('bootstrap.addSlide', 'slide-agenda', JText::_('LIB_GBJ_SLIDE_FIELDS'), 'record'); ?>
	<?php echo $this->loadTemplate('item'); ?>
	<?php echo JHtml::_('bootstrap.endSlide'); ?>

	<?php
		try
		{
			$statistics = $this->loadTemplate('statistics');
		}
		catch (Exception $e)
		{
		}
		if (isset($statistics))
		{
			echo JHtml::_('bootstrap.addSlide', 'slide-agenda', JText::_('LIB_GBJ_SLIDE_STATS'), 'statistics');
			echo $statistics;
			echo JHtml::_('bootstrap.endSlide');
		}
	?>

	<?php echo JHtml::_('bootstrap.endAccordion'); ?>
</div>
