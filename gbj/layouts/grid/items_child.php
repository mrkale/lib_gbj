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
$options = $this->getOptions();
$record = $displayData->item;
$fieldList = $options->get('fields');

if (is_string($fieldList))
{
	$fieldList = explode(',', $fieldList);
}

$fieldName = $fieldList[0];

// Injected url
if ($record->$fieldName)
{
	$view = $fieldName;
	$parentType = Helper::singular($displayData->getName());
	$id = $record->id;
	$url = Helper::getUrlViewParent($view, $parentType, $id);
	$options->set('url', $url);
}
?>

<?php echo JLayoutHelper::render('grid.items', $displayData, $layoutBasePath, $options); ?>
