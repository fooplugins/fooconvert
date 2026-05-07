<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fooconvert_sort = empty( $sort ) ? 'engagement' : $sort;

$fooconvert_sort_object = fooconvert_popup_metric_options()[ $fooconvert_sort ];

$fooconvert_stats = new FooPlugins\FooConvert\Stats();

$fooconvert_top_performers = $fooconvert_stats->get_top_performers( $fooconvert_sort );

if ( empty( $fooconvert_top_performers ) ) {
    if ( isset( $fooconvert_sort_object['pro_feature'] ) && $fooconvert_sort_object['pro_feature'] ) {
        echo '<p class="fooconvert-padding">' . esc_html( $fooconvert_sort_object['pro_message'] ) . '</p>';
        echo '<p class="fooconvert-padding"><a class="button button-primary button-large" href="' . esc_url( fooconvert_admin_url_pricing() ) . '">' . esc_html__( 'Buy FooConvert PRO!', 'fooconvert' ) . '</a></p>';
    } else if ( fooconvert_has_stats_last_updated() ) {
        echo '<p class="fooconvert-padding">' . esc_html__( 'No top performers found!', 'fooconvert' ) . '</p>';
    } else {
        echo '<p class="fooconvert-padding">' . esc_html__( 'Please update stats in order to see top performers.', 'fooconvert' );
    }
} else {
    echo '<table class="fooconvert-dashboard-table fooconvert-top-performers-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>' . esc_html__( 'Rank', 'fooconvert' ) . '</th>';
    echo '<th>' . esc_html__( 'Title', 'fooconvert' ) . '</th>';
    echo '<th>';
    echo esc_html__( 'Metric', 'fooconvert' );
    echo '<span class="fooconvert-tooltip" data-balloon-pos="left" aria-label="' . esc_attr( $fooconvert_sort_object['description'] ) . '"><i class="dashicons dashicons-editor-help"></i></span>';
    echo '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ( $fooconvert_top_performers as $fooconvert_index => $fooconvert_top_performer ) {
        $id = intval( $fooconvert_top_performer['id'] );
        $fooconvert_edit_url = fooconvert_admin_url_popup_edit( $id );
        $fooconvert_edit_link = '<a href="' . esc_url( $fooconvert_edit_url ) . '"><i class="dashicons dashicons-edit" title="' . esc_attr__( 'Edit Popup', 'fooconvert' ) . '"></i></a>';
        $fooconvert_stats_url = fooconvert_admin_url_popup_stats( $id );
        $fooconvert_stats_link = '<a href="' . esc_url( $fooconvert_stats_url ) . '"><i class="dashicons dashicons-chart-bar" title="' . esc_attr__( 'View Popup Stats', 'fooconvert' ) . '"></i></a>';
        $fooconvert_score = $fooconvert_top_performer['score'];
        if ( isset( $fooconvert_sort_object['format'] ) && $fooconvert_sort_object['format'] === 'currency' ) {
            $fooconvert_score = fooconvert_format_revenue( $fooconvert_score );
        }
        $post_type = fooconvert_get_popup_type_label( $fooconvert_top_performer['post_type'] );
        echo '<tr>';
        echo '<td>#' . esc_attr( $fooconvert_index ) . '</td>';
        echo '<td class="fooconvert-top-performers-title-cell"><div class="fooconvert-top-performers-title-row">';
        echo '<span class="fooconvert-top-performers-title">' . esc_html( $fooconvert_top_performer['title'] ) . '</span>';
        echo '<span class="fooconvert-dashboard-pill">' . esc_html( $post_type ) . '</span>';
        echo '<div class="fooconvert-dashboard-table-actions">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $fooconvert_edit_link . ' ' . $fooconvert_stats_link;
        echo '</div></div></td>';
        echo '<td>' . esc_html( $fooconvert_score ) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}
