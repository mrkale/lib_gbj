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
 * View for handling records of an agenda
 *
 * @since  3.7
 */
class GbjSeedViewList extends GbjSeedViewDetail
{
	/**
	 * The object with page list.
	 *
	 * @var object
	 */
	protected $pagination;

	/**
	 * The number of record to display.
	 *
	 * @var integer
	 */
	public $total;

	/**
	 * The number of displayed columns in a grid.
	 *
	 * @var  integer
	 */
	public $columns = 0;

	/**
	 * List of tool bar items to be ignored.
	 *
	 * @var   array
	 */
	protected $toolbarBlackList;

	/**
	 * Method to display domain records.
	 *
	 * @param   string  $tpl  The name of the template file to parse.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		// Execute model methods
		$this->state = $this->get('State');
		$this->pagination = $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');

		// Process filters in regards to a parent
		if ($this->isParent())
		{
			$this->filterForm->setValue($this->model->parentType, 'filter', $this->model->parentId);
			$this->filterForm->setFieldAttribute($this->model->parentType, 'disabled', true, 'filter');
		}

		$this->activeFilters = $this->get('ActiveFilters');

		$app = JFactory::getApplication();

		// Generate toolbar and side bar only in administrative mode
		if ($app->isClient('administrator'))
		{
			$this->addToolbar();
			$this->addSidebar();
		}

		parent::display($tpl);
	}

	/**
	 * Method to create the toolbar for handling agenda records.
	 *
	 * @return  void
	 */
	protected function addToolbar()
	{
		$viewList = $this->getName();
		$viewRecord = Helper::singular($viewList);
		$component = Helper::getName();

		$canDo	= JHelperContent::getActions($component, 'category');
		$bar = JToolBar::getInstance('toolbar');
		$prefix = '';

		if ($this->isParent())
		{
			$prefix = $this->model->parent->title . JText::_('LIB_GBJ_TITLE_SEPARATOR');
		}

		$title = JText::sprintf(strtoupper($component . '_FORM_' . $viewList), $prefix);
		JToolbarHelper::title($title);

		JToolbarHelper::addNew($viewRecord . '.add');

		if ($canDo->get('core.edit'))
		{
			JToolbarHelper::editList($viewRecord . '.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::publish($viewList . '.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish($viewList . '.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolbarHelper::custom($viewList . '.featured', 'featured.png', 'featured.png', 'JFEATURE', true);
			JToolbarHelper::custom($viewList . '.unfeatured', 'unfeatured.png', 'unfeatured.png', 'JUNFEATURE', true);
			JToolbarHelper::archiveList($viewList . '.archive');
			JToolbarHelper::checkin($viewList . '.checkin');
		}

		// Add a trash button
		if ($this->state->get('filter.state') == Helper::COMMON_STATE_TRASHED
			&& $canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList(JGLOBAL_CONFIRM_DELETE, $viewList . '.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash($viewList . '.trash');
		}

		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::preferences(Helper::getName());
		}

		// Add a batch button
		if (!in_array('batch', (array) $this->toolbarBlackList)
			&& $canDo->get('core.create')
			&& $canDo->get('core.edit')
			&& $canDo->get('core.edit.state'))
		{
			$layout = new JLayoutFile('joomla.toolbar.batch');
			$dhtml = $layout->render(array('title' => JText::_('JTOOLBAR_BATCH')));
			$bar->appendButton('Custom', $dhtml, 'batch');
		}

		// Add an exit button
		if ($this->isParent())
		{
			$viewName = $this->getName();
			$langConst = strtoupper(Helper::getName(array('FORM', $viewName), '_'));
			$action = '.enter' . Helper::proper(Helper::plural($this->model->parentType));
			JToolbarHelper::custom(
				$viewName . $action,
				'exit.png',
				'exit-2.png',
				JText::sprintf($langConst, JText::_('LIB_GBJ_BUTTON_EXIT')),
				false
			);
		}
	}

	/**
	 * Method to create the side bar for handling submenus and filters.
	 *
	 * @return  void
	 */
	protected function addSidebar()
	{
		JHtmlSidebar::setAction(Helper::getUrlView($this->getName()));
		Helper::addSubmenu($this->getName());
		$this->sidebar = JHtmlSidebar::render();
	}

	/**
	 * Generate HTML code for batch processing form
	 * from current user and component.
	 *
	 * @return  string   Rendering statement for batch processing.
	 */
	protected function getBatchForm()
	{
		$user = JFactory::getUser();
		$component = Helper::getName();

		if ($user->authorise('core.create', $component)
			&& $user->authorise('core.edit', $component)
			&& $user->authorise('core.edit.state', $component))
		{
			$html = JHtml::_(
				'bootstrap.renderModal',
				'collapseModal',
				array(
					'title' => JText::_('LIB_GBJ_BATCH_OPTIONS'),
					'footer' => $this->loadTemplate('batch_footer'),
				),
				$this->loadTemplate('batch_body')
			);
		}

		return $html;
	}

	/**
	 * Generate HTML code for entering child agenda.
	 *
	 * @param   string $agenda  The name of child agenda.
	 *
	 * @return  void
	 */
	protected function addButtonEnter($agenda)
	{
		$viewName = $this->getName();
		$langConst = strtoupper(Helper::getName(array('FORM', $agenda), '_'));
		$action = '.enter' . Helper::proper(Helper::plural($agenda));
		JToolbarHelper::custom(
			$viewName . $action,
			'enter.png',
			'enter-2.png',
			JText::sprintf($langConst, JText::_('LIB_GBJ_BUTTON_ENTER')),
			true
		);
	}
}
