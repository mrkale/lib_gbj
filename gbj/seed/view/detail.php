<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2017 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

/**
 * View for handling records of an agenda, usually just first of them
 *
 * @since  3.8
 */
class GbjSeedViewDetail extends JViewLegacy
{
	/**
	 * The object with list of sorting parameters.
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * The object with model.
	 *
	 * @var  object
	 */
	public $model;

	/**
	 * The object with agenda record, usually the very first.
	 *
	 * @var  string
	 */
	public $item;

	/**
	 * The object with list of agenda record objects.
	 *
	 * @var  object
	 */
	public $items;

	/**
	 * The object with list of parameters.
	 *
	 * @var object
	 */
	public $params;

	/**
	 * @var   array  List of form fields objects for grids.
	 */
	public $gridFields;

	/**
	 * @var   array  List of additional fields for batch processing.
	 */
	public $batchFields;

	/**
	 * The field name for ordering in a grid.
	 *
	 * @var  string
	 */
	public $listOrder;

	/**
	 * The direction for ordering in a grid.
	 *
	 * @var  string
	 */
	public $listDirn;

	/**
	 *
	 * @var   array  List of statistical measures.
	 */
	public $statistics;

	/**
	 * Constructor
	 *
	 * @param   array  $config  A named configuration array for object construction.<br/>
	 *                          name: the name (optional) of the view (defaults to the view class name suffix).<br/>
	 *                          charset: the character set to use for display<br/>
	 *                          escape: the name (optional) of the function to use for escaping strings<br/>
	 *                          base_path: the parent path (optional) of the views directory (defaults to the component folder)<br/>
	 *                          template_plath: the path (optional) of the layout directory (defaults to base_path + /views/ + view name<br/>
	 *                          helper_path: the path (optional) of the helper files (defaults to base_path + /helpers/)<br/>
	 *                          layout: the layout (optional) to use to display the view<br/>
	 *
	 * @since   12.2
	 */
	public function __construct($config = array())
	{
		if (!array_key_exists('helper_path', (array) $config))
		{
			$config['helper_path'] = dirname(__DIR__, 2)
				. DIRECTORY_SEPARATOR . Helper::COMMON_TEMPLATE_BASEDIR;
		}

		parent::__construct($config);
	}

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

		// Execute model methods
		$this->items = $this->get('Items');
		$this->total = $this->get('Total');
		$this->statistics = $this->get('Statistics');
		$this->gridFields = $this->get('GridFields');

		if ($this->total)
		{
			$this->item = $this->items[0];
		}

		if (is_object($this->state))
		{
			$this->listOrder = $this->escape($this->state->get('list.ordering'));
			$this->listDirn = $this->escape($this->state->get('list.direction'));
		}

		// View's menu parameters
		$app = JFactory::getApplication();

		if ($app->isClient('site'))
		{
			$this->params = $app->getParams();
		}

		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("; ", $errors), 500);
		}

		parent::display($tpl);
	}

	/**
	 * Create HTML attribute string.
	 *
	 * @param   string  $attrName     HTML attribute name.
	 * @param   string  $attrValue    Value for the attribute.
	 * @param   string  $attrDefault  Default definition for the attribute if some of previous params is empty.
	 *
	 * @return  string  HTML attribute.
	 */
	public function htmlAttribute(
		$attrName = null,
		$attrValue = null,
		$attrDefault = null
	)
	{
		$htmlString = $attrDefault;

		if (!empty($attrName) && !empty($attrValue) && strtolower($attrValue) !== 'null')
		{
			$htmlString = ' ' . $attrName . '="' . $attrValue . '"';
		}

		return $htmlString;
	}

	/**
	 * Create HTML attributes string for '<a>' tag.
	 *
	 * @param   string  $target  HTML target string.
	 * @param   string  $width   Width of a modal or popup window in pixels.
	 * @param   string  $height  Height of a modal or popup window in pixels.
	 *
	 * @return  string  HTML href attributes string.
	 */
	public function htmlHrefAttributes(
		$target = Helper::COMMON_URL_TARGET_NEW,
		$width = 640,
		$height = 480
	)
	{
		$hrefAttribs = array();

		switch ($target)
		{
			case Helper::COMMON_URL_TARGET_NEW:	// Open in a new window
				$hrefAttribs["target"] = "_blank";
				$hrefAttribs["rel"] = "nofollow";
				break;

			case Helper::COMMON_URL_TARGET_POPUP:	// Open in a popup window
				$hrefAttribs["onclick"] = "window.open(this.href"
					. ", 'targetWindow'"
					. ", 'toolbar=no"
					. ", location=no"
					. ", status=no"
					. ", menubar=no"
					. ", scrollbars=yes"
					. ", resizable=yes"
					. ", width=" . $width
					. ", height=" . $height . "'"
					. "); return false;";
				break;

			case Helper::COMMON_URL_TARGET_MODAL:	// Open in a modal window
				JHtml::_('behavior.modal', 'a.modal-target');
				$hrefAttribs["class"] = "modal-target";
				$hrefAttribs["rel"] = "{handler: 'iframe', size: {"
					. "x:" . $width
					. ", y:" . $height
					. "}}";
				break;

			case Helper::COMMON_URL_TARGET_PARENT:	// Open in parent window
			default:
				$hrefAttribs["target"] = "_parent";
				$hrefAttribs["rel"] = "nofollow";
				break;
		}

		return $hrefAttribs;
	}

	/**
	 * Check if the parent type is defined.
	 *
	 * @param   string  $parentType  Type of a parent table.
	 *
	 * @return  boolean  Flag about parent existence.
	 */
	public function isParent($parentType = null)
	{
		if (!is_object($this->model))
		{
			$this->model = $this->getModel();
		}

		return is_object($this->model->parent) && (is_null($parentType)
			? true : Helper::getParentRefName($parentType) === $this->model->parentType);
	}

	/**
	 * Create HTML string for displaying statistics.
	 *
	 * @return  string  HTML display string.
	 */
	public function htmlStatistics()
	{
		return '';
	}
}
