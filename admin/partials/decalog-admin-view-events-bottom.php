<?php
/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

?>

<div class="alignleft actions bulkactions">
    <label for="limit-selector-bottom" class="screen-reader-text"><?php esc_html_e('Number of lines to display', 'decalog');?></label>
    <select name="limit" id="limit-selector-bottom">
		<?php foreach ($list->get_line_number_select() as $line) { ?>
            <option <?php echo $line['selected']; ?>value="<?php echo $line['value']; ?>"><?php echo $line['text']; ?></option>
		<?php } ?>
    </select>
    <input type="submit" class="button action" value="<?php esc_html_e('Apply', 'decalog');?>"  />
</div>
