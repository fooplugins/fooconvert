<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php
    $lead_stats = ( new \FooPlugins\FooConvert\Lead() )->get_leads_table_stats();
    $total_leads = isset( $lead_stats['Number_of_Rows'] ) ? intval( $lead_stats['Number_of_Rows'] ) : 0;
    $unique_emails = isset( $lead_stats['Unique_Emails'] ) ? intval( $lead_stats['Unique_Emails'] ) : 0;
    ?>
    <div style="margin-bottom: 16px;">
        <?php if ( $total_leads === $unique_emails ) : ?>
            <strong><?php echo esc_html( number_format_i18n( $total_leads ) ); ?></strong> <?php esc_html_e( 'Leads (unique emails)', 'fooconvert' ); ?>
        <?php else : ?>
            <strong><?php echo esc_html( number_format_i18n( $total_leads ) ); ?></strong> <?php esc_html_e( 'Total Leads', 'fooconvert' ); ?>
            &nbsp;|&nbsp;
            <strong><?php echo esc_html( number_format_i18n( $unique_emails ) ); ?></strong> <?php esc_html_e( 'Unique Emails', 'fooconvert' ); ?>
        <?php endif; ?>
    </div>

    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="fooconvert-leads" />
                <select name="date_range" id="date_range" class="date-range">
                    <option value="24hours" <?php selected( isset( $_GET['date_range'] ) ? sanitize_text_field( wp_unslash( $_GET['date_range'] ) ) : '24hours', '24hours' ); ?>><?php esc_html_e( 'Last 24 Hours', 'fooconvert' ); ?></option>
                    <option value="7days" <?php selected( isset( $_GET['date_range'] ) ? sanitize_text_field( wp_unslash( $_GET['date_range'] ) ) : '24hours', '7days' ); ?>><?php esc_html_e( 'Last 7 Days', 'fooconvert' ); ?></option>
                    <option value="30days" <?php selected( isset( $_GET['date_range'] ) ? sanitize_text_field( wp_unslash( $_GET['date_range'] ) ) : '24hours', '30days' ); ?>><?php esc_html_e( 'Last 30 Days', 'fooconvert' ); ?></option>
                    <option value="forever" <?php selected( isset( $_GET['date_range'] ) ? sanitize_text_field( wp_unslash( $_GET['date_range'] ) ) : '24hours', 'forever' ); ?>><?php esc_html_e( 'All Time', 'fooconvert' ); ?></option>
                </select>
                <input type="submit" value="<?php esc_attr_e( 'Filter', 'fooconvert' ); ?>" class="button">
            </form>
        </div>
        <div class="alignright actions">
            <a href="#" class="button add-new-h2" id="export-all-leads"><?php esc_html_e( 'Export Selected Leads', 'fooconvert' ); ?></a>
        </div>
        <br class="clear">
    </div>

    <form method="get">
        <input type="hidden" name="page" value="fooconvert-leads" />
        <?php
        $table = new \FooPlugins\FooConvert\Admin\LeadsTable();
        $table->prepare_items();
        $table->display();
        ?>
    </form>

    <?php if ( empty( $table->items ) && $total_leads > 0 ) : ?>
        <div style="margin-top:20px; padding:12px; background:#fffbe5; border:1px solid #ffe066; color:#856404; border-radius:4px;">
            <?php esc_html_e( 'No leads found for the current filter. Try changing the filter above to see more leads.', 'fooconvert' ); ?>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#export-all-leads').on('click', function(e) {
        e.preventDefault();
        $('#bulk-action-selector-top').val('export');
        $('#doaction').trigger('click');
    });
});
</script>
