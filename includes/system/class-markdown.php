<?php
/**
 * Markdown handling
 *
 * Handles all Markdown operations and rendering.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace Decalog\System;

use cebe\markdownparser\GithubMarkdown;

/**
 * Define the Markdown functionality.
 *
 * Handles all Markdown operations and rendering.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class Markdown {

	/**
	 * Get the changelog.
	 *
	 * @param   string  $file        The filename.
	 * @param   array   $attributes  'style' => 'markdown', 'html'.
	 *                               'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 2.0.0
	 */
	public function get_shortcode( $file, $attributes ) {
		$_attributes = shortcode_atts(
			[
				'style' => 'html',
				'mode'  => 'clean',
			],
			$attributes
		);
		$style       = $_attributes['style'];
		$mode        = $_attributes['mode'];
		$error       = esc_html__( 'Sorry, unable to find or read the specified file.', 'decalog' );
		$result      = esc_html( $error );
		$changelog   = DECALOG_PLUGIN_DIR . $file;
		if ( file_exists( $changelog ) ) {
			try {
				// phpcs:ignore
				$content = wp_kses(file_get_contents( $changelog ), [] );
				if ( $content ) {
					switch ( $style ) {
						case 'html':
							$result = '<div class="markdown">' . $this->html_from_markdown( $content, ( 'clean' === $mode ) ) . '</div>';
							break;
						default:
							$result = esc_html( $content );
					}
				}
			} catch ( \Exception $e ) {
				$result = esc_html( $error );
			}
		}
		return $result;
	}

	/**
	 * Format a changelog in html.
	 *
	 * @param   string  $content  The raw changelog in markdown.
	 * @param   boolean $clean    Optional. Should the output be cleaned?.
	 * @return  string  The converted changelog, ready to print.
	 * @since   2.0.0
	 */
	private function html_from_markdown( $content, $clean = false ) {
		$markdown                 = new GithubMarkdown();
		$markdown->html5          = true;
		$markdown->enableNewlines = true;
		$result                   = $markdown->parse( $content );
		if ( $clean ) {
			$result = preg_replace( '/<h1>.*<\/h1>/iU', '', $result );
			for ( $i = 8; $i > 1; $i-- ) {
				$result = str_replace( [ '<h' . $i . '>', '</h' . $i . '>' ], [ '<h' . (string) ( $i + 1 ) . '>', '</h' . (string) ( $i + 1 ) . '>' ], $result );
			}
			$result = preg_replace_callback(
				'/<h([0-9])>(.*)<\/h([0-9])>/iU',
				function( $matches ) {
					return '<h' . $matches[1] . ' id="' . sanitize_title_with_dashes( $matches[2] ) . '">' . $matches[2] . '</h' . $matches[3] . '>';
				},
				$result
			);
		}
		return wp_kses(
			$result,
			[
				'a'          => [
					'href'  => [],
					'title' => [],
					'rel'   => [],
				],
				'blockquote' => [ 'cite' => [] ],
				'br'         => [],
				'p'          => [],
				'code'       => [ 'class' => [] ],
				'pre'        => [],
				'em'         => [],
				'strong'     => [],
				'ul'         => [],
				'ol'         => [],
				'li'         => [],
				'h3'         => [ 'id' => [] ],
				'h4'         => [ ],
				'h5'         => [ ],
			]
		);
	}

}
