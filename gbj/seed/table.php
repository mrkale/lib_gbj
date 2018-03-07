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

/**
 * General table methods for all extension's tables
 *
 * @since  3.7
 */
class GbjSeedTable extends JTable
{
	/**
	 * @var   array	 List of error messages language constants for checking.
	 *				 The key is usually a checked field name.
	 */
	protected $errorMsgs = array();

	/**
	 * The flag determining the overall success of the check method.
	 *
	 * @var boolean
	 */
	protected $checkFlag = true;

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
		if ($this->created == $dateNull)
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
		$this->checkTitle();
		$this->checkAlias();

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

		// Clone current table object for checking
		$primaryKeyName = $this->getKeyName();
		$table = clone $this;

		// Verify that the title is unique
		if ($table->load(array($fieldName => $this->$fieldName))
			&& (isset($primaryKeyName)
			&& ($table->$primaryKeyName != $this->$primaryKeyName
			|| $this->$primaryKeyName == 0)))
		{
			if (!isset($this->errorMsgs[$fieldName]) || empty($this->errorMsgs[$fieldName]))
			{
				$this->errorMsgs[$fieldName] = 'LIB_GBJ_ERROR_UNIQUE_TITLE';
			}

			$this->checkFlag = false;
			JFactory::getApplication()->enqueueMessage(JText::_($this->errorMsgs[$fieldName]), 'error');
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

		// Clone current table object for checking
		$primaryKeyName = $this->getKeyName();
		$table = clone $this;

		// Verify that the alias is unique
		if ($table->load(array($fieldName => $this->$fieldName))
			&& (isset($primaryKeyName)
			&& ($table->$primaryKeyName != $this->$primaryKeyName
			|| $this->$primaryKeyName == 0)))
		{
			if (!isset($this->errorMsgs[$fieldName]) || empty($this->errorMsgs[$fieldName]))
			{
				$this->errorMsgs[$fieldName] = 'LIB_GBJ_ERROR_UNIQUE_ALIAS';
			}

			$this->checkFlag = false;
			JFactory::getApplication()->enqueueMessage(JText::_($this->errorMsgs[$fieldName]), 'error');
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
		if (!isset($this->$fieldName) || empty($this->$fieldName))
		{
			return;
		}

		// Warn that the date is in the future
		if (JFactory::getDate($this->$fieldName)->toSql() > JFactory::getDate()->toSql())
		{
			if (!isset($this->errorMsgs[$fieldName]) || empty($this->errorMsgs[$fieldName]))
			{
				$this->errorMsgs[$fieldName] = 'LIB_GBJ_ERROR_FUTURE_DATE';
			}

			JFactory::getApplication()->enqueueMessage(JText::_($this->errorMsgs[$fieldName]), 'warning');
		}
	}
}
