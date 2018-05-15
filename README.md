# Element monitoring

Simple monitoring for elements depending on the configuration of a Drupal 8 website.
Checks the database and the markup.

## Use case

During caching invalidation, some unpredictable situations (e.g. a database deadlock),
can cause elements to disappear.

Such cases are depending on the environment and the caching configuration.

This monitoring provides
- a chance to get notified early, by mail
- a context that can be used in the logs (db, website, ...) for debugging.

## Getting started

### PHP based monitoring

Clone this repo on the vhost that needs to be monitored, outside of the docroot.

1. Copy the _example.settings.php_ into _settings.php_ 
2. Adapt the settings to your needs.
3. Configure a cron job that calls the php monitor.

Example of cron job configuration, every 5 minutes:

```
0,5,10,15,20,25,30,35,40,45,50,55 * * * * /usr/bin/php7.1 /home/my_vhost/monitoring/php/ElementMonitoring.php
```

Additionally, when some conditions are met, the cron job can call
a bash script that wraps the php monitor, so it can run `drush cr` 
to execute another caching invalidation.

### Puppeteer based monitoring

Headless browser, to be launched on another environment,
that checks the presence of elements on the markup.

_Under development._
