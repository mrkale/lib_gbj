<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017 Libor Gabaj. All rights reserved.
 * @license    GNU General Public License version 2 or later. See LICENSE.txt, LICENSE.php.
 * @since      3.7
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Main controller for a component
 *
 * @since  3.7
 */
class GbjSeedControllerComponent extends JControllerLegacy
{
	/**
	 * Display task realization
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JControllerLegacy  JControllerLegacy object to support chaining.
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$app = JFactory::getApplication();

		// Get parameters from URL
		$viewName = strtolower($app->input->getWord('view', Helper::HELPER_DEFAULT_VIEW));
		$layoutName = strtolower($app->input->getWord('layout', Helper::COMMON_LAYOUT_DEFAULT));

		// Check editing permissions for current user
		$editView = Helper::singular($viewName);

		if ($viewName == $editView
			&& $layoutName == Helper::COMMON_LAYOUT_EDIT)
		{
			$id = $this->input->getInt('id');
			$action = Helper::getName() . '.edit.' . $editView;

			if (!$this->checkEditId($action, $id))
			{
				$app->enqueueMessage(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
				$this->setMessage($this->getError(), 'error');
				$redirectUrl	= Helper::getUrlView();
				$this->setRedirect(JRoute::_($redirectUrl, false));

				return false;
			}
		}

		// Put desired view to URL for displaying
		$app->input->set('view', $viewName);

		parent::display();

		return $this;
	}
}
