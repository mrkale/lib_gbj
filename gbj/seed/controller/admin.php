<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017-2020 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\String\Normalise;

/**
 * General controller methods for processing list of records in an agenda.
 *
 * @since  3.8
 */
class GbjSeedControllerAdmin extends JControllerAdmin
{
	/**
	 * The current agenda view name.
	 *
	 * @var string
	 */
	private $viewName;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask('unfeatured', 'featured');
		$this->viewName = $this->input->get('view', '', 'word');
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name.
	 * @param   string  $prefix  The class prefix.
	 * @param   array   $config  Configuration array for model.
	 *
	 * @return  object  The model.
	 */
	public function getModel($name = '', $prefix = '', $config = array())
	{
		$name = empty($name)
			? Helper::singular($this->input->get('view', '', 'word'))
			: $name;

		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Method to toggle the featured setting.
	 *
	 * @return  void
	 */
	public function featured()
	{
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$app = JFactory::getApplication();

		$user   = JFactory::getUser();
		$ids    = $this->input->get('cid', array(), 'array');
		$values = array('featured' => 1, 'unfeatured' => 0);
		$task   = $this->getTask();
		$value  = JArrayHelper::getValue($values, $task, 0, 'int');

		// Get the model
		$model = $this->getModel(Helper::singular($this->viewName));

		// Access checks
		foreach ($ids as $i => $id)
		{
			$item = $model->getItem($id);

			if (!$user->authorise('core.edit.state', Helper::getName() . '.component'))
			{
				// Prune items that you can't change.
				unset($ids[$i]);
				$app->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_EDITCOMMON_STATE_NOT_PERMITTED'), 'notice');
				$this->setRedirect(Helper::getUrlView($this->viewName));

				return;
			}
		}

		// Feature/Unfeature the items
		if (!$model->featured($ids, $value))
		{
			$app->enqueueMessage($model->getError(), 'warning');
			$this->setRedirect(Helper::getUrlView($this->viewName));

			return;
		}

		$this->setRedirect(Helper::getUrlView($this->viewName));
	}

	/**
	 * Method to redirect to a child agenda.
	 *
	 * @param   string  $method  The calling method name containing child agenda.
	 *
	 * @return  void
	 */
	protected function enterAgendaChild($method)
	{
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$parentType = Helper::singular($this->viewName);
		$ids = $this->input->get('cid', array(), 'array');
		JArrayHelper::toInteger($ids);
		$parentId = $ids[0];

		// Go to child agenda
		$parts = explode(' ', Normalise::fromCamelCase($method));
		$this->setRedirect(Helper::getUrlViewParent(strtolower(end($parts)), $parentType, $parentId));
	}

	/**
	 * Method to redirect to a parent agenda.
	 *
	 * @param   string  $method  The calling method name containing parent agenda.
	 *
	 * @return  void
	 */
	protected function enterAgendaParent($method)
	{
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Go to parent view without parent filter
		$parts = explode(' ', Normalise::fromCamelCase($method));
		$this->setRedirect(Helper::getUrlViewParentDel(strtolower(end($parts)), $this->viewName));
	}

	/**
	 * Typical view method for MVC based architecture
	 *
	 * This function is provide as a default implementation, in most cases
	 * you will need to override it in your own controllers.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
	 *
	 * @return  \JControllerLegacy  A \JControllerLegacy object to support chaining.
	 *
	 * @since   3.0
	 */
	public function display($cachable = false, $urlparams = array())
	{
		parent::display($cachable, $urlparams);
		JRequest::setVar('task', '');

		return $this;
	}
}
