<?php
/**
 * @package    Joomla.Component
 * @copyright  (c) 2017 Libor Gabaj. All rights reserved.
 * @license    GNU General Public License version 2 or later. See LICENSE.txt, LICENSE.php.
 * @since      3.7
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Definition constants and methods common and useful for every extension.
 *
 * @since  3.7
 *
 * @method      string  getClassController()       getClassController($name)     Get a full controller class name.
 * @method      string  getClassModel()			   getClassModel($name)			 Get a full model class name.
 * @method      string  getClassView()			   getClassView($name, $format)  Get a full view class name.
 * @method      string  getClassTable()			   getClassTable($name)			 Get a full table class name.
 */
class GbjHelpersCommon
{
	// URL prefix
	const COMMON_URL_PREFIX_BASE = 'index.php?option=';

	// URL variables
	const COMMON_URL_VAR_ID = 'id';
	const COMMON_URL_VAR_PARENT_ID = 'parentid';
	const COMMON_URL_VAR_PARENT_TYPE = 'parent';
	const COMMON_URL_VAR_PARENT_DEL = 'parentdel';

	/*
	 * URL targets:
	 * - JBROWSERTARGET_PARENT
	 * - JBROWSERTARGET_NEW
	 * - JBROWSERTARGET_POPUP
	 * - JBROWSERTARGET_MODAL
	 */
	const COMMON_URL_TARGET_PARENT = 0;
	const COMMON_URL_TARGET_NEW	= 1;
	const COMMON_URL_TARGET_POPUP	= 2;
	const COMMON_URL_TARGET_MODAL	= 3;

	// Table prefix
	const COMMON_TABLE_PREFIX = '#__';

	// Coded table field prefix
	const COMMON_FIELD_CODED_PREFIX = 'id_';

	// Default layouts
	const COMMON_LAYOUT_EDIT = 'edit';
	const COMMON_LAYOUT_DEFAULT = 'default';

	// Layouts relative base paths
	const COMMON_LAYOUT_BASEDIR = 'layouts';
	const COMMON_LAYOUT_SUBDIR_BATCH = 'batch';
	const COMMON_LAYOUT_SUBDIR_ADMIN = 'administrator';
	const COMMON_LAYOUT_SUBDIR_SITE  = 'site';

	// Templates relative base paths
	const COMMON_TEMPLATE_BASEDIR = 'templates';

	// Forms relative base paths
	const COMMON_FORM_BASEDIR = 'forms';

	// Record status
	const COMMON_STATE_PUBLISHED = 1;
	const COMMON_STATE_UNPUBLISHED = 0;
	const COMMON_STATE_ARCHIVED = 2;
	const COMMON_STATE_TRASHED = -2;
	const COMMON_STATE_TOTAL = -99;

	// Search field tag characters
	const COMMON_SEARCH_TAG_FIELD = '#';
	const COMMON_SEARCH_TAG_RANGE = '~';

	// Session user variable for parent identification
	const COMMON_SESSION_REFERENCE_PARENTS = 'parents';

	// Name of parent identity attribute for its current record
	const COMMON_PARENT_IDENTITY_RECORD = 'record';

	// Name of parent identity table type
	const COMMON_PARENT_IDENTITY_TYPE = 'type';

	/**
	 * Convert input string into the proper form, which means the first letter
	 * capitalized and remaining ones in lowercase form.
	 *
	 * @param   string  $text  The text string to be formatted.
	 *
	 * @return string Proper form of a text string
	 */
	public static function proper($text)
	{
		return ucfirst(strtolower((string) $text));
	}

	/**
	 * Create lowercase singular from the input plural.
	 *
	 * @param   string  $text  Plural form of a text string.
	 *
	 * @return string Singular form of a text string in lowercase
	 */
	public static function singular($text)
	{
		if (is_null($text))
		{
			return null;
		}

		$text = strtolower(rtrim($text));
		$pattern = '/ies$/';
		$replace = 'y';

		if (preg_match($pattern, $text))
		{
			$text = preg_replace($pattern, $replace, $text, 1);
		}
		else
		{
			$text = rtrim($text, 's');
		}

		return $text;
	}

