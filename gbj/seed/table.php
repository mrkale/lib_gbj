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
 * General table methods for all extension's tables
 *
 * @since  3.8
 */
class GbjSeedTable extends JTable
{
	/**
	 * @var   array	 List of error messages language constants for checking.
	 *				 The key is usually a checked field name or concatenation
	 *				 of more fields with dot.
	 * .
	 */
	protected $errorMsgs = array();

	/**
	 * The flag determining the overall success of the check method.
	 *
	 * @var boolean
	 */
	protected $checkFlag = true;

	/**
	 * The flag determining raising warning instead of default error.
	 *
	 * @var boolean
	 */
	protected $checkWarning = false;

	/**
	 * Object constructor to set table and key fields.
	 *
	 * @param   JDatabaseDriver  $db     JDatabaseDriver object.
	 * @param   string           $table  Name of the table to model.
	 * @param   mixed            $key    Name of the primary key field in the table or array of field names that compose the primary key.
	 *
	 * @since   11.1
	 */
	public function __construct($db, $table = null, $key = null)
	{
		$table = $table ?? $this->getTableName();
		$key = $key ?? 'id';
		parent::__construct($table, $key, $db);

		$this->setColumnAlias('published', 'state');
		$this->checkFlag = true;
	}

	/**
	 * Method to get the database table name for the class.
	 *
	 * @param   string $parentField  The name of a parent field.
	 *
	 * @return  string  The name of the database table being modeled.
	 *
	 * @since   11.1
	 */
	public function getTableName($parentField = null)
	{
		if (empty($this->_tbl))
		{
			$parts = explode(' ', Normalise::fromCamelCase(get_called_class()));
			$agenda = end($parts);

			if (!is_null($parentField))
			{
				$parent = Helper::getParentRefRecord($agenda);
				$agenda = $parent->$parentField;
			}

			$tableName = Helper::getTable($agenda);

			return $tableName;
		}
		else
		{
			return parent::getTableName();
		}
	}

	/**
	 * Method to store a row in the database from the JTable instance properties.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 */
	public function store($updateNulls = false)
	{
		$dateSql = JFactory::getDate()->toSql();
		$dateNull = $this->getDbo()->getNullDate();
		$userId = JFactory::getUser()->get('id');

		// Timestamp - modified record
		$this->modified = $dateSql;
		$this->modified_by = $userId;

		// Timestamp - new record
		if (!$this->id)
		{
			$this->created = $dateSql;
			$this->created_by = $userId;
		}

		// Set current date if creation date is empty
		if (Helper::isEmptyDate($this->created))
		{
			$this->created = $dateSql;
		}

		// Set current user if creation user is empty
		if (!$this->created_by)
		{
			$this->created_by = $userId;
		}

		// Set publish_up to null date if not set
		if (!$this->publish_up)
		{
			$this->publish_up = $dateNull;
		}

		// Set publish_down to null date if not set
		if (!$this->publish_down)
		{
			$this->publish_down = $dateNull;
		}

		// Always publish nulls
		return parent::store(true);
	}

	/**
	 * Method to perform sanity checks on the JTable instance properties to ensure they are safe to store in the database.
	 *
	 * Child classes should override this method to make sure the data they are storing in the database is safe and as expected before storage.
	 *
	 * @return  boolean  True if the instance is sane and able to be stored in the database.
	 *
	 * @since   11.1
	 */
	public function check()
	{
		$this->checkTitleDate();
		$this->checkAlias();
		$this->checkEmpty();
		$this->checkDate('date_on');
		$this->checkDate('date_off');
		$this->checkDate('date_out');
		$this->checkDatesReverse();

		// Result
		if ($this->checkFlag)
		{
			return parent::check();
		}
		else
		{
			$this->setError(JText::_('LIB_GBJ_ERROR_FORM_FIELDS'));

			return $this->checkFlag;
		}
	}

