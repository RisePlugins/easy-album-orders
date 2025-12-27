<?php
/**
 * Reports page view.
 *
 * Displays revenue and sales reporting with KPIs, charts, and date filters.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get the current range for active state.
$current_range = isset( $_GET['range'] ) ? sanitize_key( $_GET['range'] ) : '30days';
$base_url      = admin_url( 'edit.php?post_type=album_order&page=eao-reports' );

// Date range options.
$date_ranges = array(
    'today'        => __( 'Today', 'easy-album-orders' ),
    '7days'        => __( '7 Days', 'easy-album-orders' ),
    '30days'       => __( '30 Days', 'easy-album-orders' ),
    'this_month'   => __( 'This Month', 'easy-album-orders' ),
    'last_month'   => __( 'Last Month', 'easy-album-orders' ),
    'this_quarter' => __( 'Quarter', 'easy-album-orders' ),
    'this_year'    => __( 'Year', 'easy-album-orders' ),
    'all_time'     => __( 'All Time', 'easy-album-orders' ),
);
?>
<div class="wrap eao-admin-wrap eao-reports-page">
    <div class="eao-reports-header">
        <div class="eao-reports-header__title">
            <h1><?php esc_html_e( 'Reports', 'easy-album-orders' ); ?></h1>
            <span class="eao-reports-header__range"><?php echo esc_html( $range_label ); ?></span>
        </div>
        
        <div class="eao-reports-header__filters">
            <div class="eao-date-filters">
                <?php foreach ( $date_ranges as $range_key => $range_name ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( 'range', $range_key, $base_url ) ); ?>" 
                       class="eao-date-filter <?php echo $current_range === $range_key ? 'eao-date-filter--active' : ''; ?>">
                        <?php echo esc_html( $range_name ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="eao-reports-kpis">
        <div class="eao-reports-kpi">
            <div class="eao-reports-kpi__icon eao-reports-kpi__icon--revenue">
                <?php EAO_Icons::render( 'credit-card', array( 'size' => 24 ) ); ?>
            </div>
            <div class="eao-reports-kpi__content">
                <span class="eao-reports-kpi__value"><?php echo esc_html( eao_format_price( $report_data['total_revenue'] ) ); ?></span>
                <span class="eao-reports-kpi__label"><?php esc_html_e( 'Total Revenue', 'easy-album-orders' ); ?></span>
            </div>
        </div>

        <div class="eao-reports-kpi">
            <div class="eao-reports-kpi__icon eao-reports-kpi__icon--orders">
                <?php EAO_Icons::render( 'shopping-cart', array( 'size' => 24 ) ); ?>
            </div>
            <div class="eao-reports-kpi__content">
                <span class="eao-reports-kpi__value"><?php echo esc_html( $report_data['total_orders'] ); ?></span>
                <span class="eao-reports-kpi__label"><?php esc_html_e( 'Total Orders', 'easy-album-orders' ); ?></span>
            </div>
        </div>

        <div class="eao-reports-kpi">
            <div class="eao-reports-kpi__icon eao-reports-kpi__icon--average">
                <?php EAO_Icons::render( 'receipt', array( 'size' => 24 ) ); ?>
            </div>
            <div class="eao-reports-kpi__content">
                <span class="eao-reports-kpi__value"><?php echo esc_html( eao_format_price( $report_data['avg_order_value'] ) ); ?></span>
                <span class="eao-reports-kpi__label"><?php esc_html_e( 'Avg. Order Value', 'easy-album-orders' ); ?></span>
            </div>
        </div>

        <div class="eao-reports-kpi">
            <div class="eao-reports-kpi__icon eao-reports-kpi__icon--pending">
                <?php EAO_Icons::render( 'truck', array( 'size' => 24 ) ); ?>
            </div>
            <div class="eao-reports-kpi__content">
                <span class="eao-reports-kpi__value"><?php echo esc_html( $report_data['ordered_count'] ); ?></span>
                <span class="eao-reports-kpi__label"><?php esc_html_e( 'Awaiting Shipment', 'easy-album-orders' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="eao-reports-charts">
        <!-- Revenue Chart -->
        <div class="eao-reports-card eao-reports-card--wide">
            <div class="eao-reports-card__header">
                <h3 class="eao-reports-card__title">
                    <?php EAO_Icons::render( 'chart-line', array( 'size' => 18 ) ); ?>
                    <?php esc_html_e( 'Revenue Over Time', 'easy-album-orders' ); ?>
                </h3>
            </div>
            <div class="eao-reports-card__body">
                <div class="eao-chart-container">
                    <canvas id="eao-revenue-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Orders by Status -->
        <div class="eao-reports-card">
            <div class="eao-reports-card__header">
                <h3 class="eao-reports-card__title">
                    <?php EAO_Icons::render( 'chart-pie', array( 'size' => 18 ) ); ?>
                    <?php esc_html_e( 'Orders by Status', 'easy-album-orders' ); ?>
                </h3>
            </div>
            <div class="eao-reports-card__body">
                <div class="eao-chart-container eao-chart-container--doughnut">
                    <canvas id="eao-status-chart"></canvas>
                </div>
                <div class="eao-status-legend">
                    <div class="eao-status-legend__item">
                        <span class="eao-status-legend__color eao-status-legend__color--submitted"></span>
                        <span class="eao-status-legend__label"><?php esc_html_e( 'In Cart', 'easy-album-orders' ); ?></span>
                        <span class="eao-status-legend__value"><?php echo esc_html( $report_data['submitted_count'] ); ?></span>
                    </div>
                    <div class="eao-status-legend__item">
                        <span class="eao-status-legend__color eao-status-legend__color--ordered"></span>
                        <span class="eao-status-legend__label"><?php esc_html_e( 'Ordered', 'easy-album-orders' ); ?></span>
                        <span class="eao-status-legend__value"><?php echo esc_html( $report_data['ordered_count'] ); ?></span>
                    </div>
                    <div class="eao-status-legend__item">
                        <span class="eao-status-legend__color eao-status-legend__color--shipped"></span>
                        <span class="eao-status-legend__label"><?php esc_html_e( 'Shipped', 'easy-album-orders' ); ?></span>
                        <span class="eao-status-legend__value"><?php echo esc_html( $report_data['shipped_count'] ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Charts Row -->
    <div class="eao-reports-charts">
        <!-- Monthly Comparison -->
        <div class="eao-reports-card eao-reports-card--wide">
            <div class="eao-reports-card__header">
                <h3 class="eao-reports-card__title">
                    <?php EAO_Icons::render( 'chart-bar', array( 'size' => 18 ) ); ?>
                    <?php esc_html_e( 'Monthly Performance', 'easy-album-orders' ); ?>
                </h3>
            </div>
            <div class="eao-reports-card__body">
                <div class="eao-chart-container">
                    <canvas id="eao-monthly-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="eao-reports-card">
            <div class="eao-reports-card__header">
                <h3 class="eao-reports-card__title">
                    <?php EAO_Icons::render( 'award', array( 'size' => 18 ) ); ?>
                    <?php esc_html_e( 'Popular Choices', 'easy-album-orders' ); ?>
                </h3>
            </div>
            <div class="eao-reports-card__body">
                <?php if ( ! empty( $report_data['top_materials'] ) || ! empty( $report_data['top_sizes'] ) ) : ?>
                    <?php if ( ! empty( $report_data['top_materials'] ) ) : ?>
                        <div class="eao-top-list">
                            <h4 class="eao-top-list__title"><?php esc_html_e( 'Materials', 'easy-album-orders' ); ?></h4>
                            <?php foreach ( $report_data['top_materials'] as $index => $material ) : ?>
                                <div class="eao-top-list__item">
                                    <span class="eao-top-list__rank"><?php echo esc_html( $index + 1 ); ?></span>
                                    <span class="eao-top-list__name"><?php echo esc_html( $material['material'] ); ?></span>
                                    <span class="eao-top-list__count"><?php echo esc_html( $material['count'] ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $report_data['top_sizes'] ) ) : ?>
                        <div class="eao-top-list">
                            <h4 class="eao-top-list__title"><?php esc_html_e( 'Sizes', 'easy-album-orders' ); ?></h4>
                            <?php foreach ( $report_data['top_sizes'] as $index => $size ) : ?>
                                <div class="eao-top-list__item">
                                    <span class="eao-top-list__rank"><?php echo esc_html( $index + 1 ); ?></span>
                                    <span class="eao-top-list__name"><?php echo esc_html( $size['size'] ); ?></span>
                                    <span class="eao-top-list__count"><?php echo esc_html( $size['count'] ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="eao-reports-empty">
                        <?php EAO_Icons::render( 'chart-dots', array( 'size' => 32 ) ); ?>
                        <p><?php esc_html_e( 'No data available for this period.', 'easy-album-orders' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Wait for Chart.js to load.
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js not loaded');
        return;
    }

    // Chart.js defaults.
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#6b7280';

    // Revenue Over Time Chart.
    var revenueCtx = document.getElementById('eao-revenue-chart');
    if (revenueCtx) {
        var revenueData = <?php echo wp_json_encode( array_values( $report_data['revenue_by_day'] ) ); ?>;
        var revenueLabels = <?php echo wp_json_encode( array_map( function( $date ) {
            return date_i18n( 'M j', strtotime( $date ) );
        }, array_keys( $report_data['revenue_by_day'] ) ) ); ?>;

        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: '<?php esc_html_e( 'Revenue', 'easy-album-orders' ); ?>',
                    data: revenueData,
                    borderColor: '#3858e9',
                    backgroundColor: 'rgba(56, 88, 233, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#3858e9',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return '<?php echo esc_js( eao_get_currency_symbol() ); ?>' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 10
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6'
                        },
                        ticks: {
                            callback: function(value) {
                                return '<?php echo esc_js( eao_get_currency_symbol() ); ?>' + value;
                            }
                        }
                    }
                }
            }
        });
    }

    // Orders by Status Chart.
    var statusCtx = document.getElementById('eao-status-chart');
    if (statusCtx) {
        var statusData = [
            <?php echo intval( $report_data['submitted_count'] ); ?>,
            <?php echo intval( $report_data['ordered_count'] ); ?>,
            <?php echo intval( $report_data['shipped_count'] ); ?>
        ];
        var hasData = statusData.some(function(v) { return v > 0; });

        if (hasData) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: [
                        '<?php esc_html_e( 'In Cart', 'easy-album-orders' ); ?>',
                        '<?php esc_html_e( 'Ordered', 'easy-album-orders' ); ?>',
                        '<?php esc_html_e( 'Shipped', 'easy-album-orders' ); ?>'
                    ],
                    datasets: [{
                        data: statusData,
                        backgroundColor: ['#f59e0b', '#3b82f6', '#10b981'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            cornerRadius: 8
                        }
                    }
                }
            });
        }
    }

    // Monthly Performance Chart.
    var monthlyCtx = document.getElementById('eao-monthly-chart');
    if (monthlyCtx) {
        var monthlyData = <?php echo wp_json_encode( $report_data['monthly_comparison'] ); ?>;
        var monthLabels = monthlyData.map(function(d) { return d.month; });
        var revenueValues = monthlyData.map(function(d) { return d.revenue; });
        var orderValues = monthlyData.map(function(d) { return d.orders; });

        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: '<?php esc_html_e( 'Revenue', 'easy-album-orders' ); ?>',
                    data: revenueValues,
                    backgroundColor: '#3858e9',
                    borderRadius: 6,
                    yAxisID: 'y'
                }, {
                    label: '<?php esc_html_e( 'Orders', 'easy-album-orders' ); ?>',
                    data: orderValues,
                    backgroundColor: '#10b981',
                    borderRadius: 6,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            boxWidth: 12,
                            boxHeight: 12,
                            padding: 16,
                            usePointStyle: true,
                            pointStyle: 'rectRounded'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    return '<?php echo esc_js( eao_get_currency_symbol() ); ?>' + context.parsed.y.toFixed(2);
                                }
                                return context.parsed.y + ' orders';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6'
                        },
                        ticks: {
                            callback: function(value) {
                                return '<?php echo esc_js( eao_get_currency_symbol() ); ?>' + value;
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
})();
</script>

