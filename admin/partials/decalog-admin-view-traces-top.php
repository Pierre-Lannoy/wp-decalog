<?php
/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

?>

<div class="alignleft actions bulkactions">
    <label for="llogger_id" class="screen-reader-text"><?php esc_html_e('Choose traces log to display', 'decalog');?></label>
    <select name="logger_id" id="logger_id">
		<?php foreach ($list->get_loggers() as $l) { ?>
            <option <?php echo ($list->get_current_Log_id() === $l['id'] ? 'selected="selected"' : ''); ?> value="<?php echo $l['id']; ?>"><?php echo $l['name']; ?> (<?php ($l['running']?esc_html_e('running', 'decalog'):esc_html_e('paused', 'decalog')); ?>)</option>
		<?php } ?>
    </select>
    <input type="submit" class="button action" value="<?php esc_html_e('Apply', 'decalog');?>"  />
</div>