	/**
	 * Create lowercase plural from the input singular.
	 *
	 * @param   string  $text  Singular form of a text string.
	 *
	 * @return string Plural form of a text string in lowercase
	 */
	public static function plural($text)
	{
		if (is_null($text))
		{
			return null;
		}

		$text = self::singular($text);
		$pattern = '/([^a|e|i|o|u])y$/';
		$replace = '\1ies';

		if (preg_match($pattern, $text))
		{
			$text = preg_replace($pattern, $replace, $text, 1);
		}
		else
		{
			$text .= 's';
		}

		return $text;
	}

	/**
	 * Return the first not null argument from the list of them at input.
	 *
	 * @return mixed Not null argument.
	 */
	public static function coalesce()
	{
		$args = func_get_args();

		foreach ($args as $arg)
		{
			if (!empty($arg))
			{
				return $arg;
			}
		}

		return null;
	}

	/**
	 * Return current extension name in lowercase.
	 *
	 * @return string  Extension name.
	 */
	public static function getExtensionName()
	{
		$extension = strtolower(JFactory::getApplication()->input->get('option'));

		return $extension;
	}

	/**
	 * Extract core name after prefix from the current extension name in lowercase.
	 *
	 * @param   string  $extension   The full name of the current extension.
	 *
	 * @return  string  Extension core name.
	 */
	public static function getExtensionCore($extension = null)
	{
		if (is_null($extension))
		{
			$extension = self::getExtensionName();
		}
		else
		{
			$extension = strtolower($extension);
		}

		$extension = JFactory::getApplication()->input->get('option');
		$tokens = explode('_', $extension);
		$core = strtolower($tokens[1]);

		return $core;
	}

	/**
	 * Extract extension class prefix from current extension name.
	 *
	 * @return  string  Extension class prefix.
	 */
	public static function getClassPrefix()
	{
		return self::proper(self::getExtensionCore());
	}

	/**
	 * Compose a MVC object's full class name.
	 *
	 * @param   string  $typeName   The type of a MVC object (controller, view, model, ...).
	 * @param   array   $paramList  Additional parameters (e.g., format at views).
	 *
	 * @return  string  Full class name for a MVC object.
	 */
	private static function getClassName($typeName, $paramList)
	{
		$typeName = self::proper($typeName);
		$className = self::getClassPrefix();
		$className .= $typeName;

		foreach ($paramList as $paramItem)
		{
			$className .= self::proper($paramItem);
		}

		return $className;
	}

	/**
	 * Magic method to get full class name.
	 *
	 * @param   string  $name       Name of the class type prefixed with 'getClass'.
	 * @param   array   $arguments  List of additional specific names of the class.
	 *
	 * @return  mixed   The filtered input value.
	 */
	public static function __callStatic($name, $arguments)
	{
		if (substr($name, 0, 8) == 'getClass')
		{
			$type = substr($name, 8);

			return self::getClassName($type, $arguments);
		}
	}

	/**
	 * Delete parent reference from session user state parameters of the agenda.
	 *
	 * @param   string  $agendaName  Name of the agenda.
	 *
	 * @return void
	 */
	public static function delParentRef($agendaName)
	{
		$agenda = self::getParentRefName($agendaName);
		$parentRefList = self::getParentRefList();

		if (array_key_exists($agenda, (array) $parentRefList))
		{
			unset($parentRefList[$agenda]);
			JFactory::getApplication()->setUserState(self::getName(self::COMMON_SESSION_REFERENCE_PARENTS), $parentRefList);
		}
	}

	/**
	 * Retrieve parent reference list from session user state parameters of the agenda.
	 *
	 * @return array  List of references or null.
	 */
	public static function getParentRefList()
	{
		return JFactory::getApplication()->getUserState(self::getName(self::COMMON_SESSION_REFERENCE_PARENTS));
	}

