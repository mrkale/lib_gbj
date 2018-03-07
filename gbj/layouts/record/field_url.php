<?php

/**
 * @package    Joomla.Library
 * @copyright  (c) 2017 Libor Gabaj. All rights reserved.
 * @license    GNU General Public License version 2 or later. See LICENSE.txt, LICENSE.php.
 * @since      3.7
 */
// No direct access
defined('_JEXEC') or die;

$layoutBasePath = Helper::getLayoutBase();
$options = $this->getOptions();
$options->set('url', true);	// Default url is the field value
?>
<?php echo JLayoutHelper::render('record.field', $displayData, $layoutBasePath, $options); ?>