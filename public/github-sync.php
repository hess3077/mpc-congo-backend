<?php

$env     = isset($_GET['env']) ? $_GET['env'] : 'frontend';
$isLocal = isset($_GET['is_local']) ? true : false;

$uri          = 'https://hess3077:T3R2GAvp@github.com/hess3077';
$dir_frontend = !empty($isLocal) ? '&& cd mpc-congo' : '';

// On lance la synchronisation du serveur de PROD avec le serveur Github

switch ($env) {
    case 'backend':
        `cd backend && git pull $uri/mpc-congo-backend/`; 
    break;
    default:
        `cd ../../ $dir_frontend && git pull $uri/mpc-congo/`;
    break;
}
