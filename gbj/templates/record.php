<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017-2019 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
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
$agendaTitle = JText::_(strtoupper($componentName) . '_' . strtoupper(Helper::plural($agenda)));
$parentTitle = (is_object($this->model->parent) ? $this->model->parent->title : null);
$parentPrefix = $this->escape($parentTitle ?? $tparams->get('page_heading') ?? $agendaTitle);
$itemTitle = $this->item->title ?? Helper::formatDate($this->item->date_on);

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
		<?php echo $itemTitle; ?>
	</h1>
<?php if (!empty($description)): ?>
	<div>
		<h4>
			<?php echo $description; ?>
		</h4>
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
