<?php
// config/timezone.php

function setApplicationTimezone($conn) {
    require_once __DIR__ . '/../repositories/SettingsRepository.php';
    
    $settingsRepo = new SettingsRepository($conn);
    $settings = $settingsRepo->getSettings();
    $timezone = $settings->time_zone ?? 'UTC';

    if (in_array($timezone, timezone_identifiers_list())) {
        date_default_timezone_set($timezone);
    } else {
        date_default_timezone_set('UTC');
    }
}