	/**
	 * Raise error of warning.
	 *
	 * At finish the warning flag and internally set exception message are reset.
	 *
	 * @param   string $errorIdx    The index to the list of error messages.
	 *							    Usually a name of a field or concatenated fields
	 *                              to be checked.
	 * @param   string $errorLang   The language constant for an exception message.
	 *							    Usually a name of a field or concatenated fields
	 *                              to be checked.
	 *
	 * @return void
	 */
	private function raiseError($errorIdx, $errorLang)
	{
		$resetMsg = false;

		if (!isset($this->errorMsgs[$errorIdx]) || empty($this->errorMsgs[$errorIdx]))
		{
			$resetMsg = true;
			$this->errorMsgs[$errorIdx] = $errorLang;
		}

		if (!$this->checkWarning)
		{
			$this->checkFlag = false;
		}

		$errorType = $this->checkWarning ? 'warning' : 'error';
		JFactory::getApplication()->enqueueMessage(JText::_($this->errorMsgs[$errorIdx]), $errorType);

		// Reset
		$this->checkWarning = false;

		if ($resetMsg)
		{
			$this->errorMsgs[$errorIdx] = null;
		}
	}

	/**
	 * Determine if there is a duplicate record with the same input fields.
	 *
	 * @param   array $fieldList   List of field name and values for searching a record(s).
	 * @param   array $keyList     List of field name and values for primary key(s).
	 *
	 * @return void
	 */
	protected function isDuplicateRecord($fieldList, $keyList)
	{
		$condOr = array();
		$db = $this->getDbo();
		$tableName = $this->getTableName();
		$query = $db->getQuery(true)
			->select('*')
			->from($tableName);
		$fields = array_keys($this->getProperties());

		// Search filter
		foreach ($fieldList as $field => $value)
		{
			if (in_array($field, $fields))
			{
				$query->where($db->quoteName($field) . '=' . $db->quote($value));
			}
		}

		// Negative filter conditions
		foreach ($keyList as $field => $value)
		{
			$condOr[] = $db->quoteName($field) . '<>' . $db->quote($value ?? 0);
		}

		$query->extendWhere('AND', $condOr, 'OR');
		$db->setQuery($query);

		return !is_null($db->loadResult());
	}

	/**
	 * Check the validity of the title field.
	 *
	 * @param   string $fieldName  The name of a field to be checked.
	 *
	 * @return void
	 */
	protected function checkTitle($fieldName = 'title')
	{
		// Field is not used
		if (!isset($this->$fieldName) || empty($this->$fieldName))
		{
			return;
		}

		$primaryKeyName = $this->getKeyName();

		if ($this->isDuplicateRecord(
			array($fieldName => $this->$fieldName),
			array($primaryKeyName => $this->$primaryKeyName)
		))
		{
			$this->raiseError($fieldName, 'LIB_GBJ_ERROR_UNIQUE_TITLE');
		}
	}

	/**
	 * Check the validity of the pair of title and date field.
	 *
	 * @param   string $fieldTitle  The name of a field with title.
	 * @param   string $fieldDate   The name of a field with date.
	 *
	 * @return void
	 */
	protected function checkTitleDate($fieldTitle = 'title', $fieldDate = 'date_on')
	{
		// Title field is not used
		if (!isset($this->$fieldTitle) || empty($this->$fieldTitle))
		{
			return;
		}

		// Date field is not used
		if (!isset($this->$fieldDate) || Helper::isEmptyDate($this->$fieldDate))
		{
			return;
		}

		$primaryKeyName = $this->getKeyName();

		if ($this->isDuplicateRecord(
			array(
				$fieldTitle => $this->$fieldTitle,
				$fieldDate => $this->$fieldDate
			),
			array($primaryKeyName => $this->$primaryKeyName)
		))
		{
			$fieldName = $fieldTitle . '.' . $fieldDate;
			$this->checkWarning = true;
			$this->raiseError($fieldName, 'LIB_GBJ_ERROR_UNIQUE_TITLEDATE');
		}
	}

	/**
	 * Check the validity of the alias field.
	 *
	 * @param   string $fieldName  The name of a field to be checked.
	 *
	 * @return void
	 */
	protected function checkAlias($fieldName = 'alias')
	{
		// Field is not used
		if (!isset($this->$fieldName) || empty($this->$fieldName))
		{
			return;
		}

		$primaryKeyName = $this->getKeyName();

		if ($this->isDuplicateRecord(
			array($fieldName => $this->$fieldName),
			array($primaryKeyName => $this->$primaryKeyName)
		))
		{
			$this->raiseError($fieldName, 'LIB_GBJ_ERROR_UNIQUE_ALIAS');
		}
	}

