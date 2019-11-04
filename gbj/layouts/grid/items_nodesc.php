<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2019 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

$layoutBasePath = Helper::getLayoutBase();
$record = $displayData->item;

// Inject option for italic, if description is empty
$options = $this->getOptions();

if (empty($record->description))
{
	$options->set('italic', true);
}

echo JLayoutHelper::render('grid.items', $displayData, $layoutBasePath, $options);
