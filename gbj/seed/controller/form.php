<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017-2020 Libor Gabaj
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
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask('duplicate', 'duplicate');
	}

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

	/**
	 * Method to duplicate the first selected record.
	 *
	 * @return  boolean   True if successful, false otherwise and internal error is set.
	 */
	public function duplicate()
	{
		$context = "$this->option.edit.$this->context";

		// Access check.
		if (!$this->allowAdd())
		{
			// Set the internal error and also the redirect error.
			$this->setError(\JText::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect(
				\JRoute::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_list
					. $this->getRedirectToListAppend(), false
				)
			);

			return false;
		}

		// Clear the record edit information from the session.
		\JFactory::getApplication()->setUserState($context . '.data', null);

		// Redirect to the edit screen.
		$id = $this->input->get('cid', array(), 'array')[0];
		$this->setRedirect(
			\JRoute::_(
				'index.php?option=' . $this->option . '&view=' . $this->view_item
				. $this->getRedirectToItemAppend(
					$id, Helper::COMMON_URL_VAR_CLONED_ID
				),
				false
			)
		);

		return true;
	}
}
