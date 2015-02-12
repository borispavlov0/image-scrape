<?php

if (file_exists(__DIR__ . "/../vendor/autoload.php")) {
    require __DIR__ . "/../vendor/autoload.php";
} elseif (file_exists(__DIR__ . "/../../../../vendor/autoload.php")) {
    require __DIR__ . "/../../../../vendor/autoload.php";
} else {
    die('You need to update the vendors using composer.');
}