	/**
	 * Retrieve parent reference object from session user state parameters of the agenda.
	 *
	 * @param   string  $agendaName  Name of the agenda.
	 *
	 * @return object  Parent identity record or null.
	 */
	public static function getParentRef($agendaName)
	{
		$parentRefList = self::getParentRefList();
		$agenda = self::getParentRefName($agendaName);

		if (array_key_exists($agenda, (array) $parentRefList))
		{
			return $parentRefList[$agenda];
		}
	}

	/**
	 * Get record from stored parent reference of the agenda.
	 *
	 * @param   string  $agendaName  Name of the agenda.
	 *
	 * @return object  Parent record.
	 */
	public static function getParentRefRecord($agendaName)
	{
		$parentRef = self::getParentRef($agendaName);

		return is_object($parentRef) ? $parentRef->{self::COMMON_PARENT_IDENTITY_RECORD} : null;
	}

	/**
	 * Get parent type from stored parent reference of the agenda.
	 *
	 * @param   string  $agendaName  Name of the agenda.
	 *
	 * @return string|null  Parent type.
	 */
	public static function getParentRefType($agendaName)
	{
		$parentRef = self::getParentRef($agendaName);

		if (is_object($parentRef))
		{
			return $parentRef->{self::COMMON_PARENT_IDENTITY_TYPE};
		}
		else
		{
			return null;
		}

	}

	/**
	 * Retrieve parent reference object to an agenda parent (grandparent)
	 * from session user state parameters of the agenda.
	 *
	 * @param   string  $agendaName  Name of the agenda.
	 *
	 * @return object  Grandparent identity record or null.
	 */
	public static function getParentRefParent($agendaName)
	{
		$parentRefList = self::getParentRefList();
		$agendaName = self::getParentRefName($agendaName);
		$grandparent = null;

		if (array_key_exists($agendaName, $parentRefList))
		{
			foreach ($parentRefList as $agenda => $parentRef)
			{
				if ($agenda === $agendaName)
				{
					break;
				}

				$grandparent = $parentRef;
			}

			return $grandparent;
		}
	}

	/**
	 * Retrieve grandparent record object of an agenda
	 * from session user state parameters of the agenda.
	 *
	 * @param   string  $agendaName  Name of the agenda.
	 *
	 * @return object  Grandparent identity record or null.
	 */
	public static function getParentRefParentRecord($agendaName)
	{
		$grandparentRef = self::getParentRefParent($agendaName);

		return is_object($grandparentRef)
			? $grandparentRef->{self::COMMON_PARENT_IDENTITY_RECORD} : null;
	}

	/**
	 * Store parent reference to session user state parameters of the agenda.
	 *
	 * @param   string  $agendaName  Name of the agenda.
	 * @param   object  $parentRef   Object with parent reference.
	 *
	 * @return void
	 */
	public static function setParentRef($agendaName, $parentRef)
	{
		$app = JFactory::getApplication();
		$agenda = self::getParentRefName($agendaName);

		if (!empty($agenda) && !is_null($parentRef))
		{
			$parentList = self::getParentRefList();
			$parentList[$agenda] = $parentRef;
			$app->setUserState(self::getName(self::COMMON_SESSION_REFERENCE_PARENTS), $parentList);
		}
	}

	/**
	 * Compose fixed component name.
	 * Items are delimited from each other by dot.
	 *
	 * @param   array   $items  Suffixes to component name
	 * @param   string  $glue   Delimiter between adjacent array items
	 *
	 * @return  string  Full name of the current component with potential suffixes
	 */
	public static function getName($items = array(), $glue = '.')
	{
		$compName = JFactory::getApplication()->input->get('option');
		$items = (array) $items;
		$glue  = (string) $glue;

		foreach ($items as $item)
		{
			if (!empty($item))
			{
				$compName .= $glue . strtolower($item);
			}
		}

		return $compName;
	}

	/**
	 * Compose table name prefix.
	 *
	 * @param   boolean  $useRealPprefix  Flag about using a real table prefix
	 *
	 * @return  string  Table name prefix
	 */
	private static function getTablePrefix($useRealPprefix = false)
	{
		$prefix = strtolower(
			(boolval($useRealPprefix) ? JFactory::getApplication()->get('dbprefix') : self::COMMON_TABLE_PREFIX)
			. Helper::getClassPrefix() . '_'
		);

		return $prefix;
	}

