<?php
/**
 * @package    Joomla.Library
 * @copyright  (c) 2020 Libor Gabaj
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      3.8
 */

// No direct access
defined('_JEXEC') or die;

/**
 * View for exporting records of an agenda in CSV format.
 *
 * @since  3.8
 */
class GbjSeedViewRaw extends JViewLegacy
{
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
	 * Method to display an agenda records.
	 *
	 * @param   string  $tpl  The name of the template file to parse.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		// Component parameters
		$cparams = JComponentHelper::getParams(Helper::getName());
		$this->flagConvert = boolval($cparams->get('export_convert'));

		// Agenda data
		$this->model = $this->getModel();
		$this->items = $this->get('Items');
		$this->gridFields = $this->get('GridFields');

		// Output file parameters
		// $namesfx = JFactory::getDate('now', $timezone)->format($format, true);
		$timezone = JFactory::getUser()->getTimezone();
		$format = 'Ymd' . Helper::COMMON_FILE_NAME_DELIMITER . 'Hi';
		$namesfx = JFactory::getDate()->setTimezone($timezone)->format($format, true);
		$basename = JText::_(strtoupper(Helper::getName($this->getName(), '_')));
		$basename .= Helper::COMMON_FILE_NAME_DELIMITER . $namesfx;
		$mimetype = Helper::COMMON_FILE_CSV_MEDIATYPE;
		$filetype = Helper::COMMON_FILE_CSV_TYPE;
		$filedate = JFactory::getDate()->toRFC822();

		// Export file
		$document = JFactory::getDocument();
		$document->setMimeEncoding($mimetype);
		$charset = $this->flagConvert ? 'iso-8859-2' : 'utf-8';
		JFactory::getApplication()
		-> setHeader('Content-Type', 'application/cvs; charset=' . $charset, true)
		-> setHeader('Content-Disposition', 'attachment; filename="'
		. $basename . '.' . $filetype, true)
		-> setHeader('Content-Transfer-Encoding', 'binary', true)
		-> setHeader('creation-date', $filedate, true)
		-> setHeader('Expires', '0', true)
		-> setHeader('Pragma', 'no-cache', true);

		parent::display($tpl);
	}
}
