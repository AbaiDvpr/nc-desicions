<?php
    $branch = $branch ?? 'master';
	$repo   = "git@bitbucket.org:hakuna-matata/ncdecision.com.git";

    $timezone = 'Asia/Almaty';
    $date     = new DateTime('now', new DateTimeZone($timezone));

    $user        = 'nc-user';
    $group       = 'nc-user';
    $storagePath = '/var/www/storage';
    $interpreter = 'php';
    $composer    = 'composer';

    switch ($server ?? 'test') {
        case 'mono': {
            $path = '/var/www/desktop';
            break;
        }

        case 'admin': {
            $path = '/var/www/admin';
            break;
        }

        case 'api': {
            $path = '/var/www/api';
            break;
        }

		default: {
            echo "==== EXIT - server not specified ==== \n\n";
            die;
        }
	}

	$current = $path . '/current';
	$release = $path . '/releases/'. $date->format('YmdHis');
// =========================  For production server  =========================