	/**
	 * Compose full table name from the base name.
	 *
	 * @param   string   $baseName        Base name of the table
	 * @param   boolean  $useRealPprefix  Flag about using a real table prefix
	 *
	 * @return  string  Full name of a table with prefix placeholder
	 */
	public static function getTableName($baseName, $useRealPprefix = false)
	{
		$tableName = self::getTablePrefix($useRealPprefix) . $baseName;

		return $tableName;
	}

	/**
	 * Separate table base name from full table name.
	 *
	 * @param   string   $tableName       Full name of the table
	 * @param   boolean  $useRealPprefix  Flag about using a real table prefix
	 *
	 * @return  string  Base name of a table in singular and proper format
	 */
	public static function getTableType($tableName, $useRealPprefix = false)
	{
		$prefix = self::getTablePrefix($useRealPprefix);
		$baseName = str_replace($prefix, '', $tableName);

		return self::proper(self::singular($baseName));
	}

	/**
	 * Compose full table name with respect to user session parameters.
	 *
	 * @param   string   $baseName  Base name of the table
	 * @param   boolean  $toPlural  Flag about composing plural of the base name
	 *
	 * @return  string  Full name of a table with prefix placeholder
	 */
	public static function getTable($baseName = null, $toPlural = true)
	{
		if ($toPlural)
		{
			$baseName = self::plural($baseName);
		}

		$tableName = self::getTableName($baseName);

		return $tableName;
	}

	/**
	 * Compose fixed component URL.
	 *
	 * @return  string  Full name of the current component
	 */
	public static function getComponentUrl()
	{
		$compName = self::getName();
		$compUrl = self::COMMON_URL_PREFIX_BASE . $compName;

		return $compUrl;
	}

	/**
	 * Compose a full URL for a component page.
	 * Parameters with their values are delimited from each other by ampersand.
	 *
	 * @param   array  $paramPairs  Key-value pairs of parameters
	 *
	 * @return  string  A full component URL.
	 */
	public static function getUrl($paramPairs = array())
	{
		$url = self::getComponentUrl();

		foreach ($paramPairs as $key => $value)
		{
			if (!empty($key))
			{
				$url .= '&' . $key . '=' . $value;
			}
		}

		return $url;
	}

	/**
	 * Format parent type to unified and normalized form.
	 *
	 * @param   string  $parentType  Raw parent type.
	 *
	 * @return  string  Normalized parent type.
	 */
	public static function getParentRefTypeNormalized($parentType)
	{
		return self::singular(trim(strtolower($parentType)));
	}

	/**
	 * Format agenda or parent type to unified and normalized form.
	 *
	 * @param   string  $name  Raw name.
	 *
	 * @return  string  Normalized name.
	 */
	public static function getParentRefName($name)
	{
		return self::singular(trim(strtolower($name)));
	}

	/**
	 * Compose a URL for redirecting to a child view with parent identification
	 *
	 * @param   string   $viewName    Name of a child view to redirect to.
	 * @param   string   $parentType  Type of a parent table.
	 * @param   integer  $parentId    Parent record identifier.
	 * @param   integer  $id          Requested record identifier.
	 *
	 * @return  string  A full URL to a view.
	 */
	public static function getUrlViewParent($viewName, $parentType, $parentId, $id = null)
	{
		$vars = array();
		$vars['view'] = $viewName;
		$vars[self::COMMON_URL_VAR_PARENT_TYPE] = self::getParentRefName($parentType);
		$vars[self::COMMON_URL_VAR_PARENT_ID] = abs($parentId);

		if (!is_null($id))
		{
			$vars[self::COMMON_URL_VAR_ID] = abs($id);
		}

		$url = self::getUrl($vars);

		return $url;
	}

	/**
	 * Compose a URL for redirecting to a view with parent identification removing.
	 *
	 * @param   string  $viewName    Name of a child view to redirect to.
	 * @param   string  $parentType  Type of a parent that should be removed from identity.
	 *
	 * @return  string  A full URL to a view.
	 */
	public static function getUrlViewParentDel($viewName, $parentType)
	{
		$vars = array();
		$vars['view'] = $viewName;
		$vars[self::COMMON_URL_VAR_PARENT_DEL] = self::getParentRefName($parentType);
		$url = self::getUrl($vars);

		return $url;
	}

