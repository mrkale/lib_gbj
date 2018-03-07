<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017 Libor Gabaj. All rights reserved.
 * @license    GNU General Public License version 2 or later. See LICENSE.txt, LICENSE.php.
 * @since      3.7
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\String\Normalise;
JFormHelper::loadFieldClass('list');

/**
 * Class for a custom field.
 *
 * @since  3.7
 */
class GbjSeedFieldList extends JFormFieldList
{
	/**
	 * Custom type of the field
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Method to instantiate the form field object.
	 *
	 * @param   JForm  $form  The form to attach to the form field object.
	 *
	 * @since   11.1
	 */
	public function __construct($form = null)
	{
		$parts = explode(' ', Normalise::fromCamelCase(get_called_class()));
		$this->type = end($parts);

		parent::__construct($form);
	}

	/**
	 * Creating HTML options for the select custom field
	 *
	 * @return array  List of HTML option objects
	 */
	public function getOptions()
	{
		$app = JFactory::getApplication();
		$db = JFactory::getDbo();
		$rows = array();
		$query = $db->getQuery(true);

		switch ($this->type)
		{
			case 'Year':
				$table = Helper::getTable($app->input->get('view'));
				$query
					->select('DISTINCT YEAR(a.date_on) AS value')
					->from($db->quoteName($table, 'a'))
					->order('a.date_on DESC');
				$db->setQuery($query);

				try
				{
					$rows = $db->loadObjectList();
				}
				catch (RuntimeException $e)
				{
					$app->enqueueMessage($e->getMessage(), 'warning');
				}

				foreach ($rows as $key => $row)
				{
					$rows[$key]->text = $row->value;
				}
				break;

			case 'Month':
				$table = Helper::getTable($app->input->get('view'));
				$query
					->select(array(
						'DISTINCT MONTH(a.date_on) AS value',
						'MONTHNAME(a.date_on) AS text',
						)
					)
					->from($db->quoteName($table, 'a'))
					->order(1);
				$db->setQuery($query);

				try
				{
					$rows = $db->loadObjectList();
				}
				catch (RuntimeException $e)
				{
					$app->enqueueMessage($e->getMessage(), 'warning');
				}

				foreach ($rows as $key => $row)
				{
					$rows[$key]->text = Helper::proper(JText::_(strtoupper($row->text)));
				}
				break;

			default:
				$table = Helper::getCodebookTable($this->type);

				if (!is_null($table))
				{
					$query
						->select(array($db->quoteName('a.id', 'value'), $db->quoteName('a.title', 'text')))
						->from($db->quoteName($table, 'a'))
						->where($db->quoteName('a.state') . '=' . (int) Helper::COMMON_STATE_PUBLISHED)
						->order($db->quoteName('a.title'));
					$db->setQuery($query);

					try
					{
						$rows = $db->loadObjectList();
					}
					catch (RuntimeException $e)
					{
						// Other than ER_NO_SUCH_TABLE
						if ($e->getCode() <> 1146)
						{
							$app->enqueueMessage($e->getMessage(), 'warning');
						}
					}
				}
				break;
		}

		// Merge any additional options in the XML definition.
		$rows = $this->prependVoidOptions($rows);
		$options = array_merge(parent::getOptions(), $rows);

		return $options;
	}

	/**
	 * Adding HTML options for void items to a selection list.
	 *
	 * @param   array  $rows  List of particular options.
	 *
	 * @return array  List of HTML option objects
	 */
	protected function prependVoidOptions($rows = array())
	{
		$unknown = new stdClass;
		$unknown->value = '0';
		$unknown->text = JText::_('JNONE');
		array_unshift($rows, $unknown);

		return $rows;
	}
}
