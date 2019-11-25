<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017-2019 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

/**
 * View for handling records of an agenda
 *
 * @since  3.8
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
		$this->activeFilters = $this->get('ActiveFilters');

		if ($this->isParent() && is_object($this->filterForm))
		{
			$parentFilterField = Helper::getCodedField($this->model->parentType);
			$this->filterForm->setValue($parentFilterField, 'filter', $this->model->parentId);
			$this->filterForm->setFieldAttribute($parentFilterField, 'disabled', true, 'filter');
		}

		if (JFactory::getApplication()->isClient('administrator'))
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
		$this->toolbarBlackList = (array) $this->toolbarBlackList;
		$title = '';

		if ($this->isParent())
		{
			$title .= $this->model->parent->title . JText::_('LIB_GBJ_TITLE_SEPARATOR');
		}

		$title .= JText::_(strtoupper($component . '_' . $viewList));
		JToolbarHelper::title($title);

		// Add an add button
		if (!in_array('add', $this->toolbarBlackList))
		{
			JToolbarHelper::addNew($viewRecord . '.add');
		}

		// Add an clone button
		if (!in_array('duplicate', $this->toolbarBlackList))
		{
			JToolbarHelper::custom($viewRecord . '.duplicate', 'save-copy', 'save-copy', 'JTOOLBAR_DUPLICATE', true);
		}

		// Add an edit button
		if ($canDo->get('core.edit') && !in_array('edit', $this->toolbarBlackList))
		{
			JToolbarHelper::editList($viewRecord . '.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			// Add a publish button
			if (!in_array('publish', $this->toolbarBlackList))
			{
				JToolbarHelper::publish($viewList . '.publish', 'JTOOLBAR_PUBLISH', true);
			}

			// Add an unpublish button
			if (!in_array('unpublish', $this->toolbarBlackList))
			{
				JToolbarHelper::unpublish($viewList . '.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			}

			// Add a featured button
			if (!in_array('featured', $this->toolbarBlackList))
			{
				JToolbarHelper::custom($viewList . '.featured', 'featured.png', 'featured.png', 'JFEATURE', true);
			}

			// Add an unfeatured button
			if (!in_array('unfeatured', $this->toolbarBlackList))
			{
				JToolbarHelper::custom($viewList . '.unfeatured', 'unfeatured.png', 'unfeatured.png', 'JUNFEATURE', true);
			}

			// Add an archive button
			if (!in_array('archive', $this->toolbarBlackList))
			{
				JToolbarHelper::archiveList($viewList . '.archive');
			}
		}

		// Add a trash button
		if ($this->state->get('filter.state') == Helper::COMMON_STATE_TRASHED
			&& $canDo->get('core.delete') && !in_array('delete', $this->toolbarBlackList))
		{
			JToolBarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', $viewList . '.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state') && !in_array('trash', $this->toolbarBlackList))
		{
			JToolbarHelper::trash($viewList . '.trash');
		}

		if ($canDo->get('core.admin') && !in_array('preferences', $this->toolbarBlackList))
		{
			JToolbarHelper::preferences(Helper::getName());
		}

		// Add a batch button
		if (!in_array('batch', $this->toolbarBlackList)
			&& $canDo->get('core.create')
			&& $canDo->get('core.edit')
			&& $canDo->get('core.edit.state'))
		{
			$layout = new JLayoutFile('joomla.toolbar.batch');
			$dhtml = $layout->render(array('title' => JText::_('JTOOLBAR_BATCH')));
			$bar->appendButton('Custom', $dhtml, 'batch');
		}

		// Add an exit button
		if ($this->isParent() && !in_array('exit', $this->toolbarBlackList))
		{
			$viewName = $this->getName();
			$langConst = strtoupper(Helper::getName($viewName, '_'));
			$action = '.enter' . Helper::proper(Helper::plural($this->model->parentType));
			JToolbarHelper::custom(
				$viewName . $action,
				'exit.png',
				'exit-2.png',
				JText::_('LIB_GBJ_BUTTON_EXIT') . JText::_($langConst),
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
		$langConst = strtoupper(Helper::getName($agenda, '_'));
		$action = '.enter' . Helper::proper(Helper::plural($agenda));
		JToolbarHelper::custom(
			$viewName . $action,
			'enter.png',
			'enter-2.png',
			JText::sprintf($langConst, JText::_('LIB_GBJ_BUTTON_ENTER')),
			true
		);
	}

	/**
	 * Enrich HTML string for displaying statistics.
	 *
	 * @param   array $periodStat  Array with date statistics.
	 *
	 * @return  string  HTML display string.
	 */
	public function htmlStatistics($periodStat = array())
	{
		// Record counts
		$htmlString = parent::htmlStatistics();
		$htmlString .= JText::sprintf('LIB_GBJ_STAT_RECORDS',
			Helper::formatNumber($this->pagination->total, JText::_('LIB_GBJ_FORMAT_RECORDS'))
		);

		// Date range
		if (isset($periodStat['min']) && isset($periodStat['max']))
		{
			$htmlString .= JText::sprintf(JText::_('LIB_GBJ_STAT_RANGE_EXT'), JText::_('LIB_GBJ_STAT_PER'),
				Helper::formatDate($periodStat['min']),
				Helper::formatDate($periodStat['max']),
				Helper::formatPeriodDates($periodStat['min'], $periodStat['max'])
			);
		}

		return $htmlString;
	}
}
