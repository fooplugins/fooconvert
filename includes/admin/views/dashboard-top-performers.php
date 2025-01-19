<?php

if ( empty( $sort ) ) {
    $sort = 'engagement';
}

$sort_object = fooconvert_widget_metric_options()[ $sort ];

$stats = new FooPlugins\FooConvert\Stats();

$top_performers = $stats->get_top_performers( $sort );

if ( empty( $top_performers ) ) {
    if ( isset( $sort_object['pro_feature'] ) && $sort_object['pro_feature'] ) {
        echo '<p class="fooconvert-padding">' . esc_html( $sort_object['pro_message'] ) . '</p>';
        echo '<p class="fooconvert-padding"><a class="button button-primary button-large" href="' . esc_url( fooconvert_admin_url_addons() ) . '">' . esc_html__('Buy PRO Analytics!', 'fooconvert'). '</a></p>';
    }
    else if ( fooconvert_has_stats_last_updated() ) {
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
    echo esc_html( $sort_object['table_header'] );
    echo '<span class="fooconvert-tooltip" data-balloon-pos="left" aria-label="' . esc_attr( $sort_object['tooltip'] ) . '"><i class="dashicons dashicons-editor-help"></i></span>';
    echo '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ( $top_performers as $index => $top_performer ) {
        $id = intval( $top_performer['id'] );
        $edit_url = fooconvert_admin_url_widget_edit( $id );
        $edit_link = '<a href="' . esc_url( $edit_url ) . '"><i class="dashicons dashicons-edit" title="' . esc_attr__( 'Edit Widget', 'fooconvert' ) . '"></i></a>';
        $stats_url = fooconvert_admin_url_widget_stats( $id );
        $stats_link = '<a href="' . esc_url( $stats_url ) . '"><i class="dashicons dashicons-chart-bar" title="' . esc_attr__( 'View Widget Stats', 'fooconvert' ) . '"></i></a>';
        $score = $top_performer['score'];

        echo '<tr>';
        echo '<td>#' . esc_attr( $index ) . '</td>';
        echo '<td><span>' . esc_html( $top_performer['title'] ) . '</span>';
        echo '<div class="fooconvert-dashboard-table-actions">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $edit_link . ' ' . $stats_link;
        echo '</div>';
        echo '</td>';
        echo '<td>' . esc_html( $score ) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}


