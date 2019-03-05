<?php
use php\classes;

if (version_compare(PHP_VERSION, '7.0', '<') ) die("Извини, брат, с пыхом ниже 7 - не судьба!\n");

date_default_timezone_set('Europe/Moscow');


# Open main buff
# closed in Render
ob_start();
session_start();


# Use singleton H
require_once 'Helper.php';
// if(array_key_exists('login', $_REQUEST))
// 	H::includeModule('Login');

note($_REQUEST, __FILE__, __LINE__);
// exit;

# Fork on modules || AJAX || full page
# & passing $SV to js
$Router = new Router;

// var_dump($Nav->map);
// exit;

// note($_SESSION, __FILE__, __LINE__);
// note($GLOBALS, __FILE__);

# Rendering full page
header('Content-type: text/html; charset=utf-8');
echo php\classes\Render::finalPage();
# /html