<?php

declare(strict_types=1);

namespace Prometheus;

use Decalog\System\Environment;
use Decalog\System\Blog;

class RenderDatadogFormat implements RendererInterface {

	/**
	 * The array of tag names.
	 *
	 * @since  3.0.0
	 * @var    array    $tagnames    The tag names.
	 */
	private $tagnames = [];

	/**
	 * The array of tag values.
	 *
	 * @since  3.0.0
	 * @var    array    $tagvalues    The tag values.
	 */
	private $tagvalues = [];

	/**
	 * The hostname.
	 *
	 * @since  3.0.0
	 * @var    string    $host    The hostname.
	 */
	private $host = '';

	/**
	 * @param MetricFamilySamples[] $metrics
	 * @return string
	 */
	public function render( array $metrics ): string {
		$this->host = gethostname();
		$lines      = [];
		foreach ( $metrics as $metric ) {
			foreach ( $metric->getSamples() as $sample ) {
				$lines[] = $this->renderSample( $metric, $sample );
			}
		}
		return '{"series":[' . implode( ',', $lines ) . ']}';
	}

	/**
	 * @param MetricFamilySamples $metric
	 * @param Sample $sample
	 * @return string
	 */
	private function renderSample( MetricFamilySamples $metric, Sample $sample ): string {
		$rmetric    = [
			'host'   => $this->host,
			'type'   => $metric->getType(),
			'metric' => str_replace( '_', '.', $sample->getName() ),
			'points' => [
				[
					(string) time(),
					(string) $sample->getValue(),
				],
			],
		];
		$labelNames = $metric->getLabelNames();
		if ( $metric->hasLabelNames() || $sample->hasLabelNames() ) {
			$rmetric['tags'] = $this->escapeAllLabels( $labelNames, $sample );
		}
		return wp_json_encode( $rmetric );
	}

	/**
	 * @param string $v
	 * @return string
	 */
	private function escapeLabelValue( string $v ): string {
		return str_replace( [ '\\', "\n", '"', ' ', '=', ',' ], [ '\\\\', "\\n", '\\"', '\\ ', '', '' ], $v );
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
			$escapedLabels[] = $labelName . ':' . $this->escapeLabelValue( (string) $labelValue );
		}
		return $escapedLabels;
	}
}