	/**
	 * Check the date in the future.
	 *
	 * @param   string $fieldName  The name of a field to be checked.
	 *
	 * @return void
	 */
	protected function checkDate($fieldName)
	{
		// Field is not used
		if (!isset($this->$fieldName) || Helper::isEmptyDate($this->$fieldName))
		{
			return;
		}

		// Warn that the date is in the future
		if (JFactory::getDate($this->$fieldName)->toSql() > JFactory::getDate()->toSql())
		{
			$this->checkWarning = true;
			$this->raiseError($fieldName, 'LIB_GBJ_ERROR_FUTURE_DATE');
		}
	}

	/**
	 * Check the pair of dates for reverse order.
	 *
	 * @param   string $fieldDateOne   The name of a field with start date.
	 * @param   string $fieldDateTwo   The name of a field with end date.
	 *
	 * @return void
	 */
	protected function checkDatesReverse($fieldDateOne = 'date_on', $fieldDateTwo = 'date_off')
	{
		// Date on field is not used
		if (!isset($this->$fieldDateOne) || Helper::isEmptyDate($this->$fieldDateOne))
		{
			return;
		}

		// Date off field is not used
		if (!isset($this->$fieldDateTwo) || Helper::isEmptyDate($this->$fieldDateTwo))
		{
			return;
		}

		// End date is sooner than start date
		if ($this->$fieldDateTwo < $this->$fieldDateOne)
		{
			$fieldName = $fieldDateOne . '.' . $fieldDateTwo;
			$this->raiseError($fieldName, 'LIB_GBJ_ERROR_DATEOFF_LESS');
		}
	}

	/**
	 * Check the pair of dates for equality.
	 *
	 * @param   string $fieldDateOne   The name of a field with start date.
	 * @param   string $fieldDateTwo   The name of a field with end date.
	 *
	 * @return void
	 */
	protected function checkDatesEqual($fieldDateOne = 'date_on', $fieldDateTwo = 'date_off')
	{
		// Date on field is not used
		if (!isset($this->$fieldDateOne) || Helper::isEmptyDate($this->$fieldDateOne))
		{
			return;
		}

		// Date off field is not used
		if (!isset($this->$fieldDateTwo) || Helper::isEmptyDate($this->$fieldDateTwo))
		{
			return;
		}

		// End date is equal to start date
		if ($this->$fieldDateTwo == $this->$fieldDateOne)
		{
			$fieldName = $fieldDateOne . '.' . $fieldDateTwo;
			$this->raiseError($fieldName, 'LIB_GBJ_ERROR_DATEOFF_EQUAL');
		}
	}

	/**
	 * Check the negative price.
	 *
	 * @param   string $fieldName  The name of a field to be checked.
	 *
	 * @return void
	 */
	protected function checkPrice($fieldName)
	{
		// Field is not used
		if (!isset($this->$fieldName) || empty($this->$fieldName))
		{
			if (is_string($this->$fieldName))
			{
				$this->$fieldName = null;
			}

			return;
		}

		// Price should be positive
		if ((float) $this->$fieldName < 0)
		{
			$this->raiseError($fieldName, 'LIB_GBJ_ERROR_PRICE_FIELD');
		}
	}

	/**
	 * Check the negative quantity.
	 *
	 * @param   string $fieldName  The name of a field to be checked.
	 *
	 * @return void
	 */
	protected function checkQuantity($fieldName)
	{
		// Field is not used
		if (!isset($this->$fieldName) || empty($this->$fieldName))
		{
			return;
		}

		// Price should be positive
		if ((float) $this->$fieldName < 0)
		{
			$this->raiseError($fieldName, 'LIB_GBJ_ERROR_QUANTITY_FIELD');
		}
	}

	/**
	 * Check the field is empty.
	 *
	 * @param   string $fieldName  The name of a field to be checked.
	 *
	 * @return void
	 */
	protected function checkEmpty($fieldName = 'description')
	{
		// Field is not used
		if (!isset($this->$fieldName))
		{
			return;
		}

		// Warn that the field is empty
		if (empty($this->$fieldName))
		{
			$errMsg = JText::sprintf(
				'LIB_GBJ_ERROR_FIELD_EMPTY',
				JText::_('JGLOBAL_DESCRIPTION')
			);
			$this->checkWarning = true;
			$this->raiseError($fieldName, $errMsg);
		}
	}
}
