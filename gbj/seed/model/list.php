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
 * General model methods for the list of records in an agenda.
 *
 * @since  3.7
 */
class GbjSeedModelList extends JModelList
{
	/**
	 * @var   array	 List of field parameters from grid form.
	 */
	protected $gridFields = array();

	/**
	 * @var   array  Associated array with list of various forms of coded fields
	 *				 as a subset of form fields starting with "id_".
	 *               [$fieldName]['column'] -- corresponding table column names
	 *               [$fieldName]['root'] -- corresponding code table root names
	 */
	public $codedFields = null;

	/**
	 * The identifier of a parent record.
	 *
	 * @var  integer
	 */
	public $parentId;

	/**
	 * The type of a parent agenda (database table).
	 *
	 * @var  string
	 */
	public $parentType;

	/**
	 * The object with parent record.
	 *
	 * @var  object
	 */
	public $parent;

	/**
	 * The object with grandparent record.
	 *
	 * @var  object
	 */
	public $grandparent;

	/**
	 * The value of the from field list.fullordering.
	 *
	 * @var  string
	 */
	public $fieldFullordering;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  Associative array of configuration settings.
	 */
	public function __construct($config = array())
	{
		// Button fields blacklisted
		$this->filterBlacklist[] = 'sequence';
		$this->filterBlacklist[] = 'featured';

		// External filter fields
		if (!array_key_exists('filter_fields', (array) $config))
		{
			$config['filter_fields'] = array();
		}

		// Add all form fields
		foreach (array_keys($this->getGridFields()) as $fieldName)
		{
			$config['filter_fields'][] = $fieldName;
		}

		// Add blacklisted fields if needed
		foreach ($this->filterBlacklist as $fieldName)
		{
			if (array_search($fieldName, $config['filter_fields']) === false)
			{
				$config['filter_fields'][] = $fieldName;
			}
		}

		parent::__construct($config);
	}

	/**
	 * Method to set the default sorting parameters and filter states.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication();
		$this->setState('list.filter',
			$this->getUserStateFromRequest(
				$this->context . '.filter.search',
				'filter_search',
				'',
				'string'
			)
		);
		$this->setFilterState('state');
		$this->setFilterState('featured');

		// Set filter state for coded fields
		foreach (array_keys($this->getCodedFields()) as $fieldName)
		{
			$this->setFilterState($fieldName, 'uint');
		}

		// Set filter state for id
		$this->setFilterState(
			'id',
			'uint',
			$this->id = $app->input->getInt(Helper::COMMON_URL_VAR_ID)
		);

		$this->processParent();

		// Application params
		if ($app->isClient('site'))
		{
			$params = $app->getParams(Helper::getName());
			$this->setState('request.params', $params);
		}

		// Default sorting parameters taken from filter form
		if (is_null($ordering) || is_null($direction))
		{
			$errors = $this->getErrors();
			$filterForm = $this->getFilterForm(null, false);

			if ($filterForm === false)
			{
				// Set original errors
				$this->set('_errors', $errors);
			}
			else
			{
				$fullordering = $filterForm->getFieldAttribute(
					'fullordering',
					'default',
					null,
					'list'
				);
				list($ordering, $direction) = explode(' ', $fullordering ?? 'title ASC');
			}
		}

		parent::populateState($ordering, $direction);
	}

	/**
	 * Retrieve list of records from database.
	 *
	 * @return  object  The query for domains.
	 */
	protected function getListQuery()
	{
		$app = JFactory::getApplication();
		$db	= $this->getDbo();
		$tableName = $this->getTable()->getTableName();

		$query = $db->getQuery(true)
			->select('a.*')
			->from($db->quoteName($tableName, 'a'));

		$query = $this->extendQuery($query);

		// Filter by state
		$state = $this->getState('filter.state');

		if ($state === '')
		{
			if ($app->isClient('site'))
			{
				$query->where($db->quoteName('state') . '=' . Helper::COMMON_STATE_PUBLISHED);
			}
			else
			{
				$query->where(
					'(' . $db->quoteName('state') . ' IN ('
					. (int) Helper::COMMON_STATE_UNPUBLISHED . ', '
					. (int) Helper::COMMON_STATE_PUBLISHED
					. '))'
				);
			}
		}
		else
		{
			$this->setFilterQueryNumeric('state', $query);
		}

		// Filter by featured
		$this->setFilterQueryNumeric('featured', $query);

		// Filter by coded fields
		foreach ($this->getCodedFields() as $fieldName => $fieldForms)
		{
			$this->setFilterQueryNumeric($fieldName, $query, $fieldForms['column']);
		}

		// Filter by site id
		if ($app->isClient('site'))
		{
			$this->setFilterQueryNumeric('id', $query);
		}

		// Determine search
		$searchClause = $this->getSearchWhereClause($db, $this->getState('list.filter'));

		if (!empty($searchClause))
		{
			$query->where($searchClause);
		}

		// Determine ordering parameters
		$orderCol = $db->escape($this->state->get('list.ordering'));
		$orderDirn = $db->escape($this->state->get('list.direction'));

		if (!empty($orderCol) && !empty($orderDirn))
		{
			$fullordering = $orderCol . ' ' . $orderDirn;
			$query->order(trim($fullordering));
		}

		return $query;
	}

