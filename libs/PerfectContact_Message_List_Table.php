<?php

if (! class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class PerfectContact_Message_List_Table extends WP_List_Table
{
    static $tableName = 'perfect_contact_messages';

    /** Class constructor */
    public function __construct()
    {
        parent::__construct([
            'singular' => __('Message', 'sp'),
            'plural'   => __('Messages', 'sp'),
            'ajax'     => false
        ]);
    }

    /**
     * Retrieve data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_table_data($per_page = 5, $page_number = 1)
    {
        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->prefix}" . self::$tableName;

        if (! empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= ! empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;


        $result = $wpdb->get_results($sql, 'ARRAY_A');

        return $result;
    }

    /**
     * Delete a record.
     *
     * @param int $id ID
     */
    public static function delete_record($id)
    {
        global $wpdb;

        $wpdb->delete(
            "{$wpdb->prefix}" . self::$tableName,
            ['id' => $id],
            ['%d']
        );
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count()
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}" . self::$tableName;

        return $wpdb->get_var($sql);
    }

    /** Text displayed when no customer data is available */
    public function no_items()
    {
        _e('No data avaliable.', 'sp');
    }

    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'message':
            case 'reason':
                return $item[$column_name];
            case 'email':
                return sprintf(
                    '<a href="mailto:%s">%s</a>',
                    $item[$column_name],
                    $item[$column_name]
                );
            case 'telephone':
                return sprintf(
                    '<a href="tel:%s">%s</a>',
                    str_replace(' ', '', $item[$column_name]),
                    $item[$column_name]
                );
            case 'created_at':
                $dateFormat = get_option('date_format');
                $timeFormat = get_option('time_format');
                $date = sprintf('%s at %s', get_date_from_gmt($item[$column_name], $dateFormat), get_date_from_gmt($item[$column_name], $timeFormat));
                return $date;
            case 'responded':
                if ($item[$column_name]) {
                    return '<i class="far fa-check"></i>';
                }

                return '<i class="far fa-times"></i>';
            default:
                return print_r($item, true);
        }
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />',
            $item['id']
        );
    }

    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    function column_name($item)
    {
        $delete_nonce = wp_create_nonce('sp_delete_record');

        $title = '<strong>' . $item['name'] . '</strong>';

        $actions = [
            'delete' => sprintf('<a href="?page=%s&action=%s&customer=%s&_wpnonce=%s">Delete</a>', esc_attr($_REQUEST['page']), 'delete', absint($item['id']), $delete_nonce)
        ];

        return $title . $this->row_actions($actions);
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns()
    {
        return [
            'cb'         => '<input type="checkbox" />',
            'name'       => __('Name', 'sp'),
            'email'      => __('Email', 'sp'),
            'telephone'  => __('Telephone', 'sp'),
            'reason'     => __('Reason', 'sp'),
            'message'    => __('Message', 'sp'),
            'created_at' => __('Submitted At', 'sp'),
            'responded'  => __('Responded', 'sp'),
        ];
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        return [
            'name'       => ['name', false],
            'email'      => ['email', false],
            'telephone'  => ['telephone', false],
            'created_at' => ['created_at', false],
            'responded'  => ['responded', false],
        ];
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        return [
            'bulk-delete' => 'Delete'
        ];
    }

    public function prepare_items()
    {
        $this->_column_headers = $this->get_column_info();

        /** Process bulk action */
        $this->process_bulk_action();

        $per_page     = $this->get_items_per_page('records_per_page', 5);
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $this->items = self::get_table_data($per_page, $current_page);
    }

    public function process_bulk_action()
    {
        // Detect when a bulk action is being triggered...
        if ('delete' === $this->current_action()) {
            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr($_REQUEST['_wpnonce']);

            if (!wp_verify_nonce($nonce, 'sp_delete_record')) {
                die('Access denied.');
            } else {
                self::delete_record(absint($_GET['customer']));

                wp_redirect(esc_url_raw(add_query_arg()));
                exit;
            }

        }

        // If the delete bulk action is triggered
        if ((isset($_POST['action']) && $_POST['action'] == 'bulk-delete')
            || (isset($_POST['action2']) && $_POST['action2'] == 'bulk-delete')
        ) {
            $delete_ids = esc_sql($_POST['bulk-delete']);

            // loop over the array of record IDs and delete them
            foreach ($delete_ids as $id) {
                self::delete_record($id);
            }

            wp_redirect(esc_url_raw(add_query_arg()));
            exit;
        }
    }
}