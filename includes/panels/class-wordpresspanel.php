<?php

namespace Decalog\Panel;

/**
 * Custom panel based on global $current_screen variable
 *
 * @author Martin Hlaváč
 */
class WordpressPanel extends AbstractPanel {

	/**
	 * {@inheritDoc}
	 */
	protected function get_name() {
		return 'WordPress';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_title() {
		return 'WordPress';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getTab() {
		return $this->get_standard_tab();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPanel() {
		$objects = [];
		global $wp;
		if ( ! empty( $wp ) ) {
			$objects[] = $wp;
		}
		global $current_screen;
		if ( ! empty( $current_screen ) ) {
			$objects[] = $current_screen;
		}
		global $wp_query;
		if ( ! empty( $wp_query ) ) {
			$objects[] = $wp_query;
		}


		global $wp_roles;
		if ( ! empty( $wp_roles ) ) {
			$objects[] = $wp_roles;
		}


		return $this->get_objects_panel( $objects );
	}


	/*
	 *
	 * global $post;
		$queriedObject = get_queried_object();
		if (!empty($queriedObject) && $queriedObject !== $post) {
			$objects[] = $queriedObject;
		}
	 *
	 *
	 * if (is_user_logged_in()) {
            $currentUser = wp_get_current_user();
            $output = parent::getTablePanel([
                __("ID") => $currentUser->ID,
                __("Login") => $currentUser->user_login,
                __("E-mail") => $currentUser->user_email,
                __("Display Name") => $currentUser->display_name,
                __("First Name") => $currentUser->first_name,
                __("Last Name") => $currentUser->last_name,
                __("Roles") => Debugger::dump($currentUser->roles, true),
                __("Allcaps") => Debugger::dump($currentUser->allcaps, true),
            ]);
        }
	 *
	 *
	 *
	 *
	 *
	 *
	 *
	 */

	//$wpdb
	//$post
	//$wp_query;
	//$wp_rewrite
	//$wp_roles
}
