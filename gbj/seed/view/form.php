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
 * View for handling one record of agenda.
 *
 * @since  3.8
 */
class GbjSeedViewForm extends JViewLegacy
{
	/**
	 * The object with model.
	 *
	 * @var  object
	 */
	public $model;

	/**
	 * The object with agenda record.
	 *
	 * @var  string
	 */
	public $item;

	/**
	 * The object with form for an agenda record.
	 *
	 * @var  string
	 */
	protected $form;

	/**
	 * Method to display an agenda record.
	 *
	 * @param   string  $tpl  The name of the template file to parse.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$this->model = $this->getModel();
		$this->item = $this->get('Item');
		$this->form = $this->get('Form');
		$this->addToolbar();

		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("; ", $errors), 500);
		}

		parent::display($tpl);
	}

	/**
	 * Method to create the toolbar for handling an agenda record.
	 *
	 * @return  mixed  Nothing relevant.
	 */
	protected function addToolbar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$agenda		= $this->getName();
		$user		= JFactory::getUser();
		$userId		= $user->get('id');
		$isNew		= ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
		$component  = Helper::getName();
		$title     = '';

		if ($this->isParent())
		{
			$title .= $this->model->parent->title . JText::_('LIB_GBJ_TITLE_SEPARATOR');
		}

		$title .= JText::_(strtoupper($component . '_' . $agenda));
		JToolbarHelper::title($title);

		JToolbarHelper::apply($agenda . '.apply');
		JToolbarHelper::save($agenda . '.save');
		JToolbarHelper::save2new($agenda . '.save2new');
		JToolbarHelper::save2copy($agenda . '.save2copy');
		JToolbarHelper::cancel($agenda . '.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
	}

	/**
	 * Check if the parent record is defined.
	 *
	 * @return  boolean  Flag about parent record existence.
	 */
	public function isParent()
	{
		return is_object($this->model->parent);
	}

	/**
	 * Create HTML string for displaying record detail tab label.
	 *
	 * @return  string  HTML display string.
	 */
	protected function getTabRecord()
	{
		// Component parameters
		$cparams = JComponentHelper::getParams(Helper::getName());
		$bold = $cparams->get('detail_record_tab_bold') ? 'bolder' : 'normal';

		$recordTag = JText::sprintf('LIB_GBJ_OLD_RECORD', $this->item->id);
		$color = $cparams->get('detail_record_tab_color_edit');

		$app = JFactory::getApplication();
		$lastTask = $app->getUserState(self::getName(Helper::COMMON_SESSION_TASK));

		if (empty($this->item->id))
		{
			$recordTag = JText::_('LIB_GBJ_NEW_RECORD');
			$color = $cparams->get('detail_record_tab_color_new');
		}
		elseif ($lastTask === 'save2copy')
		{
			$recordTag = JText::sprintf('LIB_GBJ_COPY_RECORD', $this->item->id);
			$color = $cparams->get('detail_record_tab_color_copy');
		}

		$recordTag = '<span style="color: ' . $color
			. '; font-weight: ' . $bold . ';">'
			. $recordTag . '</span>';

		return $recordTag;
	}
}