	/**
	 * Extend and amend input query with sub queries, etc.
	 *
	 * @param   object  $query   Query to be extended inserted by reference.
	 *
	 * @return  void  The extended query for chaining.
	 */
	protected function extendQuery($query)
	{
		$db	= $this->getDbo();
		$aliasNum = 0;

		// Coded fields
		foreach ($this->getCodedFields() as $fieldForms)
		{
			$alias = chr(ord('a') + ++$aliasNum);
			$columnName = $fieldForms['column'];
			$codebookField = $fieldForms['root'];
			$auxFields = explode(' ', trim($fieldForms['auxfields']));
			$select = array();

			// Compose fields for query
			foreach ($auxFields as $field)
			{
				$select[] = $db->quoteName(
					$alias . '.' . $field,
					$columnName . '_' . $field
				);
			}

			$query
				->select($select)
				->leftjoin($db->quoteName(Helper::getCodebookTable($codebookField), $alias)
					. ' ON ' . $alias . '.id = a.' . $columnName
				);
		}

		// Wrap query
		$wrapQuery = $db->getQuery(true)
			->select('*')
			->from('(' . $query . ') a');

		return $wrapQuery;
	}

	/**
	 * Create filter clause for search parameters
	 *
	 * - The searchValue may contain pattern "#<fieldLabel># <lowestValue> ~ <highestValue>".
	 * - If in-line field #<fieldLabel># is present, the class finds table field
	 * name (without leading and trailing spaces) in registered search fields
	 * in method getSearchFields() and uses it for searching instead of
	 * the default (first) registered field.
	 * - String after second "#" is considered as the searched value for inline field.
	 * - If there is no in-line field present, the entire searchValue is used for default search
	 * field.
	 * - If the in-line field is present, but is not registered, the error is raised
	 * and previous search condition is used searchValue is ignored.
	 * - If "~" is present, the searchValue is considered as a searched range
	 * from <lowestValue> to <highestValue> (without leading and trailing
	 * spaces in each of them) of values of inline or default search field.
	 * - If some of range limits absents, it is considered as corresponding infinity.
	 *
	 * @param   object  $db           Database object
	 * @param   string  $searchValue  Search value
	 *
	 * @return   string  Filter string for where clause
	 */
	private function getSearchWhereClause($db, $searchValue)
	{
		$searchParams = $this->getSearchParams($searchValue);

		// Check if the searching is needed
		if (strlen($searchParams['value']) == 0 && strlen($searchParams['limit']) == 0)
		{
			return null;
		}

		// Check if the searching is valid
		if (is_null($searchParams['name']))
		{
			JFactory::getApplication()->enqueueMessage(JText::sprintf('LIB_GBJ_ERROR_SEARCH_FIELD', $searchParams['label']), 'warning');

			return null;
		}

		$clause = null;

		// Two parameters to search
		if ($searchParams['range'])
		{
			if (!empty($searchParams['value']))
			{
				$clauseList[] = $db->quoteName($searchParams['name']) . '>='
				. $db->quote($searchParams['value']);
			}

			if (!empty($searchParams['limit']))
			{
				$clauseList[] = $db->quoteName($searchParams['name']) . '<='
				. $db->quote($searchParams['limit']);
			}

			$clause = '(' . implode(" AND ", $clauseList) . ')';
		}

		// One parameter to search
		else
		{
			switch ($searchParams['type'])
			{
				case 'string':
				case 'text':
				case 'coded':
					$wildcard = (strpos($searchValue, '%') || strpos($searchValue, '_') ? '' : '%');
					$clause = '(' . $db->quoteName($searchParams['name']) . ' LIKE '
						. $db->quote($wildcard . $searchParams['value'] . $wildcard)
						. ')';
					break;

				default:
					$clause = '(' . $db->quoteName($searchParams['name']) . '='
						. $db->quote($searchParams['value']) . ')';
					break;
			}
		}

		return $clause;
	}

