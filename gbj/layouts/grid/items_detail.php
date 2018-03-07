<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017 Libor Gabaj. All rights reserved.
 * @license    GNU General Public License version 2 or later. See LICENSE.txt, LICENSE.php.
 * @since      3.7
 */

// No direct access
defined('_JEXEC') or die;

$layoutBasePath = Helper::getLayoutBase();

// Injected option
$view = Helper::singular($displayData->getName());
$id = $displayData->item->id;
$url = Helper::getUrl(array('view' => $view, 'id' => $id));

// Options
$options = $this->getOptions();
$options->set('url', $url);
?>

<?php echo JLayoutHelper::render('grid.items', $displayData, $layoutBasePath, $options); ?>
