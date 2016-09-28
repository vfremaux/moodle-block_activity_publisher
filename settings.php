<?php  //$Id: settings.php,v 1.2 2011-11-20 12:50:23 vf Exp $

$settings->add(new admin_setting_configselect('activity_publisher_keep_files_safe', get_string('keepfilessafe', 'block_activity_publisher'),
                   get_string('configkeepfilessafe', 'block_activity_publisher'), 1, array(0 => get_string('no'), 1 => get_string('yes'))));

$settings->add(new admin_setting_configtext('activity_publisher_unable_mods', get_string('modulesunabledtopublish', 'block_activity_publisher'),
                   get_string('configmodulesunabledtopublish', 'block_activity_publisher'), ''));
