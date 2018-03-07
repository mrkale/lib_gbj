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
 * View for handling one record of agenda.
 *
 * @since  3.7
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
	protected $item;

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
		$prefix     = '';

		if ($this->isParent())
		{
			$prefix = $this->model->parent->title . JText::_('LIB_GBJ_TITLE_SEPARATOR');
		}

		$title = JText::sprintf(strtoupper($component . '_FORM_' . $agenda), $prefix);
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
}
