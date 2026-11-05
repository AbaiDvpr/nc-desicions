<!DOCTYPE html>
<html lang="en">
    <head>
        <base href="./">
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
        <meta name="description" content="NC-decision: admin panel">
        <meta name="author" content="Lyos Anatoliy">
        <meta name="keyword" content="NC-decision">
        <title>NC-decision: admin panel</title>
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="theme-color" content="#ffffff">

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Main styles for this application-->
        <link rel="stylesheet" href="/admin/vendors/bootstrap-icons/bootstrap-icons.css">
        <link rel="stylesheet" href="/admin/assets/icons/css/all.css">

        <link rel="stylesheet" href="{{ mix('/admin/css/app.css') }}">
    </head>

    <body class="c-app flex-row align-items-center">