	/**
	 * Gather search parameters from the search input
	 *
	 * @param   string  $searchValue  Search value put to search input
	 *
	 * @return  array  List of search parameters including search field record
	 */
	private function getSearchParams($searchValue)
	{
		$searchFields = $this->getGridFields();

		// Default search field
		$defaultFieldName = array_keys($searchFields)[0];
		$searchParams = array(
			'name'  => $defaultFieldName,
			'label' => JText::_($searchFields[$defaultFieldName]->getAttribute('label')),
			'type'  => $searchFields[$defaultFieldName]->getAttribute('type', 'string'),
			'value' => trim($searchValue),
			'limit' => null,
			'range' => false,
			);

		// Parse search parameters from search string
		$matches = array();

		// Search field defined in search value
		if (!empty($searchValue) && preg_match(
			"/^" . Helper::COMMON_SEARCH_TAG_FIELD
			. "(.+)" . Helper::COMMON_SEARCH_TAG_FIELD . "(.*)$/",
			$searchValue, $matches
		) === 1)
		{
			$searchParams['name'] = null;
			$searchParams['label'] = trim($matches[1]);
			$searchLabel = strtolower($searchParams['label']);

			foreach ($searchFields as $searchField)
			{
				$fieldLabel = JText::_($searchField->getAttribute('label'));

				if (strtolower($fieldLabel) == $searchLabel)
				{
					$searchParams['name']  = $searchField->getAttribute('name');
					$searchParams['label'] = JText::_($searchField->getAttribute('label'));
					$searchParams['type']  = $searchField->getAttribute('type', 'string');
					break;
				}
			}

			$searchValue = trim($matches[2]);
			$searchParams['value'] = $searchValue;
		}

		// Test if the search field is searchable (default) by the form including default one
		if (is_null($searchParams['name'])
			|| !filter_var($searchFields[$searchParams['name']]->getAttribute('searchable', 'true'), FILTER_VALIDATE_BOOLEAN))
		{
			$searchParams['name'] = null;
		}

		// Search range defined in search value
		if (preg_match(
			"/^(.*[^\\\\]*)" . Helper::COMMON_SEARCH_TAG_RANGE
			. "(.*)$/", $searchValue, $matches
		) === 1)
		{
			$searchParams['value'] = trim($matches[1]);
			$searchParams['limit'] = trim($matches[2]);
			$searchParams['range'] = true;
		}

		// Convert date field
		if ($searchParams['type'] == 'date')
		{
			$app = JFactory::getApplication();
			$tz = $app->getCfg('offset');
			$dateNull = $this->getDbo()->getNullDate();

			if (empty($searchParams['value']))
			{
				$searchParams['value'] = $dateNull;
			}
			else
			{
				$jdate = new JDate($searchParams['value'], $tz);
				$searchParams['value'] = $jdate->toSQL();
			}

			if (empty($searchParams['limit']))
			{
				$searchParams['limit'] = $dateNull;
			}
			else
			{
				$jdate = new JDate($searchParams['limit'], $tz);
				$searchParams['limit'] = $jdate->toSQL();
			}
		}

		return $searchParams;
	}

	/**
	 * Separate fields from form field set "gridfields" to the array.
	 *
	 * @return   array  List of field objects indexed by field name.
	 */
	public function getGridFields()
	{
		if (is_array($this->gridFields) && count($this->gridFields) > 0)
		{
			return $this->gridFields;
		}

		$this->gridFields = $this->readForm(
			Helper::getName() . '.' . $this->getName(),
			$this->getName()
		);

		return $this->gridFields;
	}

