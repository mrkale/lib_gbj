<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017-2019 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
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
$view = $fieldName;
$parentType = Helper::singular($displayData->getName());
$id = $record->id;
$url = Helper::getUrlViewParent($view, $parentType, $id);
$options->set('url', $url);

echo JLayoutHelper::render('grid.items', $displayData, $layoutBasePath, $options);
