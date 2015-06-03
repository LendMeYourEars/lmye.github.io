<?php
	/****************************************/
	/*          Essential Settings          */
	/****************************************/
	$settings[ 'db_server' ] = 'localhost'; // database server 127.0.0.1
	$settings[ 'db_database' ] = 'stats_lmye'; // database name
	$settings[ 'db_username' ] = 'lmye_user'; // database username
	$settings[ 'db_password' ] = 'BsC7e7hDKJ9wrXxW'; // database password

	$settings[ 'root_username' ] = 'lmye_stats_root'; // root username
	$settings[ 'root_password' ] = '23%vb^EGms893'; // root password


	/****************************************/
	/*           Advanced Settings          */
	/****************************************/
	$settings[ 'salt' ] = 'x$5-*(82f3g6sd49jklavby$%^vk';

	$settings[ 'unauthorized_show_errors' ] = true;
	$settings[ 'unauthorized_muted_errors' ] = true;
	$settings[ 'unauthorized_log_errors' ] = true;

	$settings[ 'authorized_show_errors' ] = true;
	$settings[ 'authorized_muted_errors' ] = false;
	$settings[ 'authorized_log_errors' ] = false;

	$settings[ 'down' ] = false;
	$settings[ 'down_message' ] = 'down for maintenance';

	$settings[ 'profile_name' ] = 'Default';

	$settings[ 'db_charset' ] = 'utf8';
	$settings[ 'db_collation' ] = 'utf8_general_ci';
	$settings[ 'db_mysql_big_selects' ] = false;

	$settings[ 'disable_output_buffering' ] = true;
?>