	/**
	 * Read form fields in a field set recursively in sub forms.
	 * A sub form is defined by a form field of the type "subform" with
	 * the XML file name in the field name. The path to that XML file can be
	 * defined in the field attribute "formsource", else the library is used.
	 *
	 * @param   string  $formName       The name of a form, usually its XML file.
	 * @param   string  $formSource     The path to the XML file.
	 *                                  Default is in library folder "forms".
	 * @param   string  $formFieldset   The name of a form field set with desired fields.
	 *
	 * @return  array   List of field objects indexed by field name.
	 */
	private function readForm($formName, $formSource = null, $formFieldset = 'gridfields')
	{
		$formSource = $formSource ?? Helper::getLibraryDir(true)
			. DIRECTORY_SEPARATOR
			. Helper::COMMON_FORM_BASEDIR
			. DIRECTORY_SEPARATOR
			. $formName
			. '.xml';
		$fields = array();
		$errors = $this->getErrors();
		$form = $this->loadForm($formName, $formSource);

		if ($form === false)
		{
			// Set original errors
			$this->set('_errors', $errors);
		}
		else
		{
			foreach ($form->getFieldset($formFieldset) as $fieldObject)
			{
				if ($fieldObject->getAttribute('type') == 'subform')
				{
					$fields = array_merge(
						$fields,
						$this->readForm(
							$fieldObject->getAttribute('name'),
							$fieldObject->getAttribute('formsource'),
							$formFieldset
						)
					);
				}
				else
				{
					$fields[$fieldObject->getAttribute('name')] = $fieldObject;
				}
			}
		}

		return $fields;
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

		foreach ($this->getGridFields() as $fieldName => $fieldObject)
		{
			if (substr($fieldName, 0, 3) == Helper::COMMON_FIELD_CODED_PREFIX)
			{
				$columnName = $this->getCodedColumn($fieldName);
				$rootName = $this->getCodedRoot($columnName);
				$this->codedFields[$fieldName]['column'] = $columnName;
				$this->codedFields[$fieldName]['root'] = $rootName;
				$this->codedFields[$fieldName]['auxfields'] = 'title alias '
					. $fieldObject->getAttribute('auxfields');
			}
		}

		return $this->codedFields;
	}

	/**
	 * Method for processing parent agenda relationship.
	 *
	 * @return  void
	 */
	public function processParent()
	{
		$app = JFactory::getApplication();
		$parentAgenda = $this->getName();

		// Delete other parent identity, if it is signalled by request
		$parentDel = $app->input->getWord(Helper::COMMON_URL_VAR_PARENT_DEL);

		if (!is_null($parentDel))
		{
			Helper::delParentRef($parentDel);
		}

		// If there are request parent parameters, use them
		$this->parentId = $app->input->getInt(Helper::COMMON_URL_VAR_PARENT_ID);
		$this->parentType = $app->input->getWord(Helper::COMMON_URL_VAR_PARENT_TYPE);

		if (!is_null($this->parentId) && !is_null($this->parentType))
		{
			Helper::delParentRef($parentAgenda);
		}

		// If there is former parent identity in the session, use it
		$parentRef = Helper::getParentRef($parentAgenda);

		if (is_object($parentRef))
		{
			$this->grandparent = Helper::getParentRefParentRecord($parentAgenda);
			$this->parent = $parentRef->{Helper::COMMON_PARENT_IDENTITY_RECORD};
			$this->parentType = $parentRef->{Helper::COMMON_PARENT_IDENTITY_TYPE};
			$this->parentId = $this->parent->id;
			$this->setState('filter.' . $this->parentType, $this->parentId);

			return;
		}

		// There are no request parent parameters defined
		if (is_null($this->parentId) || is_null($this->parentType))
		{
			$this->resetParent();

			return;
		}

		// Read parent record
		try
		{
			$tableName = Helper::getTable($this->parentType);
			$db = $this->getDbo();
			$query = $db->getQuery(true)
				->select('*')
				->from($db->quoteName($tableName))
				->where('id=' . $this->parentId);
			$db->setQuery($query);
			$this->parent = $db->loadObject();
		}
		catch (Exception $e)
		{
			$this->resetParent();

			return;
		}

		// Store parent identity to the session
		$parentRef = new stdClass;
		$parentRef->{Helper::COMMON_PARENT_IDENTITY_RECORD} = $this->parent;
		$parentRef->{Helper::COMMON_PARENT_IDENTITY_TYPE} = $this->parentType;
		Helper::setParentRef($parentAgenda, $parentRef);
		$this->grandparent = Helper::getParentRefParentRecord($parentAgenda);

		// Set filter for a parent record
		$this->setFilterState(
			$this->parentType,
			'uint',
			$this->parentId
		);
	}

