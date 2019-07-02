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
 * Methods supporting editing of the agenda's record.
 *
 * @since  3.8
 */
class GbjSeedControllerForm extends JControllerForm
{
	/**
	 * Method to run batch operations.
	 *
	 * @param   object  $model  The model.
	 *
	 * @return  boolean   True if successful, false otherwise and internal error is set.
	 */
	public function batch($model = null)
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Set the model
		$model = $this->getModel($this->view_item, '', array());

		// Preset the redirect
		$this->setRedirect(
			JRoute::_(
				Helper::getUrlView($this->view_list)
				. $this->getRedirectToListAppend(), false
			)
		);

		return parent::batch($model);
	}
}
