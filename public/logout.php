<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';

initSession();
logoutUser();
redirectTo('cnx.php');
