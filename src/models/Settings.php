<?php
// src/models/Settings.php

class Settings {
    private string $lowstock_threshold;
    private string $header;
    private string $footer;
    private string $default_lang;
    private string $from_email;
    private string $app_password;
    private string $date_format;
    public string $time_zone;

    public function __construct(
        string $lowstock_threshold,
        string $header,
        string $footer,
        string $default_lang,
        string $from_email,
        string $app_password,
        string $date_format = 'Y-m-d',
        string $time_zone = 'UTC'
    ) {
        $this->lowstock_threshold = $lowstock_threshold;
        $this->header = $header;
        $this->footer = $footer;
        $this->default_lang = $default_lang;
        $this->from_email = $from_email;
        $this->app_password = $app_password;
        $this->date_format = $date_format;
        $this->time_zone = $time_zone;
    }

    // Low stock threshold
    public function getLowstockThreshold(): string {
        return $this->lowstock_threshold;
    }
    public function setLowstockThreshold(string $value): void {
        $this->lowstock_threshold = $value;
    }

    // Header
    public function getHeader(): string {
        return $this->header;
    }
    public function setHeader(string $value): void {
        $this->header = $value;
    }

    // Footer
    public function getFooter(): string {
        return $this->footer;
    }
    public function setFooter(string $value): void {
        $this->footer = $value;
    }

    // Default language
    public function getDefaultLang(): string {
        return $this->default_lang;
    }
    public function setDefaultLang(string $value): void {
        $this->default_lang = $value;
    }

    // From email
    public function getFromEmail(): string {
        return $this->from_email;
    }
    public function setFromEmail(string $value): void {
        $this->from_email = $value;
    }

    // App password
    public function getAppPassword(): string {
        return $this->app_password;
    }
    public function setAppPassword(string $value): void {
        $this->app_password = $value;
    }

    public function getDateFormat(): string {
        return $this->date_format;
    }
    public function setDateFormat(string $value): void {
        $this->date_format = $value;
    }

    // Time zone
    public function getTimeZone(): string {
        return $this->time_zone;
    }
    public function setTimeZone(string $value): void {
        $this->time_zone = $value;
    }
}
