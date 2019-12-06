<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017-2019 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * General model methods for the list of records in an agenda.
 *
 * @since  3.8
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
	 * The filter form object with field attributes
	 *
	 * @var object
	 */
	protected $filterForm;

	const FILTER_FIELDS_GROUP = 'filter';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  Associative array of configuration settings.
	 */
	public function __construct($config = array())
	{
		$this->filterBlacklist[] = 'description';
		$this->filterBlacklist[] = 'sequence';

		if (!array_key_exists('filter_fields', (array) $config))
		{
			$config['filter_fields'] = array();
		}

		// Read filter form if exists
		$errors = $this->getErrors();
		$this->filterForm = $this->getFilterForm(null, false);

		// Set exact filter fields from filter form
		if (is_object($this->filterForm))
		{
			$filterFields = $this->filterForm->getGroup(self::FILTER_FIELDS_GROUP);

			foreach ($filterFields as $fieldName => $filterField)
			{
				$fieldName = 'filter_' . $filterField->getAttribute('name');

				if ($filterField->getAttribute('type') == 'subform')
				{
					$subformSource = $filterField->getAttribute('formsource')
						?? Helper::getLibraryDir(true)
						. DIRECTORY_SEPARATOR
						. Helper::COMMON_FORM_BASEDIR
						. DIRECTORY_SEPARATOR
						. $fieldName
						. '.xml';
					$form = \JForm::getInstance($fieldName, $subformSource);
					$subformFilterFields = $form->getGroup(self::FILTER_FIELDS_GROUP);
					unset($filterFields[$fieldName]);
					$filterFields = array_merge($subformFilterFields, $filterFields);
				}
			}

			foreach ($filterFields as $filterField)
			{
				$fieldName = $filterField->getAttribute('name');

				if (array_search($fieldName, $config['filter_fields']) === false)
				{
					$config['filter_fields'][] = $fieldName;
				}
			}
		}
		else
		{
			// Set original errors
			$this->set('_errors', $errors);
		}

		// Set grid fields not in filter form as filter fields for sorting
		foreach (array_keys($this->getGridFields()) as $gridField)
		{
			if (!in_array($gridField, $this->filterBlacklist)
				&& array_search($gridField, $config['filter_fields']) === false)
			{
				$config['filter_fields'][] = $gridField;
			}
		}

		// Add state filter field at frontend, if not already involved
		$gridField = 'state';

		if (JFactory::getApplication()->isClient('site')
			&& array_search($gridField, $config['filter_fields']) === false)
		{
			$config['filter_fields'][] = $gridField;
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
		$this->id = $app->input->getInt(Helper::COMMON_URL_VAR_ID);

		foreach ($this->filter_fields as $filterField)
		{
			switch ($filterField)
			{
				case 'search':
					$this->setState('list.filter',
						$this->getUserStateFromRequest(
							$this->context . '.filter.search',
							'filter_search', '', 'string'
						)
					);
					break;

				case 'state':
					$this->setFilterState($filterField);
					break;

				case 'id':
					$this->setFilterState($filterField, 'uint', $this->id);
					break;

				// Process fields by an attribute
				default:
					if (is_object($this->filterForm))
					{
						$dataType = $this->filterForm->getFieldAttribute(
							$filterField, 'datatype', 'uint', 'filter'
						);
					}
					else
					{
						$gridField = $this->gridFields[$filterField];
						$dataType = $gridField->getAttribute('datatype', 'uint');
					}

					$this->setFilterState($filterField, $dataType);
					break;
			}
		}

		$this->processParent();
		$this->setFilterParent();

		// Application params
		if ($app->isClient('site'))
		{
			$params = $app->getParams(Helper::getName());
			$this->setState('request.params', $params);
		}

		// Default sorting parameters taken from filter form
		if ((is_null($ordering) || is_null($direction))
			&& is_object($this->filterForm)
		)
		{
			$fullordering = $this->filterForm->getFieldAttribute(
				'fullordering',
				'default',
				null,
				'list'
			);
			list($ordering, $direction) = explode(' ', $fullordering ?? 'title ASC');
		}

		parent::populateState($ordering, $direction);
	}

	/**
	 * Retrieve list of records from database.
	 *
	 * @return  object  The query.
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
		$query = $this->processQueryFilter($query);

		// Determine ordering parameters
		$orderCol = $db->escape($this->state->get('list.ordering'));
		$orderDirn = $db->escape($this->state->get('list.direction'));

		if (!empty($orderCol) && !empty($orderDirn))
		{
			if (array_key_exists($orderCol, $this->gridFields))
			{
				$gridField = $this->gridFields[$orderCol];
				$orderCol = $gridField->getAttribute('datafield', $orderCol);
			}

			$fullordering = $orderCol . ' ' . $orderDirn;
			$query->order(trim($fullordering));
		}

		return $query;
	}

	/**
	 * Extend and amend input query with sub queries, etc.
	 *
	 * @param   object  $query   Query to be extended.
	 *
	 * @return  void  The extended query for chaining.
	 */
	protected function extendQuery($query)
	{
		$db	= $this->getDbo();
		$aliasNum = 0;

		// Coded fields
		foreach ($this->getCodedFields() as $columnName => $fieldForms)
		{
			$codebookFieldType = $fieldForms['root'];
			$table = Helper::getCodebookTable($codebookFieldType);

			if (!is_null($table))
			{
				$alias = chr(ord('a') + ++$aliasNum);
				$select = array();

				// Compose fields for query
				if (array_key_exists('auxfields', $fieldForms))
				{
					foreach ($fieldForms['auxfields'] as $fieldName)
					{
						$codebookFieldName = preg_replace('/^' . $columnName . '_/',
							'',	$fieldName,	1
						);
						$select[] = $db->quoteName($alias . '.' . $codebookFieldName, $fieldName);
					}
				}

				// Join codebook table
				$query
					->select($select)
					->leftjoin($db->quoteName($table, $alias)
						. ' ON ' . $alias . '.id = a.' . $columnName
					);
			}
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

		$dateNull = $this->getDbo()->getNullDate();

		if ($searchParams['value'] == $dateNull && $searchParams['limit'] == $dateNull)
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
			if (Helper::isCodedField($fieldName))
			{
				$this->codedFields[$fieldName]['root'] = Helper::getCodedRoot($fieldName);
				$auxFieldsAttribs = array('datafield', 'tooltip');

				foreach ($auxFieldsAttribs as $fieldAttr)
				{
					$auxField = $fieldObject->getAttribute($fieldAttr);

					if (isset($auxField))
					{
						$this->codedFields[$fieldName]['auxfields'][] = $auxField;
					}
				}
			}
		}

		return $this->codedFields;
	}

	/**
	 * Method for processing parent agenda relationship.
	 *
	 * @return  void
	 */
	protected function processParent()
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
	}

	/**
	 * Method for reseting parent relationship.
	 *
	 * @return  void
	 */
	protected function resetParent()
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
		$fieldValue = $this->getUserStateFromRequest(
			$this->context . '.filter.' . $fieldName,
			'filter_' . $fieldName,
			$fieldDefault,
			$fieldType
		);

		// Do not save state for multiple filtered field without parent
		if (!is_object($this->parent)
			&& is_object($this->filterForm)
			&& strtoupper($this->filterForm->getFieldAttribute($fieldName, 'multiple', 'FALSE', 'filter')) === 'TRUE'
		)
		{
			return;
		}

		$this->setState('filter.' . $fieldName, $fieldValue);
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
			$query->where('(' . $db->quoteName($columnName) . '=' . (int) $fieldValue . ')'
			);
		}
		elseif (is_array($fieldValue))
		{
			$fieldValue = ArrayHelper::toInteger($fieldValue);
			$fieldValue = implode(',', $fieldValue);
			$query->where('(' . $db->quoteName($columnName)	. ' IN (' . $fieldValue . '))');
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
				// Without items
				case '0':
					$query->where('(COALESCE(' . $db->quoteName($columnName) . ',0)=0)');
					break;

				// With some items
				case '1':
					$query->where('(' . $db->quoteName($columnName) . '>0)');
					break;
			}
		}

		return $query;
	}

	/**
	 * Set filter for a year field to the model query.
	 *
	 * @param   string   $fieldName    Name of filtered field.
	 * @param   object   $query		   Model query object for enrichment.
	 * @param   string   $columnName   Column name if different from field name.
	 *
	 * @return   object  Query object for chaining.
	 */
	protected function setFilterQueryYear($fieldName, $query, $columnName = null)
	{
		$db = $this->getDbo();
		$fieldValue = $this->getState('filter.' . $fieldName);
		$columnName = $columnName ?? $fieldName;

		if (is_numeric($fieldValue))
		{
			$query->where('(YEAR(' . $db->quoteName($columnName) . ')=' . (int) $fieldValue . ')'
			);
		}
		elseif (is_array($fieldValue))
		{
			$fieldValue = ArrayHelper::toInteger($fieldValue);
			$fieldValue = implode(',', $fieldValue);
			$query->where('(YEAR(' . $db->quoteName($columnName) . ') IN (' . $fieldValue . '))');
		}

		return $query;
	}

	/**
	 * Set filter for a month field to the model query.
	 *
	 * @param   string   $fieldName    Name of filtered field.
	 * @param   object   $query		   Model query object for enrichment.
	 * @param   string   $columnName   Column name if different from field name.
	 *
	 * @return   object  Query object for chaining.
	 */
	protected function setFilterQueryMonth($fieldName, $query, $columnName = null)
	{
		$db = $this->getDbo();
		$fieldValue = $this->getState('filter.' . $fieldName);
		$columnName = $columnName ?? $fieldName;

		if (is_numeric($fieldValue))
		{
			$query->where('(MONTH(' . $db->quoteName($columnName) . ')=' . (int) $fieldValue . ')'
			);
		}
		elseif (is_array($fieldValue))
		{
			$fieldValue = ArrayHelper::toInteger($fieldValue);
			$fieldValue = implode(',', $fieldValue);
			$query->where('(MONTH(' . $db->quoteName($columnName) . ') IN (' . $fieldValue . '))');
		}

		return $query;
	}

	/**
	 * Set filter for a parent field
	 *
	 * @return   void
	 */
	protected function setFilterParent()
	{
		if (is_object($this->parent))
		{
			$parentFilterField = Helper::getCodedField($this->parentType);
			$this->setFilterState($parentFilterField, 'uint', $this->parentId);
			$this->filterBlacklist[] = $parentFilterField;
		}
	}

	/**
	 * Format numeric statistics value and add it to the provided array
	 * for web site one record statistics.
	 *
	 * @param   array   $statArray    List of statistics key-value pairs.
	 * @param   number  $statValue    Statistics numeric value.
	 * @param   number  $statLabel    Name of a statistic value.
	 * @param   number  $statMeasure  Name of a record field.
	 * @param   number  $statUnit     Optional measurement unit of a statistics.
	 * @param   string  $statFormat   List of formatting parameters separated
	 *                                by semicolon in default:
	 *                                number of decimals, decimal separator,
	 *                                thousands separator
	 *
	 * @return  void
	 */
	public function addStatisticsNumber(& $statArray, $statValue,
		$statLabel, $statMeasure, $statUnit = null, $statFormat=null
	)
	{
		$label = JText::sprintf('LIB_GBJ_STAT_VALUE_TITLE', $statMeasure, $statLabel);
		$statArray[$label] = JText::sprintf('LIB_GBJ_STAT_VALUE_UNIT',
			Helper::formatNumber($statValue, $statFormat),
			$statUnit
		);
	}

	/**
	 * Format date statistics value and add it to the provided array
	 * for web site one record statistics.
	 *
	 * @param   array   $statArray    List of statistics key-value pairs.
	 * @param   number  $statValue    Statistics numeric value.
	 * @param   number  $statMeasure  Name of a record field.
	 *
	 * @return  void
	 */
	public function addStatisticsDate(& $statArray, $statValue, $statMeasure)
	{
		$value = JHtml::_('date', $statValue, JText::_('LIB_GBJ_FORMAT_DATE_LONG'));
		$statArray[$statMeasure] = $value;
	}

	/**
	 * Get the filter form
	 *
	 * @param   array    $data      data
	 * @param   boolean  $loadData  load current data
	 *
	 * @return  \JForm|boolean  The \JForm object or false on error
	 *
	 * @since   3.2
	 */
	public function getFilterForm($data = array(), $loadData = true)
	{
		$form = parent::getFilterForm($data, $loadData);

		if (!is_object($form))
		{
			return $form;
		}

		$filterFields = $this->getFilterFormFields($form, $loadData);

		// Rewrite fields
		if (array_key_exists(Helper::COMMON_TABLE_PREFIX, $filterFields)
			&& $filterFields[Helper::COMMON_TABLE_PREFIX] === true)
		{
			foreach ($form->getGroup(self::FILTER_FIELDS_GROUP) as $fieldObject)
			{
				$form->removeField($fieldObject->getAttribute('name'), self::FILTER_FIELDS_GROUP);
			}

			foreach ($filterFields as $name => $filterFieldArr)
			{
				if ($name == Helper::COMMON_TABLE_PREFIX)
				{
					continue;
				}

				$form->setField($filterFieldArr['xml'], self::FILTER_FIELDS_GROUP);
				$form->setValue($name, self::FILTER_FIELDS_GROUP, $filterFieldArr['val']);
			}
		}

		return $form;
	}

	/**
	 * Get the filter form fields recursively
	 *
	 * @param   object   $form      Filter form
	 * @param   boolean  $loadData  Load current data
	 *
	 * @return  Array with filter form fields with resolved subforms
	 *
	 * @since   3.2
	 */
	private function getFilterFormFields($form, $loadData = true)
	{
		$filterFields = array();

		foreach ($form->getGroup(self::FILTER_FIELDS_GROUP) as $fieldName => $fieldObject)
		{
			$name = $fieldObject->getAttribute('name');

			if ($fieldObject->getAttribute('type') == 'subform')
			{
				$source = $fieldObject->getAttribute('formsource')
					?? Helper::getLibraryDir(true)
					. DIRECTORY_SEPARATOR
					. Helper::COMMON_FORM_BASEDIR
					. DIRECTORY_SEPARATOR
					. $fieldName
					. '.xml';
				$formOptions['load_data'] = $loadData;
				$subform = $this->loadForm($fieldName, $source, $formOptions);
				$filterFields = array_merge(
					$filterFields,
					$this->getFilterFormFields($subform, $loadData)
				);
				$filterFields[Helper::COMMON_TABLE_PREFIX] = true;
			}
			else
			{
				$filterFields[$name]['xml'] = $form->getFieldXml($name, self::FILTER_FIELDS_GROUP);
				$filterFields[$name]['val'] = $form->getValue($name, self::FILTER_FIELDS_GROUP);
			}
		}

		return $filterFields;
	}

	/**
	 * Process filtering in behalf of input query.
	 *
	 * @param   object  $query   Query to be processed.
	 *
	 * @return  object  The query for domains.
	 */
	protected function processQueryFilter($query)
	{
		$app = JFactory::getApplication();
		$db	= $this->getDbo();

		foreach ($this->filter_fields as $filterField)
		{
			switch ($filterField)
			{
				case 'search':
					$searchClause = $this->getSearchWhereClause($db, $this->getState('list.filter'));

					if (!empty($searchClause))
					{
						$query->where($searchClause);
					}

					break;

				case 'state':
					// Ignore state at individual record mode
					if (is_int($this->getState('filter.id')))
					{
						break;
					}

					$state = $this->getState('filter.' . $filterField);

					if ($state === '')
					{
						// Default filtering
						if ($app->isClient('site'))
						{
							$query->where($db->quoteName($filterField) . '=' . Helper::COMMON_STATE_PUBLISHED);
						}
						else
						{
							$query->where(
								'(' . $db->quoteName($filterField) . ' IN ('
								. (int) Helper::COMMON_STATE_UNPUBLISHED . ', '
								. (int) Helper::COMMON_STATE_PUBLISHED
								. '))'
							);
						}
					}
					elseif ($state === Helper::COMMON_STATE_ALL && $app->isClient('site'))
					{
						$query->where(
							'(' . $db->quoteName($filterField) . ' IN ('
							. (int) Helper::COMMON_STATE_ARCHIVED . ', '
							. (int) Helper::COMMON_STATE_PUBLISHED
							. '))'
						);
					}
					else
					{
						$this->setFilterQueryNumeric($filterField, $query);
					}

					break;

				case 'year':
					if (is_object($this->filterForm))
					{
						$dataField = $this->filterForm->getFieldAttribute(
							$filterField, 'datafield', 'date_on', 'filter'
						);
					}
					else
					{
						$gridField = $this->gridFields[$filterField];
						$dataField = $gridField->getAttribute('datafield', 'date_on');
					}

					$this->setFilterQueryYear($filterField, $query, $dataField);
					break;

				case 'month':
					if (is_object($this->filterForm))
					{
						$dataField = $this->filterForm->getFieldAttribute(
							$filterField, 'datafield', 'date_on', 'filter'
						);
					}
					else
					{
						$gridField = $this->gridFields[$filterField];
						$dataField = $gridField->getAttribute('datafield', 'date_on');
					}

					$this->setFilterQueryMonth($filterField, $query, $dataField);
					break;

				case 'yearoff':
					if (is_object($this->filterForm))
					{
						$dataField = $this->filterForm->getFieldAttribute(
							$filterField, 'datafield', 'date_off', 'filter'
						);
					}
					else
					{
						$gridField = $this->gridFields[$filterField];
						$dataField = $gridField->getAttribute('datafield', 'date_off');
					}

					$this->setFilterQueryYear($filterField, $query, $dataField);
					break;

				case 'monthoff':
					if (is_object($this->filterForm))
					{
						$dataField = $this->filterForm->getFieldAttribute(
							$filterField, 'datafield', 'date_off', 'filter'
						);
					}
					else
					{
						$gridField = $this->gridFields[$filterField];
						$dataField = $gridField->getAttribute('datafield', 'date_off');
					}

					$this->setFilterQueryMonth($filterField, $query, $dataField);
					break;

				// Process fields by an attribute
				default:
					if (is_object($this->filterForm))
					{
						$dataMode = $this->filterForm->getFieldAttribute(
							$filterField, 'datamode', 'value', 'filter'
						);
					}
					else
					{
						$gridField = $this->gridFields[$filterField];
						$dataMode = $gridField->getAttribute('datamode', 'value');
					}

					switch ($dataMode)
					{
						case 'binary':
							$this->setFilterQuerySome($filterField, $query);
							break;

						default:
							$this->setFilterQueryNumeric($filterField, $query);
							break;
					}

					break;
			}
		}

		return $query;
	}

	/**
	 * Calculates statistics from filtered records.
	 *
	 * @param   array   $fieldList   List of individual fields or pairs of them
	 *                               separated by comma for statistical evaluation.
	 *
	 * @return  array  The list of statistics variables and values.
	 */
	public function getFilterStatistics($fieldList = array())
	{
		$statistics = array();
		$statList = array('cnt', 'sum', 'avg', 'min', 'max');

		$db	= $this->getDbo();
		$table = $this->getTable();
		$tableName = $table->getTableName();
		$query = $db->getQuery(true)
			->from($db->quoteName($tableName, 'a'));
		$query = $this->processQueryFilter($query);
		$emptyDateTime = $db->getNullDate();
		$emptyDates = explode(' ', $emptyDateTime);
		$emptyDate = $emptyDates[0];
		$emptyTime = $emptyDates[1];

		foreach ($fieldList as $field)
		{
			$fields = explode(',', $field);
			$fieldName = trim($fields[0]);
			$fieldExpr = $fieldName;

			// Detect date/time field
			if (array_key_exists($fieldName, $this->gridFields))
			{
				$gridField = $this->gridFields[$fieldName];
				$type = $gridField->getAttribute('type', $fieldName);

				switch ($type)
				{
					case 'date':
						$fieldExpr = 'IF(' . $fieldExpr . '="' . $emptyDate
							. '",NULL,' . $fieldExpr . ')';
						break;
					case 'datetime':
						$fieldExpr = 'IF(' . $fieldExpr . '="' . $emptyDateTime
							. '",NULL,' . $fieldExpr . ')';
						break;
				}
			}

			if (isset($fields[1]))
			{
				$fieldAlt = trim($fields[1]);
				$fieldExpr = 'IFNULL(' . $fieldExpr . ',' . $fieldAlt . ')';
			}

			$query
				->select('COUNT(' . $fieldExpr . ') AS cnt_' . $fieldName)
				->select('SUM(' . $fieldExpr . ') AS sum_' . $fieldName)
				->select('AVG(' . $fieldExpr . ') AS avg_' . $fieldName)
				->select('MIN(' . $fieldExpr . ') AS min_' . $fieldName)
				->select('MAX(' . $fieldExpr . ') AS max_' . $fieldName);
		}

		try
		{
			$db->setQuery($query);
			$statRecord = $db->loadAssoc();
		}
		catch (\RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return $statistics;
		}

		foreach ($fieldList as $field)
		{
			$fields = explode(',', $field);
			$fieldName = trim($fields[0]);

			foreach ($statList as $statVar)
			{
				$statistics[$fieldName][$statVar] = $statRecord[$statVar . '_' . $fieldName] ?? 0;
			}
		}

		return $statistics;
	}

	/**
	 * Calculates statistics from child agenda of filtered records.
	 *
	 * @param   string   $agenda        Child agenda table base name
	 * @param   string   $fieldParent   Parent discriminator field name
	 * @param   array    $fieldList     List of individual fields or pairs of them
	 *                                  separated by comma for statistical evaluation
	 *
	 * @return  array  The list of agenda statistics variables and values.
	 */
	public function getFilterStatisticsChild($agenda, $fieldParent, $fieldList = array())
	{
		$statistics = array();
		$statList = array('cnt', 'sum', 'avg', 'min', 'max');

		$db	= $this->getDbo();
		$tableName = Helper::getTableName($agenda);
		$query = $db->getQuery(true)
			->from($db->quoteName($tableName, 'a'));
		$query = $this->processQueryFilter($query);
		$query
			->where('(' . $db->quoteName($fieldParent) . '>0)')
			->select('COUNT(*) AS total');
		$emptyDateTime = $db->getNullDate();
		$emptyDates = explode(' ', $emptyDateTime);
		$emptyDate = $emptyDates[0];
		$emptyTime = $emptyDates[1];

		foreach ($fieldList as $field)
		{
			$fields = explode(',', $field);
			$fieldName = trim($fields[0]);
			$fieldExpr = $fieldName;

			// Detect date/time field
			if (array_key_exists($fieldName, $this->gridFields))
			{
				$gridField = $this->gridFields[$fieldName];
				$type = $gridField->getAttribute('type', $fieldName);

				switch ($type)
				{
					case 'date':
						$fieldExpr = 'IF(' . $fieldExpr . '="' . $emptyDate
							. '",NULL,' . $fieldExpr . ')';
						break;
					case 'datetime':
						$fieldExpr = 'IF(' . $fieldExpr . '="' . $emptyDateTime
							. '",NULL,' . $fieldExpr . ')';
						break;
				}
			}

			if (isset($fields[1]))
			{
				$fieldAlt = trim($fields[1]);
				$fieldExpr = 'IFNULL(' . $fieldExpr . ',' . $fieldAlt . ')';
			}

			$query
				->select('COUNT(' . $fieldExpr . ') AS cnt_' . $fieldName)
				->select('SUM(' . $fieldExpr . ') AS sum_' . $fieldName)
				->select('AVG(' . $fieldExpr . ') AS avg_' . $fieldName)
				->select('MIN(' . $fieldExpr . ') AS min_' . $fieldName)
				->select('MAX(' . $fieldExpr . ') AS max_' . $fieldName);
		}

		try
		{
			$db->setQuery($query);
			$statRecord = $db->loadAssoc();
		}
		catch (\RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return $statistics;
		}

		foreach ($fieldList as $field)
		{
			$fields = explode(',', $field);
			$fieldName = trim($fields[0]);

			foreach ($statList as $statVar)
			{
				$statistics[$agenda][$fieldName][$statVar] = $statRecord[$statVar . '_' . $fieldName] ?? 0;
			}
		}

		$statistics[$agenda]['#'] = $statRecord['total'] ?? 0;

		return $statistics;
	}
}