	/**
	 * Method for reseting parent relationship.
	 *
	 * @return  void
	 */
	public function resetParent()
	{
		Helper::delParentRef($this->getName());
		$this->parentId = null;
		$this->parentType = null;
		$this->parent = null;
		$this->grandparent = null;
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
		$name = Helper::singular(empty($name) ? $this->getName() : $name);
		$prefix = empty($prefix) ? Helper::getClassTable() : $prefix;

		return JTable::getInstance($name, $prefix, $options);
	}

	/**
	 * Determine corresponding table column name for a field name.
	 *
	 * @param   string   $fieldName   Field name.
	 *
	 * @return  string|null   Column name or null.
	 */
	protected function getCodedColumn($fieldName)
	{
		$tableFields = $this->getTable()->getFields();

		foreach (array_keys($tableFields) as $columnName)
		{
			/*
			 * Coded column starts with dedicated prefix and field name
			 * starts with that column name.
			 */
			if (preg_match('/^' . Helper::COMMON_FIELD_CODED_PREFIX . '/', $columnName)
				&& preg_match('/^' . $columnName . '_?/', $fieldName))
			{
				return $columnName;
			}
		}

		return null;

	}

	/**
	 * Determine corresponding code table root name for a column name.
	 *
	 * @param   string   $columnName   Table column name of a field name.
	 *
	 * @return  string   Code table root name.
	 */
	protected function getCodedRoot($columnName)
	{
		$root = preg_replace(
			'/^' . Helper::COMMON_FIELD_CODED_PREFIX . '/',
			'',
			$columnName,
			1
		);

		return $root;
	}

	/**
	 * Set filter state to the model state.
	 *
	 * @param   string   $fieldName     Name of filtered field.
	 * @param   string   $fieldType     Data type of the filtering field.
	 * @param   mixed    $fieldDefault  Default value for the filter.
	 *
	 * @return   void
	 */
	protected function setFilterState(
		$fieldName,
		$fieldType = 'string',
		$fieldDefault = ''
	)
	{
		$this->setState('filter.' . $fieldName,
			$this->getUserStateFromRequest(
				$this->context . '.filter.' . $fieldName,
				'filter_' . $fieldName,
				$fieldDefault,
				$fieldType
			)
		);
	}

	/**
	 * Set filter for a field to the model query.
	 *
	 * @param   string   $fieldName    Name of filtered field.
	 * @param   object   $query		   Model query object for enrichment.
	 * @param   string   $columnName   Column name if different from field name.
	 *
	 * @return   object  Query object for chaining.
	 */
	protected function setFilterQueryNumeric($fieldName, $query, $columnName = null)
	{
		$db = $this->getDbo();
		$fieldValue = $this->getState('filter.' . $fieldName);
		$columnName = $columnName ?? $fieldName;

		if (is_numeric($fieldValue))
		{
			$query->where('(' . $db->quoteName($columnName)
				. ' = ' . (int) $fieldValue . ')'
			);
		}

		return $query;
	}

	/**
	 * Set filter for a field to the model query.
	 *
	 * @param   string   $fieldName    Name of filtered field.
	 * @param   object   $query		   Model query object for enrichment.
	 * @param   string   $columnName   Column name if different from field name.
	 *
	 * @return   object  Query object for chaining.
	 */
	protected function setFilterQuerySome($fieldName, $query, $columnName = null)
	{
		$db = $this->getDbo();
		$fieldValue = $this->getState('filter.' . $fieldName);
		$columnName = $columnName ?? $fieldName;

		if (is_numeric($fieldValue))
		{
			switch ($fieldValue)
			{
				// Without codes
				case '0':
					$query->where('(' . $db->quoteName($columnName) . ' = 0)');
					break;

				// With some codes
				case '1':
					$query->where('(' . $db->quoteName($columnName) . ' > 0)');
					break;
			}
		}

		return $query;
	}
}
