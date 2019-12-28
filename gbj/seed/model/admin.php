<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017-2019 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\String\Normalise;

/**
 * General model methods for the detail of a record in an agenda.
 *
 * @since  3.8
 */
abstract class GbjSeedModelAdmin extends JModelAdmin
{
	/**
	 * Parameter name for edited record.
	 *
	 * @var string
	 */
	protected $recordPrmName;

	/**
	 * @var  object  The object with parent record.
	 */
	public $parent;

	/**
	 * @var  string  The parent type, i.e., root name of a parent field.
	 */
	public $parentType;

	/**
	 * @var   array	 List of field parameters from grid form.
	 */
	protected $recordFields = array();

	/**
	 * @var   array  Associated array with list of various forms of coded fields
	 *				 as a subset of form fields starting with "id_".
	 *               [$fieldName]['root'] -- corresponding code table root names
	 */
	public $codedFields = null;

	/**
	 * @var array   Allowed batch commands
	 */
	protected $batch_commands = array();

	/**
	 * @var object  Object for caching current form
	 */
	protected $form;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JModelLegacy
	 * @since   12.2
	 */
	public function __construct($config = array())
	{
		$this->text_prefix = strtoupper(Helper::getName());
		$this->recordPrmName = Helper::getAgendaPrmEditData($this->getName());

		$this->processParent();

		parent::__construct($config);

		// Set batch commands for coded fields
		foreach ($this->getCodedFields() as $fieldForms)
		{
			$this->batch_commands[$fieldForms['root'] . '_id']
				= 'batch' . Helper::proper($fieldForms['root']);
		}
	}

	/**
	 * Method to get a table object, loading it if required.
	 *
	 * @param   string  $name     The table name.
	 * @param   string  $prefix   The class prefix.
	 * @param   array   $options  Configuration array for table.
	 *
	 * @return  object  The table object.
	 */
	public function getTable($name = '', $prefix = '', $options = array())
	{
		$name = empty($name) ? $this->getName() : $name;
		$prefix = empty($prefix) ? Helper::getClassTable() : $prefix;

		return JTable::getInstance($name, $prefix, $options);
	}

