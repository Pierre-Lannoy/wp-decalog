<?php
/**
 * BuddyPress listener for DecaLog.
 *
 * Defines class for BuddyPress listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */

namespace Decalog\Listener;

use Decalog\System\Environment;
use Decalog\System\Option;

/**
 * BuddyPress listener for DecaLog.
 *
 * Defines methods and properties for BuddyPress listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class BuddyPressListener extends AbstractListener {

	/**
	 * Activity to be deleted.
	 *
	 * @since   2.4.0
	 * @var bool
	 */
	protected $deleted_activity = false;

	/**
	 * Activity args to be deleted.
	 *
	 * @since   2.4.0
	 * @var array
	 */
	protected $delete_activity_args = [];

	/**
	 * Ignore activity deletions.
	 *
	 * @since   2.4.0
	 * @var bool
	 */
	protected $ignore_activity_bulk_deletion = false;


	/**
	 * Sets the listener properties.
	 *
	 * @since    2.4.0
	 */
	protected function init() {
		$this->id      = 'buddypress';
		$this->class   = 'plugin';
		$this->product = 'BuddyPress';
		$this->name    = 'BuddyPress';
		if ( class_exists( 'BuddyPress' ) ) {
			$this->version = \BuddyPress::instance()->version;
		} else {
			$this->version = 'x';
		}
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    2.4.0
	 */
	protected function is_available() {
		return class_exists( 'BuddyPress' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    2.4.0
	 */
	protected function launch() {
		add_action( 'bp_before_activity_delete', [ $this, 'bp_before_activity_delete' ], 10, 1 );
		add_action( 'bp_activity_deleted_activities', [ $this, 'bp_activity_deleted_activities' ], 10, 1 );
		add_action( 'bp_activity_mark_as_spam', [ $this, 'bp_activity_mark_as_spam' ], 10, 2 );
		add_action( 'bp_activity_mark_as_ham', [ $this, 'bp_activity_mark_as_ham' ], 10, 2 );
		add_action( 'bp_activity_admin_edit_after', [ $this, 'bp_activity_admin_edit_after' ], 10, 2 );
		add_action( 'groups_create_group', [ $this, 'groups_create_group' ], 10, 3 );
		add_action( 'groups_update_group', [ $this, 'groups_update_group' ], 10, 2 );
		add_action( 'groups_details_updated', [ $this, 'groups_update_group' ], 10, 1 );
		add_action( 'groups_settings_updated', [ $this, 'groups_update_group' ], 10, 1 );
		add_action( 'groups_leave_group', [ $this, 'groups_leave_group' ], 10, 2 );
		add_action( 'groups_join_group', [ $this, 'groups_join_group' ], 10, 2 );
		add_action( 'groups_demote_member', [ $this, 'groups_demote_member' ], 10, 2 );
		add_action( 'groups_promote_member', [ $this, 'groups_promote_member' ], 10, 3 );
		add_action( 'groups_ban_member', [ $this, 'groups_ban_member' ], 10, 2 );
		add_action( 'groups_unban_member', [ $this, 'groups_unban_member' ], 10, 2 );
		add_action( 'bp_rest_group_members_update_item', [ $this, 'bp_rest_group_members_update_item' ], 10, 5 );
		add_action( 'groups_remove_member', [ $this, 'groups_remove_member' ], 10, 2 );
		add_action( 'bp_rest_group_members_delete_item', [ $this, 'bp_rest_group_members_delete_item' ], 10, 5 );
		return true;
	}

	/**
	 * Performs post-launch operations if needed.
	 *
	 * @since    2.4.0
	 */
	protected function launched() {
		// No post-launch operations
	}

	/**
	 * "bp_before_activity_delete" event.
	 *
	 * @param array $args.
	 * @since    2.4.0
	 */
	public function bp_before_activity_delete( $args = [] ) {
		if ( empty( $args['id'] ) ) {
			$this->delete_activity_args = $args;
		} else {
			$activity               = new \BP_Activity_Activity( $args['id'] );
			$this->deleted_activity = $activity;
		}
	}

	/**
	 * "bp_activity_deleted_activities" event.
	 *
	 * @param array $activities.
	 * @since    2.4.0
	 */
	public function bp_activity_deleted_activities( $activities = [] ) {
		if ( 1 === count( $activities ) && isset( $this->deleted_activity ) ) {
			$activity = $this->deleted_activity;
			$this->logger->info( sprintf( 'Activity deleted: "%s".', $activity->action ) );
		} else {
			if ( $this->ignore_activity_bulk_deletion ) {
				$this->ignore_activity_bulk_deletion = false;
			} else {
				$this->logger->info( sprintf( '%d activities deleted.', count( $activities ) ) );
			}
		}
	}

	/**
	 * "bp_activity_mark_as_spam" event.
	 *
	 * @param array $activity.
	 * @param integer $by.
	 * @since    2.4.0
	 */
	public function bp_activity_mark_as_spam( $activity, $by = 0 ) {
		$this->logger->notice( sprintf( 'Activity marked as spam: "%s".', $activity->action ) );
	}

	/**
	 * "bp_activity_mark_as_ham" event.
	 *
	 * @param array $activity.
	 * @param integer $by.
	 * @since    2.4.0
	 */
	public function bp_activity_mark_as_ham( $activity, $by = 0 ) {
		$this->logger->info( sprintf( 'Activity marked as not spam: "%s".', $activity->action ) );
	}

	/**
	 * "bp_activity_admin_edit_after" event.
	 *
	 * @param array $activity.
	 * @param object $error.
	 * @since    2.4.0
	 */
	public function bp_activity_admin_edit_after( $activity, $error = null ) {
		$this->logger->info( sprintf( 'Activity updated: "%s".', $activity->action ) );
	}

	/**
	 * "groups_create_group" event.
	 *
	 * @param integer $group_id.
	 * @param object $member.
	 * @param object $group.
	 * @since    2.4.0
	 */
	public function groups_create_group( $group_id, $member = null, $group = null ) {
		$group  = null;
		$id     = 0;
		$title  = '';
		$author = '';
		if ( is_numeric( $group_id ) ) {
			$group = \groups_get_group( $group_id );
		}
		if ( $group instanceof \BP_Groups_Group ) {
			$id     = $group->id;
			$title  = $group->name;
			$author = $this->get_user( $group->creator_id );
		}
		$this->logger->info( sprintf( 'Group created: "%s" (group ID %s) by %s.', $title, $id, $author ) );
	}

	/**
	 * "groups_update_group" multi-event.
	 *
	 * @param integer $group_id.
	 * @param object $group.
	 * @since    2.4.0
	 */
	public function groups_update_group( $group_id, $group = null ) {
		$group  = null;
		$id     = 0;
		$title  = '';
		$author = '';
		if ( is_numeric( $group_id ) ) {
			$group = \groups_get_group( $group_id );
		}
		if ( $group instanceof \BP_Groups_Group ) {
			$id     = $group->id;
			$title  = $group->name;
			$author = $this->get_user( $group->creator_id );
		}
		$this->logger->info( sprintf( 'Group updated: "%s" (group ID %s) by %s.', $title, $id, $author ) );
	}

	/**
	 * "groups_leave_group" event.
	 *
	 * @param integer $group_id.
	 * @param integer $user_id.
	 * @since    2.4.0
	 */
	public function groups_leave_group( $group_id, $user_id = 0 ) {
		$group = null;
		$id    = 0;
		$title = '';
		$user  = $this->get_user( (int) $user_id );
		if ( is_numeric( $group_id ) ) {
			$group = \groups_get_group( $group_id );
		}
		if ( $group instanceof \BP_Groups_Group ) {
			$id    = $group->id;
			$title = $group->name;
		}
		$this->logger->info( sprintf( '%s has left "%s" (group ID %s).', $user, $title, $id ) );
	}

	/**
	 * "groups_join_group" event.
	 *
	 * @param integer $group_id.
	 * @param integer $user_id.
	 * @since    2.4.0
	 */
	public function groups_join_group( $group_id, $user_id = 0 ) {
		$group = null;
		$id    = 0;
		$title = '';
		$user  = $this->get_user( (int) $user_id );
		if ( is_numeric( $group_id ) ) {
			$group = \groups_get_group( $group_id );
		}
		if ( $group instanceof \BP_Groups_Group ) {
			$id    = $group->id;
			$title = $group->name;
		}
		$this->logger->info( sprintf( '%s has joined "%s" (group ID %s).', $user, $title, $id ) );
	}

	/**
	 * "groups_demote_member" event.
	 *
	 * @param integer $group_id.
	 * @param integer $user_id.
	 * @since    2.4.0
	 */
	public function groups_demote_member( $group_id, $user_id = 0 ) {
		$group = null;
		$id    = 0;
		$title = '';
		$user  = $this->get_user( (int) $user_id );
		if ( is_numeric( $group_id ) ) {
			$group = \groups_get_group( $group_id );
		}
		if ( $group instanceof \BP_Groups_Group ) {
			$id    = $group->id;
			$title = $group->name;
		}
		$this->logger->info( sprintf( '%s has been demoted of "%s" (group ID %s).', $user, $title, $id ) );
	}

	/**
	 * "groups_promote_member" event.
	 *
	 * @param integer $group_id.
	 * @param integer $user_id.
	 * @param string  $status.
	 * @since    2.4.0
	 */
	public function groups_promote_member( $group_id, $user_id = 0, $status = 'admin' ) {
		$group = null;
		$id    = 0;
		$title = '';
		$user  = $this->get_user( (int) $user_id );
		$role  = 'unknown';
		if ( 'admin' === $status ) {
			$role = 'administrator';
		}
		if ( 'mod' === $status ) {
			$role = 'moderator';
		}
		if ( is_numeric( $group_id ) ) {
			$group = \groups_get_group( $group_id );
		}
		if ( $group instanceof \BP_Groups_Group ) {
			$id    = $group->id;
			$title = $group->name;
		}
		$this->logger->info( sprintf( '%s has been promoted %s of "%s" (group ID %s).', $user, $role, $title, $id ) );
	}

	/**
	 * "groups_ban_member" event.
	 *
	 * @param integer $group_id.
	 * @param integer $user_id.
	 * @since    2.4.0
	 */
	public function groups_ban_member( $group_id, $user_id = 0 ) {
		$group = null;
		$id    = 0;
		$title = '';
		$user  = $this->get_user( (int) $user_id );
		if ( is_numeric( $group_id ) ) {
			$group = \groups_get_group( $group_id );
		}
		if ( $group instanceof \BP_Groups_Group ) {
			$id    = $group->id;
			$title = $group->name;
		}
		$this->logger->notice( sprintf( '%s has been banned from "%s" (group ID %s).', $user, $title, $id ) );
	}

	/**
	 * "groups_unban_member" event.
	 *
	 * @param integer $group_id.
	 * @param integer $user_id.
	 * @since    2.4.0
	 */
	public function groups_unban_member( $group_id, $user_id = 0 ) {
		$group = null;
		$id    = 0;
		$title = '';
		$user  = $this->get_user( (int) $user_id );
		if ( is_numeric( $group_id ) ) {
			$group = \groups_get_group( $group_id );
		}
		if ( $group instanceof \BP_Groups_Group ) {
			$id    = $group->id;
			$title = $group->name;
		}
		$this->logger->info( sprintf( '%s has been unbanned from "%s" (group ID %s).', $user, $title, $id ) );
	}

	/**
	 * "bp_rest_group_members_update_item" event.
	 *
	 * @param \WP_User          $user         The updated member.
	 * @param \BP_Groups_Member $group_member The group member object.
	 * @param \BP_Groups_Group  $group        The group object.
	 * @param \WP_REST_Response $response     The response data.
	 * @param \WP_REST_Request  $request      The request sent to the API.
	 * @since    2.4.0
	 */
	public function bp_rest_group_members_update_item( $user, $group_member, $group, $response, $request ) {
		$action = $request['action'];
		$role   = $request['role'];
		switch ( $action ) {
			case 'promote':
				$this->groups_promote_member( $group->id, $user->ID, $role );
				break;
			case 'demote':
				$this->groups_demote_member( $group->id, $user->ID );
				break;
			case 'ban':
				$this->groups_ban_member( $group->id, $user->ID );
				break;
			case 'unban':
				$this->groups_unban_member( $group->id, $user->ID );
				break;
		}
	}

	/**
	 * "groups_remove_member" event.
	 *
	 * @param integer $group_id.
	 * @param integer $user_id.
	 * @since    2.4.0
	 */
	public function groups_remove_member( $group_id, $user_id = 0 ) {
		$group = null;
		$id    = 0;
		$title = '';
		$user  = $this->get_user( (int) $user_id );
		if ( is_numeric( $group_id ) ) {
			$group = \groups_get_group( $group_id );
		}
		if ( $group instanceof \BP_Groups_Group ) {
			$id    = $group->id;
			$title = $group->name;
		}
		$this->logger->info( sprintf( '%s has been removed from "%s" (group ID %s).', $user, $title, $id ) );
	}

	/**
	 * "bp_rest_group_members_delete_item" event.
	 *
	 * @param \WP_User          $user     The updated member.
	 * @param \BP_Groups_Member $member   The group member object.
	 * @param \BP_Groups_Group  $group    The group object.
	 * @param \WP_REST_Response $response The response data.
	 * @param \WP_REST_Request  $request  The request sent to the API.
	 * @since    2.4.0
	 */
	public function bp_rest_group_members_delete_item( $user, $member, $group, $response, $request ) {
		$this->groups_remove_member( $group->id, $user->ID );
	}

	/**
	 * Finalizes monitoring operations.
	 *
	 * @since    3.0.0
	 */
	public function monitoring_close() {
		if ( ! $this->is_available() ) {
			return;
		}
		// No monitors to finalize.
	}
}
