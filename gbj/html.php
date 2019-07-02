<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

/**
 * HTML helper class.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_gbjcodes
 * @since       3.8
 */
abstract class GbjHtml
{
	/**
	 * Show the featured/not-featured icon.
	 *
	 * @param   int     $i          Id of the item.
	 * @param   int     $value      The featured value.
	 * @param   bool    $canChange  Whether the value can be changed or not.
	 * @param   string  $viewName   Name of the view with toggled records.
	 *
	 * @return  string	The anchor tag to toggle featured/unfeatured contacts.
	 *
	 * @since   3.8
	 */
	public static function featured($i, $value = 0, $canChange = true, $viewName = Helper::HELPER_DEFAULT_VIEW)
	{
		// Array of image, task, title, action
		$states = array(
			array('unfeatured', $viewName . '.featured',   'LIB_GBJ_UNFEATURED', 'JGLOBAL_TOGGLE_FEATURED'),
			array('featured',   $viewName . '.unfeatured', 'LIB_GBJ_FEATURED', 'JGLOBAL_TOGGLE_FEATURED'),
		);
		$state = JArrayHelper::getValue($states, (int) $value, $states[1]);
		$icon  = $state[0];

		if ($canChange)
		{
			$html = '<a href="#" onclick="return listItemTask(\'cb' . $i . '\',\'' . $state[1] . '\')" class="btn btn-micro hasTooltip'
				. ($value == 1 ? ' active' : '') . '" title="' . JHtml::tooltipText($state[3]) . '"><span class="icon-' . $icon . '"></span></a>';
		}
		else
		{
			$html = '<a class="btn btn-micro hasTooltip disabled' . ($value == 1 ? ' active' : '') . '" title="'
				. JHtml::tooltipText($state[2]) . '"><span class="icon-' . $icon . '"></span></a>';
		}

		return $html;
	}
}