	/**
	 * Compose a URL for redirecting to a view
	 *
	 * @param   string   $viewName  Name of a view to redirect to.
	 *                              If not defined, the default one is used.
	 * @param   integer  $id        Requested record identifier.
	 *
	 * @return  string  A full URL to a view.
	 */
	public static function getUrlView($viewName = null, $id = null)
	{
		$vars = array();
		$vars['view'] = (is_null($viewName) ? self::HELPER_DEFAULT_VIEW : $viewName);

		if (!is_null($id))
		{
			$vars[self::COMMON_URL_VAR_ID] = abs($id);
		}

		$url = self::getUrl($vars);

		return $url;
	}

	/**
	 * Compose a URL for editing current record.
	 *
	 * @param   string  $viewName  Name of a view to redirect to.
	 *                             If not defined, the default one is used.
	 *
	 * @return  string  A full URL to a view.
	 */
	public static function getUrlEdit($viewName = null)
	{
		if (is_null($viewName))
		{
			$viewName = self::EDIT_VIEW;
		}

		$url = self::getUrl(array('view' => $viewName));

		return $url;
	}

	/**
	 * Read state distribution for a table.
	 *
	 * @param   string  $tableName  Table name for state distribution.
	 *
	 * @return  array  List of state=>count.
	 */
	public static function getTableStateDistrib($tableName)
	{
		$db = JFactory::getDbo();

		try
		{
			$query = $db->getQuery(true)
				->select('state, COUNT(*)')
				->from($db->quoteName($tableName))
				->group('state');
			$db->setQuery($query);
			$stateDistrib = $db->loadRowList();
		}
		catch (Exception $e)
		{
			return null;
		}

		$stateStat = array(
			self::COMMON_STATE_PUBLISHED => 0,
			self::COMMON_STATE_UNPUBLISHED => 0,
			self::COMMON_STATE_ARCHIVED => 0,
			self::COMMON_STATE_TRASHED => 0,
			self::COMMON_STATE_TOTAL => 0,
		);

		foreach ((array) $stateDistrib as $state)
		{
			$stateStat[self::COMMON_STATE_TOTAL] += (int) $state[1];
			$stateStat[(int) $state[0]] = (int) $state[1];
		}

		return $stateStat;
	}

	/**
	 * Configure the side bar.
	 *
	 * @param   string  $vName  The name of the active view
	 *
	 * @return  void
	 */
	public static function addSubmenu($vName)
	{
		foreach (Helper::$helperViewsInSubmenu as $viewName)
		{
			$langConst = strtoupper(self::getName(array('SUBMENU', $viewName), '_'));
			JHtmlSidebar::addEntry(
				JText::_($langConst),
				self::getUrlViewParentDel($viewName, $viewName),
				$vName == $viewName
			);
		}
	}

	/**
	 * Compose user session parameter name for edited data
	 *
	 * @param   string  $agendaName  Name of and agenda (usually view)
	 *
	 * @return  string  Parameter name.
	 */
	public static function getAgendaPrmEditData($agendaName)
	{
		$paramName = strtolower(self::getName(array('edit', $agendaName, 'data')));

		return $paramName;
	}

	/**
	 * Compose full table name with code list from the base name.
	 * If a codebook table does not exits, the procedure uses an agenda table.
	 *
	 * @param   string   $baseName        Base name of the table (table type).
	 * @param   boolean  $useRealPprefix  Flag about using a real table prefix.
	 *
	 * @return  string|null  Full name of a codebook table with prefix placeholder.
	 */
	public static function getCodebookTable($baseName, $useRealPprefix = false)
	{
		$realPrefix = JFactory::getApplication()->get('dbprefix');
		$tablePrefix = boolval($useRealPprefix) ?
			$realPrefix :
			self::COMMON_TABLE_PREFIX;
		$tableSuffix = '_' . self::plural($baseName);
		$prefixes = array(
			Helper::HELPER_CODEBOOK_TABLE_PREFIX,
			Helper::getClassPrefix(),
		);

		foreach ($prefixes as $prefix)
		{
			$tableName = $realPrefix . $prefix . $tableSuffix;

			if (self::isTable($tableName))
			{
				$tableName = strtolower($tablePrefix . $prefix . $tableSuffix);

				return $tableName;
			}
		}

		return null;
	}

