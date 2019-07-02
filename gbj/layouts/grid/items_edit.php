<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

$layoutBasePath = Helper::getLayoutBase();

// Injected option
$task = Helper::singular($displayData->getName()) . '.' . Helper::COMMON_LAYOUT_EDIT;
$id = $displayData->item->id;
$url = Helper::getUrl(array('task' => $task, 'id' => $id));

// Options
$options = $this->getOptions();
$options->set('url', $url);
?>

<?php echo JLayoutHelper::render('grid.items', $displayData, $layoutBasePath, $options); ?>
