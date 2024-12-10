<?php

if ( empty( $sort ) ) {
    $sort = 'engagement';
}

$sort_object = fooconvert_top_performers_sort_options()[ $sort ];

$stats = new FooPlugins\FooConvert\Stats();

$top_performers = $stats->get_top_performers( $sort );

if ( empty( $top_performers ) ) {
    if ( isset( $sort_object['pro_feature'] ) && $sort_object['pro_feature'] ) {
        echo '<p class="fooconvert-padding">' . esc_html( $sort_object['pro_message'] ) . '</p>';
        echo '<p class="fooconvert-padding"><a class="button button-primary button-large" href="' . esc_url( fooconvert_admin_url_addons() ) . '">' . __('Buy PRO Analytics!', 'fooconvert'). '</a></p>';
    }
    else if ( fooconvert_has_stats_last_updated() ) {
        echo '<p class="fooconvert-padding">' . __( 'No top performers found!', 'fooconvert' ) . '</p>';
    } else {
        echo '<p class="fooconvert-padding">' . __( 'Please update stats in order to see top performers.', 'fooconvert' );
    }
} else {
    echo '<table class="fooconvert-top-performers-table">';
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
        $edit_url = admin_url( 'post.php?post=' . $id . '&action=edit' );
        $edit_link = '<a href="' . esc_url( $edit_url ) . '"><i class="dashicons dashicons-edit" title="' . esc_attr__( 'Edit Widget', 'fooconvert' ) . '"></i></a>';
        $stats_url = fooconvert_admin_url_widget_stats( $id );
        $stats_link = '<a href="' . esc_url( $stats_url ) . '"><i class="dashicons dashicons-chart-bar" title="' . esc_attr__( 'View Widget Stats', 'fooconvert' ) . '"></i></a>';
        $score = $top_performer['score'];

        echo '<tr>';
        echo '<td>#' . $index . '</td>';
        echo '<td><span>' . $top_performer['title'] . '</span>';
        echo '<div class="fooconvert-top-performers-table-actions">';
        echo $edit_link . ' ' . $stats_link;
        echo '</div>';
        echo '</td>';
        echo '<td>' . $score . '</td>';
        //echo '<td>' . $edit_link . ' ' . $stats_link . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}