	/**
	 * Separate the name of the super ordered directory alone.
	 *
	 * @param   boolean  $full Flag about returning the absolute path.
	 *
	 * @return   string
	 */
	public static function getLibraryDir($full = false)
	{
		$path = dirname(__DIR__);

		if ($full)
		{
			$dir = $path;
		}
		else
		{
			$dirs = explode(DIRECTORY_SEPARATOR, $path);
			$dir = end($dirs);
		}

		return $dir;
	}

	/**
	 * Construct a base path to layouts for input or running application.
	 *
	 * @param   string  $client  The classification name of the running application.
	 * @param   string  $folder  The parent folder of the layouts.
	 *
	 * @return  string  Absolute base path to the layouts.
	 */
	public static function getLayoutBase($client = '', $folder = '')
	{
		$layoutPath = DIRECTORY_SEPARATOR . self::COMMON_LAYOUT_BASEDIR;

		// Determine default parent folder
		if (empty($folder))
		{
			$folder = dirname(__DIR__);
		}

		// Set base path for particular running application
		switch ($client)
		{
			case 'site':
				$layoutPath .= DIRECTORY_SEPARATOR . self::COMMON_LAYOUT_SUBDIR_SITE;
				break;

			case 'administrator':
				$layoutPath .= DIRECTORY_SEPARATOR . self::COMMON_LAYOUT_SUBDIR_ADMIN;
				break;

			case 'batch':
				$layoutPath .= DIRECTORY_SEPARATOR . self::COMMON_LAYOUT_SUBDIR_BATCH;
				break;

			default:
				break;
		}

		return $folder . $layoutPath;
	}

	/**
	 * Create and launch main controller.
	 *
	 * @return void
	 */
	public static function launchMainController()
	{
		$app = JFactory::getApplication();

		if ($app->isClient('administrator'))
		{
			if (!JFactory::getUser()->authorise('core.manage', self::getName()))
			{
				$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');

				return;
			}
		}

		if ($app->isClient('site'))
		{
			JFormHelper::addFieldPath(JPATH_COMPONENT_ADMINISTRATOR
				. DIRECTORY_SEPARATOR
				. 'models'
				. DIRECTORY_SEPARATOR
				. 'fields'
			);
			JFormHelper::addFormPath(JPATH_COMPONENT
				. DIRECTORY_SEPARATOR
				. 'models'
				. DIRECTORY_SEPARATOR
				. 'forms'
			);

			// Load backend as well as frontend language constants for current language
			JFactory::getLanguage()->load(Helper::getName(), JPATH_COMPONENT_ADMINISTRATOR);
			JFactory::getLanguage()->load(Helper::getName(), JPATH_COMPONENT_SITE, null, true);

			$document = JFactory::getDocument();
			$cssFile = '.'
				. DIRECTORY_SEPARATOR
				. 'media'
				. DIRECTORY_SEPARATOR
				. Helper::getName(array('css', 'site.css'), DIRECTORY_SEPARATOR);
			$document->addStyleSheet($cssFile);
		}

		$controller	= JControllerLegacy::getInstance(self::getClassPrefix());
		$controller->execute($app->input->get('task'));
		$controller->redirect();
	}

	/**
	 * Check if a database table exists in the database.
	 *
	 * @param   string  $tableName  Table name with real table prefix.
	 *
	 * @return  boolean  Flag about table existence.
	 */
	public static function isTable($tableName)
	{
		$db = JFactory::getDbo();
		$query = "SHOW TABLES LIKE '" . $db->escape($tableName) . "'";
		$db->setQuery($query);

		return !is_null($db->loadResult());
	}
}
