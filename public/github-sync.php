<?php

$env     = isset($_GET['env']) ? $_GET['env'] : 'frontend';
$isLocal = isset($_GET['is_local']) ? true : false;

$uri          = 'https://hess3077:T3R2GAvp@github.com/hess3077';
$dir_frontend = !empty($isLocal) ? '&& cd mpc-congo' : '';

$cmd_git_pull  = '&& git pull';

// On lance la synchronisation du serveur de PROD avec le serveur Github

switch ($env) {
    case 'backend':
        `$cmd_git_pull $uri/mpc-congo-backend/`; 
    break;
    default:
        `cd ../../ $dir_frontend && $cmd_git_pull $uri/mpc-congo/ && cp dist/mpccongo/* web/`;
    break;
}