	/**
	 * Getting the form from the model.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure.
	 */
	public function getForm($data = array(), $loadData = true)
	{
		if (is_object($this->form))
		{
			return $this->form;
		}

		// Get data without data
		$modelName = $this->getName();
		$formName = strtolower(Helper::getName($modelName));
		$formSource = $modelName;
		$formOptions = array(
			'control' => 'jform',
			'load_data' => false,
		);
		$form = $this->loadForm(
			$formName,
			$formSource,
			$formOptions
		);

		if (empty($form))
		{
			return false;
		}

		// Load all sub forms to the form recursively
		do
		{
			$isSubform = false;

			// Traverse through all field sets
			foreach ($form->getFieldsets() as $fieldSet)
			{
				// Traverse through all fields in a field set
				foreach ($form->getFieldset($fieldSet->name) as $fieldObject)
				{
					if ($fieldObject->getAttribute('type') == 'subform')
					{
						$isSubform = true;
						$fieldName = $fieldObject->getAttribute('name');
						$subformSource = $fieldObject->getAttribute('formsource')
							?? Helper::getLibraryDir(true)
							. DIRECTORY_SEPARATOR
							. Helper::COMMON_FORM_BASEDIR
							. DIRECTORY_SEPARATOR
							. $fieldName
							. '.xml';
						$form->loadFile($subformSource);
						$form->removeField($fieldName);
					}
				}
			}
		}
		while ($isSubform);

		if ($loadData)
		{
			// Create form as new with data
			$formOptions['load_data'] = true;
			$form = $this->loadForm(
				$formName,
				$formSource,
				$formOptions,
				true
			);
			$this->form = $form;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  An array with data for the form.
	 */
	protected function loadFormData()
	{
		$app = JFactory::getApplication();
		$record = $app->getUserState($this->recordPrmName);

		if (is_null($record))
		{
			$table = $this->getTable();
			$fields = $table->getFields();
			$origid = $app->input->getInt(Helper::COMMON_URL_VAR_CLONED_ID);
			$record = $this->getItem($origid);

			// Force data for duplicated record from grid
			if ($origid)
			{
				$record->id = null;
				$record->state = Helper::COMMON_STATE_UNPUBLISHED;

				if (array_key_exists('title', $fields))
				{
					$record->title .= JText::_('LIB_GBJ_CLONE');
				}

				if (array_key_exists('alias', $fields))
				{
					$record->alias .= JText::_('LIB_GBJ_CLONE');
				}

				if (array_key_exists('date_on', $fields))
				{
					$record->date_on = null;
				}

				if (array_key_exists('date_off', $fields))
				{
					$record->date_off = null;
				}

				if (array_key_exists('date_out', $fields))
				{
					$record->date_out = null;
				}

				if (array_key_exists('period', $fields))
				{
					$record->period = null;
				}

				if (array_key_exists('lifespan', $fields))
				{
					$record->lifespan = null;
				}
			}

			// Default title if required
			$fieldName = 'title';

			if (array_key_exists($fieldName, $fields) && empty($record->$fieldName))
			{
				$record->$fieldName = $this->getNewTitle();
			}

			// Generate alias if required; preferably from title
			$fieldName = 'alias';

			if (array_key_exists($fieldName, $fields)
			&& empty($record->$fieldName)
			&& $this->isXmlRequired($fieldName))
			{
				$default = $this->getXmlDefault($fieldName);
				$title = $record->alias ?? $default ?? $record->title;
				$record->$fieldName = JFilterOutput::stringURLSafe($title);
			}

			// Generate start date if required; preferably as current date
			$fieldName = 'date_on';

			if (array_key_exists($fieldName, $fields) && empty($record->$fieldName)
				&& $this->isXmlRequired($fieldName))
			{
				$record->$fieldName = JFactory::getDate()->toSQL();
			}

			/*
			 * Generate stop date if required; preferably from start date
			 * as next day otherwise as current date.
			 */
			$fieldName = 'date_off';

			if (array_key_exists($fieldName, $fields) && empty($record->$fieldName)
				&& $this->isXmlRequired($fieldName))
			{
				$fieldStart = 'date_on';

				if (array_key_exists($fieldStart, $fields)
					&& !empty($record->$fieldStart)
					&& JFactory::getDate($record->$fieldStart)->toUnix() >= 0
				)
				{
					$startDate = JFactory::getDate($record->$fieldStart);
					$stopDate = $startDate->modify('+1 days');
					$record->$fieldName = $stopDate->toSQL();
				}
				else
				{
					$stopDate = JFactory::getDate();
				}

				$record->$fieldName = $stopDate->toSQL();
			}

			// Set the first parent identifier if any
			if (is_object($this->parent))
			{
				$parentField = Helper::COMMON_FIELD_CODED_PREFIX . $this->parentType;

				if (array_key_exists($parentField, $this->recordFields))
				{
					$record->$parentField = (int) $this->parent->id;
				}
			}

			// Convert object to the output array
			$record = get_object_vars($record);
		}

		// Save original record to the model status
		$app->setUserState($this->recordPrmName, $record);

		return $record;
	}

	/**
	 * Method to prepare and sanitize the table data prior to saving.
	 *
	 * @param   JTable  $table  A reference to a JTable object.
	 *
	 * @return  void
	 */
	protected function prepareTable($table)
	{
		$app = JFactory::getApplication();
		$fields = $table->getFields();

		// Trim all fields
		foreach ($fields as $fieldName => $field)
		{
			if (is_string($table->$fieldName))
			{
				$table->$fieldName = trim($table->$fieldName);
			}

			// Prepare by database date type
			switch (strtolower($field->Type))
			{
				case 'date':
				case 'datetime':
				case 'timestamp':
					// Sanitize datetime string
					if ($table->$fieldName)
					{
						$table->$fieldName = JFactory::getDate($table->$fieldName)->toSql();
					}
					else
					{
						$table->$fieldName = $this->getDbo()->getNullDate();
					}
					break;

				default:
					break;
			}
		}

		// Prepare title
		if (array_key_exists('title', $fields))
		{
			$table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
		}

		// Prepare alias
		if (array_key_exists('alias', $fields))
		{
			// Generate new
			if (empty($table->alias) && $this->getXmlDefaulted('alias'))
			{
				$title = $table->title ?? $this->getNewTitle();
				$table->alias = JFilterOutput::stringURLSafe($title);
			}

			$table->alias = htmlspecialchars_decode($table->alias, ENT_QUOTES);
		}

		// Force data to items of a cloned record from detail
		$task = $app->input->get('task');
		$app->setUserState(self::getName(Helper::COMMON_SESSION_TASK), $task);

		if ($task === 'save2copy')
		{
			$table->state = Helper::COMMON_STATE_UNPUBLISHED;

			if (array_key_exists('title', $fields))
			{
				$table->title .= JText::_('LIB_GBJ_CLONE');
			}

			if (array_key_exists('alias', $fields))
			{
				$table->alias .= JText::_('LIB_GBJ_CLONE');
			}

			if (array_key_exists('date_on', $fields))
			{
				$table->date_on = JFactory::getDate()->toSql();
			}

			if (array_key_exists('date_off', $fields))
			{
				$table->date_off = $this->getDbo()->getNullDate();
			}

			if (array_key_exists('date_out', $fields))
			{
				$table->date_out = $this->getDbo()->getNullDate();
			}
		}
	}

	/**
	 * Method to toggle the featured setting.
	 *
	 * @param   array    $pks    The ids of the items to toggle.
	 * @param   integer  $value  The value to toggle to.
	 *
	 * @return  boolean  True on success.
	 */
	public function featured($pks, $value = 0)
	{
		$pks = (array) $pks;
		JArrayHelper::toInteger($pks);

		if (empty($pks))
		{
			return false;
		}

		try
		{
			$db = $this->getDbo();
			$query = $db->getQuery(true);
			$query->update($this->getTable()->getTableName());
			$query->set('featured = ' . (int) $value);
			$query->where('id IN (' . implode(',', $pks) . ')');
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return false;
		}

		$this->cleanCache();

		return true;
	}

	/**
	 * Method for processing the parent relationship.
	 *
	 * @return  void
	 */
	public function processParent()
	{
		if (!is_object($this->parent))
		{
			$agenda = $this->getName();
			$this->parent = Helper::getParentRefRecord($agenda);
			$this->parentType = Helper::getParentRefType($agenda);
		}
	}

	/**
	 * Generate new title for the record from the next primary key.
	 *
	 * @return  string  Default title.
	 */
	public function getNewTitle()
	{
		$table = $this->getTable();
		$primaryKeyName = $table->getKeyName();
		$tableName = $table->getTableName();
		$result = null;

		// Calculate next free ID
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select('MAX(' . $primaryKeyName . ')')
			->from($db->quoteName($tableName));
		$db->setQuery($query);

		try
		{
			$number = $db->loadResult() + 1;
			$result = JText::sprintf('LIB_GBJ_NEW_TITLE', $number);
		}
		catch (RuntimeException $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}

		return $result;
	}

	/**
	 * Batch setting of coded field to a list of items
	 *
	 * @param   string  $method     A name of calling method.
	 * @param   string  $value      The id of the new value.
	 * @param   array   $pks        An array of row IDs.
	 * @param   array   $contexts   An array of item contexts.
	 *
	 * @return  boolean Flag about table existence.
	 */
	protected function processBatch($method, $value, $pks, $contexts)
	{
		// Set the variables
		$app = JFactory::getApplication();
		$user = $this->user;
		$table = $this->table;

		// Separate codebook agenda as the last part of the camel case method
		$parts = explode(' ', Normalise::fromCamelCase($method));
		$tableField = Helper::COMMON_FIELD_CODED_PREFIX . strtolower(end($parts));

		foreach ($pks as $pk)
		{
			if ($user->authorise('core.edit', $contexts[$pk]))
			{
				$table->reset();
				$table->load($pk);
				$update = false;

				if ($table->{$tableField} != (int) $value)
				{
					$table->{$tableField} = (int) $value;
					$update = true;
				}

				if ($update && !$table->store())
				{
					$app->enqueueMessage($table->getError(), 'error');

					return false;
				}
			}
			else
			{
				$app->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'), 'error');

				return false;
			}
		}

		$this->cleanCache();

		return true;
	}

	/**
	 * Determine if a form field is defined as required in the XML form.
	 *
	 * @param   string   $fieldName   The name of a form field.
	 *
	 * @return boolean   Flag determining if the field is required in the form.
	 */
	protected function isXmlRequired($fieldName)
	{
		$required = $this->getForm(null, false)
			->getField($fieldName)
			->getAttribute('required', 'false');

		return filter_var($required, FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Extract default text from the form field in the XML form.
	 *
	 * @param   string   $fieldName   The name of a form field.
	 *
	 * @return string   String (including empty) from default in the form.
	 */
	protected function getXmlDefault($fieldName)
	{
		$default = $this->getForm(null, false)
			->getField($fieldName)
			->getAttribute('default');

		return $default;
	}

	/**
	 * Extract defaulted flag from the form field in the XML form.
	 *
	 * @param   string   $fieldName   The name of a form field.
	 *
	 * @return boolean   Flag from defaulted in the form.
	 */
	protected function getXmlDefaulted($fieldName)
	{
		$defaulted = $this->getForm(null, false)
			->getField($fieldName)
			->getAttribute('defaulted', 'true');

		return filter_var($defaulted, FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Extract hint text from the form field in the XML form.
	 *
	 * @param   string   $fieldName   The name of a form field.
	 *
	 * @return string   String (including empty) from hint in the form.
	 */
	protected function getXmlHint($fieldName)
	{
		$hint = $this->getForm(null, false)
			->getField($fieldName)
			->getAttribute('hint', '');

		return $hint;
	}

	/**
	 * Separate fields from form field set "recordfields" to the array.
	 *
	 * @return   array  List of field objects indexed by field name.
	 */
	public function getRecordFields()
	{
		if (is_array($this->recordFields) && count($this->recordFields) > 0)
		{
			return $this->recordFields;
		}

		// For case than there are no fields
		$this->recordFields = array();
		$form = $this->getForm(null, false);

		foreach ($form->getFieldset('recordfields') as $fieldObject)
		{
			$this->recordFields[$fieldObject->getAttribute('name')] = $fieldObject;
		}

		return $this->recordFields;
	}

	/**
	 * Separate coded fields with names prefixed with 'id_' from all form fields.
	 *
	 * @return   array  List of coded fields objects indexed by field name.
	 */
	public function getCodedFields()
	{
		if (is_array($this->codedFields))
		{
			return $this->codedFields;
		}

		// For case than there are no coded fields
		$this->codedFields = array();

		foreach (array_keys($this->getRecordFields()) as $fieldName)
		{
			if (Helper::isCodedField($fieldName))
			{
				$this->codedFields[$fieldName]['root'] = Helper::getCodedRoot($fieldName);
			}
		}

		return $this->codedFields;
	}
}
