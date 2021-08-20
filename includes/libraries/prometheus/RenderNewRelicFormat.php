<?php

declare(strict_types=1);

namespace Prometheus;

use Decalog\System\Environment;
use Decalog\System\Blog;

class RenderNewRelicFormat implements RendererInterface {

	/**
	 * The array of tag names.
	 *
	 * @since  3.2.0
	 * @var    array    $tagnames    The tag names.
	 */
	private $tagnames = [];

	/**
	 * The array of tag values.
	 *
	 * @since  3.2.0
	 * @var    array    $tagvalues    The tag values.
	 */
	private $tagvalues = [];

	/**
	 * @param MetricFamilySamples[] $metrics
	 * @return string
	 */
	public function render( array $metrics ): string {
		$this->tagnames  = [
			'instance',
			'version',
		];
		$this->tagvalues = [
			gethostname(),
			Environment::wordpress_version_text( true ),
		];
		if ( Environment::is_wordpress_multisite() ) {
			$this->tagnames[]  = 'site';
			$this->tagvalues[] = Blog::get_current_blog_id( 0 );
		}
		$lines = [];
		foreach ( $metrics as $metric ) {
			foreach ( $metric->getSamples() as $sample ) {
				$lines[] = $this->renderSample( $metric, $sample );
			}
		}
		return '[{"metrics":[' . implode( ',', $lines ) . ']}]';
	}

	/**
	 * @param MetricFamilySamples $metric
	 * @param Sample $sample
	 * @return string
	 */
	private function renderSample( MetricFamilySamples $metric, Sample $sample ): string {
		if ( in_array( $metric->getType(), [ 'counter', 'gauge' ], true ) ) {
			$rmetric    = [
				'type'        => 'counter' === $metric->getType() ? 'count' : $metric->getType(),
				'name'        => str_replace( '_', '.', $sample->getName() ),
				'value'       => (float) $sample->getValue(),
				'interval.ms' => 1000,
				'timestamp'   => time(),
			];
			$labelNames = $metric->getLabelNames();
			if ( $metric->hasLabelNames() || $sample->hasLabelNames() ) {
				$rmetric['attributes'] = $this->escapeAllLabels( $labelNames, $sample );
			}
			return wp_json_encode( $rmetric );
		}
		return '';
	}

	/**
	 * @param string $v
	 * @return string
	 */
	private function escapeLabelValue( string $v ): string {
		return str_replace( [ '\\', "\n", '"', '=', ',' ], [ '\\\\', "\\n", '\\"', '', '' ], $v );
	}

	/**
	 * @param string[]  $labelNames
	 * @param Sample $sample
	 *
	 * @return string[]
	 */
	private function escapeAllLabels( array $labelNames, Sample $sample ): array {
		$escapedLabels = [];
		$labels        = array_combine( array_merge( $labelNames, $sample->getLabelNames(), $this->tagnames ), array_merge( $sample->getLabelValues(), $this->tagvalues ) );
		if ( $labels === false ) {
			return [];
		}
		foreach ( $labels as $labelName => $labelValue ) {
			$escapedLabels[$labelName] =  $this->escapeLabelValue( (string) $labelValue );
		}
		return $escapedLabels;
	}
}
