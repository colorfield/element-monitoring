<?php

/**
 * Copy this file to settings.php, in your own environment,
 * and adapt the values to your needs.
 */

// Database credentials to allow PDO access to the
// Drupal 8 configuration table.
// They are copied here but could be extracted from the Drupal settings.php.
// PDO access has been chosen so it does not rely on the Drupal
// database bootstrap.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'my_db');

// Drupal 8 configuration ID's to monitored from the cache_config table
// and amount of items expected.
define('CONFIGURATION_IDS', [
  'my_configuration_id' => 42,
]);

// XPath matches definitions.
define('XPATH_MATCHES',
  [
    [
      // Languages to test on.
      'languages' => ['fr', 'nl', 'en',],
      // Urls to test on without the trailing slash.
      'urls' => ['https://example.com',],
      // Element ids that must be on the DOM.
      'element_ids' => ['my-id',],
    ],
  ]
);

// Emails that will receive the monitoring alerts.
define('MONITOR_EMAILS', [
  'john@doe.com',
  'jane@doe.com',
]);
