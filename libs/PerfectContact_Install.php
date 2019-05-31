<?php

global $perfectcontact_db_version;
$perfectcontact_db_version = '1.0.0';

class PerfectContact_Install
{
    static $tableName = 'perfect_contact_messages';

    public function activate()
    {
        global $wpdb, $perfectcontact_db_version;

        $tableName = $wpdb->prefix . self::$tableName;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "create table $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            reason text,
            message text,
            name varchar(500),
            email varchar(255),
            telephone varchar(30),
            responded tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charsetCollate";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);

        add_option('perfectcontact_db_version', $perfectcontact_db_version);

        $this->addInitialData();
    }

    public function addInitialData()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . self::$tableName;

        $wpdb->insert($tableName, [
            'reason'     => 'Other',
            'message'    => 'Contact Form successfully installed.',
            'name'       => 'PerfectMy.co.uk',
            'email'      => 'hello@perfectmy.co.uk',
            'telephone'  => '07000 000 000',
            'created_at' => current_time('mysql'),
        ]);
    }
}