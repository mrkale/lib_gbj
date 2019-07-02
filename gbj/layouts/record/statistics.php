<?php
/**
 * @package     Joomla.Library
 * @subpackage  Layout
 * @copyright  (c) 2018-2019 Libor Gabaj
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @since       3.8
 */

// No direct access
defined('_JEXEC') or die;

// Options
$options = $this->getOptions();
$statistics = $options->get('statistics');
?>
<?php foreach ($statistics as $name => $value): ?>
<dt><?php echo $name; ?></dt>
<dd><?php echo $value ?? 0; ?></dd>
<?php endforeach;