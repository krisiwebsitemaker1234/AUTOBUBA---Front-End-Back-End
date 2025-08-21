<?php
require_once 'config.php';

// Get timezone from request
$timezone = isset($_GET['timezone']) ? $_GET['timezone'] : 'UTC';

// Validate timezone
$validTimezones = DateTimeZone::listIdentifiers();
if (!in_array($timezone, $validTimezones)) {
    $timezone = 'UTC'; // Default to UTC if invalid
}

// Create date object with specified timezone
$date = new DateTime();
$date->setTimezone(new DateTimeZone($timezone));

// Format date and time
echo $date->format('F j, Y - g:i A');