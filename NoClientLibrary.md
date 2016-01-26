If you plan to make requests to the AdWords API calls with PHP we recommend you to use the AdWords API PHP client library.  If you don't wish to use the client library and would rather implement your own solution this document explains how to get started.  The example below use a [Google Account](https://www.google.com/accounts/NewAccount), [AdWords API v201109](http://code.google.com/apis/adwords/docs/), and [PHP 5.2.11](http://php.net/releases/5_2_11.php) with the extensions [SoapClient](http://us3.php.net/manual/en/book.soap.php), [OpenSSL](http://php.net/manual/en/book.openssl.php) and [cURL](http://php.net/manual/en/book.curl.php).  The API requests are made to the [AdWords API Sandbox](http://code.google.com/apis/adwords/docs/developer/adwords_api_sandbox.html).



# Authentication #

Authentication is handled using the [ClientLogin API](http://code.google.com/apis/accounts/docs/AuthForInstalledApps.html).  It is a simple HTTP API that takes the email and password of a Google account and returns an authentication token that must be included with all requests to the AdWords API.  The required parameters are:

| accountType | Should always be "GOOGLE" |
|:------------|:--------------------------|
| Email       | The email address of the AdWords Google account |
| Passwd      | The password of the account |
| service     | Should always be "adwords" |
| source      | Should be a unique string identifying your application |

A successful response will include a series of tokens, one per line.  Only the "Auth" token is required by the AdWords API.

```
SID=DQAAAGgA...7Zg8CTN 
LSID=DQAAAGsA...lk8BBbG 
Auth=DQAAAGgA...dk3fA5N
```

The example below demonstrates how to generate an auth token for a given email address and password. You should request an auth token when you application loads and use it for all future requests (they are valid for up to 2 weeks).

```
<?php
error_reporting(E_STRICT | E_ALL);

// Provide AdWords login information.
$email = 'INSERT_LOGIN_EMAIL_HERE';
$password = 'INSERT_PASSWORD_HERE';

$params = array(
    'Email' => $email,
    'Passwd' => $password,
    'accountType' => 'GOOGLE',
    'service' => 'adwords',
    'source' => 'AdWords API PHP Code Example');

$url = 'https://www.google.com/accounts/ClientLogin';

// Make request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$response = curl_exec($ch);
curl_close($ch);

// Parse response.
$lines = explode("\n", $response);
foreach ($lines as $line) {
  $parts = explode('=', $line, 2);
  if ($parts[0] == 'Auth') {
    $authToken = $parts[1];
  }
  if ($parts[0] == 'Error') {
    $error = $parts[1];
  }
}

// Display results.
if (isset($authToken)) {
  print 'Auth token: ' . $authToken . "\n";
} else if (isset($error)) {
  print 'Error: ' . $error . "\n";
}
```

# SOAP Services #

## Getting the Service ##

The easiest way to create a SOAP client for a given [AdWords service](http://code.google.com/apis/adwords/docs/) is to use the associated WSDL.  The SoapClient library will automatically download the WSDL, parse it, and use the settings specified within.  Additional settings are required for the SOAP client to work with the AdWords API though, which are passed in as an array of options.  Specifically the feature "SOAP\_SINGLE\_ELEMENT\_ARRAYS" must be enabled to ensure arrays are marshalled correctly, and the encoding must be set to "utf-8".  At this time it is also best to set the namespaces used by the WSDL, since they will be required later when creating certain objects.

This example uses the CampaignService in the Sandbox environment.  Please note that the namespace remains the same in both the Sandbox and Production environments.

```
// Set SOAP and XML settings. To send requests to production environment,
// replace "adwords-sandbox.google.com" with "adwords.google.com" in the wsdl
// URL. The namespace will always be "adwords.google.com", even in the
// sandbox.
$wsdl = 'https://adwords-sandbox.google.com/api/adwords/cm/v201109/CampaignService?wsdl';
$namespace = 'https://adwords.google.com/api/adwords/cm/v201109';
$options = array(
    'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
    'encoding' => 'utf-8');

// Get CampaignService.
$campaignService = new SoapClient($wsdl, $options);
```

## Setting the Headers ##

The API uses one SOAP header, called "RequestHeader", to hold the various header fields of the request.  This header must be created using the SoapHeader class, using the namespace of the service and the [required header fields](http://code.google.com/apis/adwords/docs/headers.html).  In this example the field "authToken" is populated using the AuthToken object created earlier.

```
// Set headers.
$headers = new SoapHeader($namespace, 'RequestHeader', array(
    'authToken' => $authToken,
    'clientCustomerId' => $clientCustomerId,
    'userAgent' => $userAgent,
    'developerToken' => $developerToken));
$campaignService->__setSoapHeaders($headers);
```

## Creating the Arguments ##

The API request arguments can be created in a variety of ways, but the simplest involves using a mixture of associative arrays and SoapVar objects.  An associative array can be used for simple types in the API, but more complex types that require an xsi:type attribute or that are part of a different namespace must be created with the SoapVar class.

In this example the biddingStrategy field of the campaign must be of the type "ManualCPC".  This type has no fields, so the data and encoding parameters are left empty.

```
// Create campaign.
$campaign = array(
    'name' => 'Interplanetary Cruise #' . time(),
    'status' => 'PAUSED',
    'biddingStrategy' => new SoapVar(NULL, NULL, 'ManualCPC', $namespace),
    'budget' => array(
        'period' => 'DAILY',
        'amount' => array('microAmount' => 50000000),
        'deliveryMethod' => 'STANDARD'));

// Create operations.
$operation = array('operator' => 'ADD', 'operand' => $campaign);
$operations = array($operation);
```

## Making the Request ##

If the SOAP client was created from the WSDL then a method will be available that you can use the make the request.  If an error occurs during the request a SoapFault will be thrown.

```
try {
  // Add campaign.
  $result = $campaignService->mutate($operations);
} catch (SoapFault $e) {
  print_r($e);
  exit(1);
}
```

## Using the Results ##

The result of a method call will be a wrapper object with a single filed called "rval". This field will contain the actual results, as defined by the API.  The results are StdClass objects with the appropriate fields.

```
// Display campaigns.
foreach ($result->rval->value as $campaign) {
  print 'Campaign with name "' . $campaign->name . '" and id "'
      . $campaign->id . "\" was added.\n";
}
```

## Complete Example ##

```
<?php
error_reporting(E_STRICT | E_ALL);

// Provide header information.
$authToken = 'INSERT_AUTH_TOKEN_HERE';
$clientCustomerId = 'INSERT_CLIENT_CUSTOMER_ID_HERE';
$userAgent = 'INSERT_COMPANY_NAME: AdWords API PHP Code Example';
$developerToken = 'INSERT_DEVELOPER_TOKEN_HERE';

// Set SOAP and XML settings. To send requests to production environment,
// replace "adwords-sandbox.google.com" with "adwords.google.com" in the wsdl
// URL. The namespace will always be "adwords.google.com", even in the
// sandbox.
$wsdl = 'https://adwords-sandbox.google.com/api/adwords/cm/v201109/CampaignService?wsdl';
$namespace = 'https://adwords.google.com/api/adwords/cm/v201109';
$options = array(
    'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
    'encoding' => 'utf-8');

// Get CampaignService.
$campaignService = new SoapClient($wsdl, $options);

// Set headers.
$headers = new SoapHeader($namespace, 'RequestHeader', array(
    'authToken' => $authToken,
    'clientCustomerId' => $clientCustomerId,
    'userAgent' => $userAgent,
    'developerToken' => $developerToken));
$campaignService->__setSoapHeaders($headers);

// Create campaign.
$campaign = array(
    'name' => 'Interplanetary Cruise #' . time(),
    'status' => 'PAUSED',
    'biddingStrategy' => new SoapVar(NULL, NULL, 'ManualCPC', $namespace),
    'budget' => array(
        'period' => 'DAILY',
        'amount' => array('microAmount' => 50000000),
        'deliveryMethod' => 'STANDARD'));

// Create operations.
$operation = array('operator' => 'ADD', 'operand' => $campaign);
$operations = array($operation);

try {
  // Add campaign.
  $result = $campaignService->mutate($operations);
} catch (SoapFault $e) {
  print_r($e);
  exit(1);
}

// Display results.
foreach ($result->rval->value as $campaign) {
  print 'Campaign with name "' . $campaign->name . '" and id "'
      . $campaign->id . "\" was added.\n";
}
```

# Report Downloads #

Reports are downloaded using a special HTTP servlet (instead of a SOAP service). An example of how to do this using cURL is below.

## Report Definition Service ##
In this example we define the report using XML.

```
<?php
// Provide header information.
$authToken = 'INSERT_AUTH_TOKEN_HERE';
$clientCustomerId = 'INSERT_CLIENT_CUSTOMER_ID_HERE';
$developerToken = 'INSERT_DEVELOPER_TOKEN_HERE';

// Create report definition XML.
$reportDefinition = <<<EOT
<reportDefinition xmlns="https://adwords.google.com/api/adwords/cm/v201109">
  <selector>
    <fields>CampaignId</fields>
    <fields>Id</fields>
    <fields>Impressions</fields>
    <fields>Clicks</fields>
    <fields>Cost</fields>
    <predicates>
      <field>Status</field>
      <operator>IN</operator>
      <values>ENABLED</values>
      <values>PAUSED</values>
    </predicates>
  </selector>
  <reportName>Custom Adgroup Performance Report</reportName>
  <reportType>ADGROUP_PERFORMANCE_REPORT</reportType>
  <dateRangeType>LAST_7_DAYS</dateRangeType>
  <downloadFormat>CSV</downloadFormat>
</reportDefinition>
EOT;

// Create parameters.
$params = array('__rdxml' => $reportDefinition);

// Create headers.
$headers = array();
$headers[]= 'Authorization: GoogleLogin auth=' . $authToken;
$headers[]= 'clientCustomerId: ' . $clientCustomerId;
$headers[]= 'developerToken: ' . $developerToken;

$downloadPath = 'report.csv';
$url = 'https://adwords.google.com/api/adwords/reportdownload/v201109';

$file = fopen($downloadPath, 'w');
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_FILE, $file);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
fclose($file);

if ($code == 200) {
  printf("Report downloaded to: %s\n", $downloadPath);
} else {
  $file = fopen($downloadPath, 'r');
  $error = fread($file, 1024);
  fclose($file);
  printf("Error: %s\n", $error);
}
```

## AWQL Service ##
In this example we construct the same example as shown above using [AWQL](https://developers.google.com/adwords/api/docs/guides/awql). The key differences here are, firstly the query is defined using SQL like syntax. Secondly, the query is bound to the 'rdquery' parameter and finally, a report format is bound to the 'fmt' parameter.

```
<?php
// Provide header information.
$apiVersion = 'v201209';
$authToken = 'INSERT_AUTH_TOKEN_HERE';
$clientCustomerId = 'INSERT_CLIENT_CUSTOMER_ID_HERE';
$developerToken = 'INSERT_DEVELOPER_TOKEN_HERE';

// Create report definition XML.
$reportDefinition = 'SELECT CampaignId, Id, Impressions, Clicks, Cost ' .
  '  FROM ADGROUP_PERFORMANCE_REPORT ' .
  'WHERE Status IN ["ENABLED","PAUSED"]' .
  '  DURING LAST_7_DAYS';

$format = 'CSV';

// Create parameters.
$params = array(
  '__rdquery' => $reportDefinition,
  '__fmt' => $format,
);

// Create headers.
$headers = array();
$headers[]= 'Authorization: GoogleLogin auth=' . $authToken;
$headers[]= 'clientCustomerId: ' . $clientCustomerId;
$headers[]= 'developerToken: ' . $developerToken;

$downloadPath = 'report.csv';
$url = "https://adwords.google.com/api/adwords/reportdownload/$apiVersion";

$file = fopen($downloadPath, 'w');
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_FILE, $file);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
fclose($file);

if ($code == 200) {
  printf("Report downloaded to: %s\n", $downloadPath);
} else {
  $file = fopen($downloadPath, 'r');
  $error = fread($file, 1024);
  fclose($file);
  printf("Error: %s\n", $error);
}
```