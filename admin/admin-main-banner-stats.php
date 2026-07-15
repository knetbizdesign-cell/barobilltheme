<?php
/**
 * Main배너 > 인사이트
 *
 * @package borobill_theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return string
 */
function borobill_get_main_banner_stats_menu_slug() {
	return 'borobill-main-banner-stats';
}

/**
 * Main배너 인사이트 페이지
 */
function borobill_render_main_banner_stats_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$period_context = function_exists( 'borobill_get_stats_visitor_chart_context' )
		? borobill_get_stats_visitor_chart_context()
		: array(
			'period'        => '1',
			'summary_label' => '당일',
			'from'          => wp_date( 'Y-m-d' ),
			'to'            => wp_date( 'Y-m-d' ),
		);

	if ( function_exists( 'borobill_resolve_stats_period_dates' ) ) {
		list( $period_from, $period_to ) = borobill_resolve_stats_period_dates( $period_context );
	} else {
		$period_from = isset( $period_context['from'] ) ? (string) $period_context['from'] : wp_date( 'Y-m-d' );
		$period_to   = isset( $period_context['to'] ) ? (string) $period_context['to'] : wp_date( 'Y-m-d' );
	}

	$period_context['from'] = $period_from;
	$period_context['to']   = $period_to;

	$period_options = array(
		'1'      => '1일(당일)',
		'5'      => '5일',
		'10'     => '10일',
		'15'     => '15일',
		'20'     => '20일',
		'25'     => '25일',
		'30'     => '30일',
		'custom' => '직접선택',
	);
	$daily_period      = isset( $period_context['period'] ) ? (string) $period_context['period'] : '1';
	$daily_custom_from = $period_from;
	$daily_custom_to   = $period_to;
	$is_custom_period  = ( 'custom' === $daily_period );
	$period_range_label = wp_date( 'Y.m.d', strtotime( $period_from ) ) . ' ~ ' . wp_date( 'Y.m.d', strtotime( $period_to ) );

	$top_rows = function_exists( 'borobill_get_banner_insight_report_rows' )
		? borobill_get_banner_insight_report_rows( $period_context )
		: array();
	$bottom_rows = function_exists( 'borobill_get_bottom_banner_insight_report_rows' )
		? borobill_get_bottom_banner_insight_report_rows( $period_context )
		: array();
	$top_click_bar_rows = function_exists( 'borobill_get_banner_insight_bar_chart_rows' )
		? borobill_get_banner_insight_bar_chart_rows( $top_rows, 'clicks' )
		: array();
	$bottom_click_bar_rows = function_exists( 'borobill_get_banner_insight_bar_chart_rows' )
		? borobill_get_banner_insight_bar_chart_rows( $bottom_rows, 'clicks' )
		: array();
	$has_banners = ! empty( $top_rows ) || ! empty( $bottom_rows );
	?>
	<div class="wrap borobill-main-banner-stats-page borobill-banner-insights">
		<div class="borobill-stats-header borobill-banner-insights__header">
			<div class="borobill-stats-header__text">
				<h1>인사이트</h1>
				<p class="borobill-banner-insights__desc">배너 버튼을 누른 <strong>클릭 수</strong>를 배너별로 보여줍니다.</p>
			</div>
			<form method="get" class="borobill-stats-daily-period-form borobill-stats-period-global borobill-banner-insights__period-form" aria-label="기간 설정">
				<input type="hidden" name="page" value="<?php echo esc_attr( borobill_get_main_banner_stats_menu_slug() ); ?>" />
				<span class="borobill-stats-period-global__range" aria-hidden="true">
					<span class="borobill-stats-period-global__icon"></span>
					<?php echo esc_html( $period_range_label ); ?>
				</span>
				<label class="screen-reader-text" for="borobill-banner-insight-period">조회 기간</label>
				<span class="borobill-stats-daily-period-form__select-wrap">
					<select id="borobill-banner-insight-period" name="daily_period">
						<?php foreach ( $period_options as $option_key => $option_label ) : ?>
							<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $daily_period, $option_key ); ?>>
								<?php echo esc_html( $option_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="borobill-stats-daily-period-form__arrow" aria-hidden="true"></span>
				</span>
				<span class="borobill-stats-daily-period-form__custom<?php echo $is_custom_period ? ' is-visible' : ''; ?>"<?php echo $is_custom_period ? '' : ' hidden'; ?>>
					<label class="screen-reader-text" for="borobill-banner-insight-from">시작일</label>
					<input
						type="date"
						id="borobill-banner-insight-from"
						name="daily_from"
						class="borobill-stats-daily-period-form__date-input"
						value="<?php echo esc_attr( $daily_custom_from ); ?>"
					/>
					<span class="borobill-stats-daily-period-form__range-sep" aria-hidden="true">~</span>
					<label class="screen-reader-text" for="borobill-banner-insight-to">종료일</label>
					<input
						type="date"
						id="borobill-banner-insight-to"
						name="daily_to"
						class="borobill-stats-daily-period-form__date-input"
						value="<?php echo esc_attr( $daily_custom_to ); ?>"
					/>
				</span>
			</form>
		</div>

		<?php if ( ! $has_banners ) : ?>
			<p class="borobill-banner-insights__empty">집계할 배너가 없습니다.</p>
		<?php else : ?>
			<div class="borobill-banner-insights__charts">
				<section class="borobill-banner-insights__chart-panel">
					<h2 class="borobill-banner-insights__chart-title">상단배너 클릭 수</h2>
					<?php
					if ( function_exists( 'borobill_render_top_posts_views_bar_chart' ) ) {
						borobill_render_top_posts_views_bar_chart( $top_click_bar_rows );
					}
					?>
				</section>
				<section class="borobill-banner-insights__chart-panel">
					<h2 class="borobill-banner-insights__chart-title">하단배너 클릭 수</h2>
					<?php
					if ( function_exists( 'borobill_render_top_posts_views_bar_chart' ) ) {
						borobill_render_top_posts_views_bar_chart( $bottom_click_bar_rows );
					}
					?>
				</section>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
