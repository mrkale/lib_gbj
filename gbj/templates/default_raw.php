<?php
/**
 * @package    Joomla.Component
 * @copyright  (c) 2020 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

$layoutBasePath = Helper::getLayoutBase();
$content = '';

// Header line
foreach ($this->gridFields as $field)
{
	$content .= JLayoutHelper::render('raw.headers', $this, $layoutBasePath,
		array('field' => $field->getAttribute('name'))
	);
}

$content .= PHP_EOL;

// Body lines
foreach ($this->items as $this->item)
{
	foreach ($this->gridFields as $field)
	{
		$content .= JLayoutHelper::render('raw.items', $this, $layoutBasePath,
			array('field' => $field->getAttribute('name'))
		);
	}

	$content .= PHP_EOL;
}

echo $content;
