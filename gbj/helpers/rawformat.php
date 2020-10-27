<?php
/**
 * @package    Joomla.Library
 * @subpackage  Toolbar
 * @copyright  (c) 2020 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Renders an adapted standard button

 * @since  3.8
 */
class JToolbarButtonRawFormat extends JToolbarButtonStandard
{
	protected $_name = 'RawFormat';

	/**
	 * Get the JavaScript command for the button
	 * Refer to the script function RawFormatSubmitbutton in stead of the
	 * standard Joomla.submitbutton
	 */
	protected function _getCommand($name, $task, $list)
	{
		return	str_replace("Joomla.submitbutton", "RawFormatSubmitbutton",
			parent::_getCommand($name, $task, $list)
		);
	}
}
