<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'login';
define('APP_PATH', __DIR__ . '/');
require_once 'Kotchasan/load.php';
$request = Kotchasan\Http\Request::createFromGlobals();
$model = new \Index\Login\Model;
$model->showModal($request);
