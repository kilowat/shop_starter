<?php

foreach (glob(__DIR__ . '/*/init.php') as $file) {
    require_once $file;
}