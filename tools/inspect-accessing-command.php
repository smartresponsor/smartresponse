<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

echo class_exists(App\Accessing\Command\AccessDemoResetCommand::class) ? "CLASS_EXISTS=1\n" : "CLASS_EXISTS=0\n";

