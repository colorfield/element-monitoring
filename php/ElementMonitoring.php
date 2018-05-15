<?php

/**
 * Class ElementMonitoring
 *
 * Simple monitoring for elements depending on the configuration of a Drupal 8 website.
 * Checks the cache_config table and the markup via curl or xpath.
 */
class ElementMonitoring {

  private $dbHost = DB_HOST;
  private $dbName = DB_NAME;
  private $dbUser = DB_USER;
  private $dbPass = DB_PASS;

  private $xPathMatches = [];
  private $configurationIds = [];
  private $monitorEmails = [];

  /**
   * @var PDO instance.
   */
  protected $pdo;

  /**
   * ElementMonitoring constructor.
   */
  function __construct() {
    $this->setMonitorEmails();
    // Try with a curl simple match.
    $this->monitorCurlMatches();
    // Start by a Xpath check.
    //$this->monitorXpathMatches();
    // Continue with database configuration instances check.
    $this->monitorDatabaseConfigurationInstances();
  }

  /**
   * Copies the db credentials from the settings file.
   */
  private function setDatabaseCredentials() {
    $this->dbHost = DB_HOST;
    $this->dbUser = DB_USER;
    $this->dbPass = DB_PASS;
    $this->dbName = DB_NAME;
  }

  /**
   * Copies the configuration ids to monitor from the settings file.
   */
  private function setConfigurationIds() {
    $this->configurationIds = CONFIGURATION_IDS;
  }

  /**
   * Copies the configuration ids to monitor from the settings file.
   */
  private function setMonitorEmails() {
    $this->monitorEmails = MONITOR_EMAILS;
  }

  /**
   * Copies the configuration of the element ids to monitor from the settings file.
   */
  private function setXPathMatches() {
    $this->xPathMatches = XPATH_MATCHES;
  }

  /**
   * Checks if the expected matches are met, with Curl.
   *
   * @todo to be replaced by monitorXpathMatches()
   */
  private function monitorCurlMatches() {
    $this->setXPathMatches();
    if(!empty($this->xPathMatches) && !empty($this->monitorEmails)) {
      $curl = curl_init();
      $debug = [];
      $result = TRUE;
      foreach($this->xPathMatches as $xPathMatch) {
        foreach($xPathMatch['languages'] as $language) {
          $url = $xPathMatch['urls'][0] . '/' . $language;
          print("Checking language " . $language . " on url " . $url . "\n");
          curl_setopt($curl, CURLOPT_URL, $url);
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($curl, CURLOPT_HEADER, false);
          $document = curl_exec($curl);
          $matchFound = strpos($document, $xPathMatch['element_ids'][0]);

          if (!$matchFound) {
            $debug[$language] = "Match for " . $xPathMatch['element_ids'][0] . " in language " . $language . " NOT found";
            $result = FALSE;
            // @todo send alert.
          } else {
            $debug[$language] = "Match for " . $xPathMatch['element_ids'][0] . " in language " . $language . " found";
          }
        }
      }
      $debug = implode("\n", $debug);
      print($debug);
      // @todo move this side effect in the client
      if(!$result) {
        $this->sendMonitoringAlert($debug);
      }
      curl_close($curl);
    }else {
      print("Define at least one XPath match and one email that receives monitoring results into settings.php\n");
    }
  }

  /**
   * @todo check if the expected XPath matches are met.
   *
   * @return bool
   *   The result of the monitoring.
   */
  private function monitorXpathMatches() {
    $this->setXPathMatches();
    // @todo needs work.
    if(!empty($this->xPathMatches) && !empty($this->monitorEmails)) {
      foreach($this->xPathMatches as $xPathMatch) {
        $dom = new DOMDocument;
        // @todo iterate on urls
        //foreach($xPathMatch['languages'] as $language) {
        $language = 'fr';
          $dom->loadHTMLFile($xPathMatch['urls'][0] . '/' . $language);
          //print("Dom element = " . $dom->getElementById($xPathMatch['element_ids'][0]));
          $xpath = new DOMXPath($dom);
          // @todo iterate on element_ids.
          $result = $xpath->evaluate("//div[contains(@id, '".$xPathMatch['element_ids'][0]."')]");
          print_r($result);
          print("XPath expression = //div[contains(@id, '".$xPathMatch['element_ids'][0]."')]");
          print("Result = " . $result->length . " for id " . $xPathMatch['element_ids'][0] . "\n");
        //}
      }
    }else {
      print("Define at least one XPath match and one email that receives monitoring results into settings.php\n");
    }
  }

  /**
   * Returns the amount of configuration instances.
   *
   * @param string $cid
   *   Configuration id.
   *
   * @return int
   *   Amount of config rows found.
   */
  private function getConfigurationInstances($cid) {
    $result = 0;
    try {
      $statement = $this->pdo->prepare("SELECT cid FROM cache_config WHERE cid LIKE :cid");
      $statement->execute([':cid' => '%' . $cid . '%',]);
      $result = $statement->rowCount();
      // Closing the connection.
      $statement->closeCursor(); // @todo check if both are necessary.
      $statement = null;
      $this->pdo = null;
    } catch (Exception $e) {
      die($e->getMessage());
    }
    return $result;
  }

  /**
   * Checks if the configuration instances are complying with the expected amount.
   *
   * If not send a mail to the monitoring emails.
   *
   * @return bool
   *   The result of the monitoring.
   */
  private function monitorDatabaseConfigurationInstances() {
    // @todo check if the monitoring was set properly.
    $this->setConfigurationIds();
    if(!empty($this->configurationIds) && !empty($this->monitorEmails)) {
      $this->setDatabaseCredentials();
      try{
        $this->pdo = new PDO('mysql:host='. $this->dbHost .';dbname='. $this->dbName .';charset=utf8', $this->dbUser, $this->dbPass);
        foreach ($this->configurationIds as $cid => $expectedAmount) {
          // @todo collect all configuration ids and send a single mail.
          $amountInstances = $this->getConfigurationInstances($cid);
          $debug = "\nDatabase Result found for " . $cid . ": " . $amountInstances;
          $debug .= ", amount expected: " . $expectedAmount . "\n";
          print($debug);
          if((int) $expectedAmount !== (int) $amountInstances) {
            $this->sendMonitoringAlert($debug);
          }
        }
      } catch (Exception $e) {
        die($e->getMessage());
      }
    }else {
      print("Define at least one configuration ID and one email that receives monitoring results into settings.php\n");
    }
  }

  /**
   * Sends the monitoring alert to the defined emails.
   */
  private  function sendMonitoringAlert($debug) {
    $subject = 'Monitoring alert';
    $path = 'Path = ' . realpath(dirname(__FILE__));
    $message = "\n" . $path . "\n" . $debug;
    foreach ($this->monitorEmails as $emailTo) {
      print("\nSend monitoring alert to " . $emailTo . "\n");
      mail($emailTo, $subject, $message);
    }
  }

}

if (file_exists(__DIR__ . '/settings.php')) {
  include(__DIR__ . '/settings.php');
  $monitoring = new ElementMonitoring();
}else {
  print("Copy the example.settings.php file into settings.php\n");
}
