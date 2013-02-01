<?php
/**
 * This example sets a campaign to be enhanced.
 *
 * Tags: CampaignService.mutate
 * Restriction: adwords-only
 *
 * Copyright 2013, Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package    GoogleApiAdsAdWords
 * @subpackage v201209
 * @category   WebServices
 * @copyright  2013, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License,
 *             Version 2.0
 * @author     Paul Matthews <pmatthews@google.com>
 */

// Include the initialization file
require_once dirname(dirname(__FILE__)) . "/init.php";

// Enter parameters required by the code example.
$campaignId = "INSERT_CAMPAIGN_ID_HERE";

/**
 * Runs the example.
 * @param AdWordsUser $user the user to run the example with
 * @param string $campaignId the id of the campaign to modify
 */
function SetCampaignEnhancedExample(AdWordsUser $user, $campaignId) {
  // Get the CampaignCriterionService, also loads classes
  $campaignService = $user->GetService("CampaignService", ADWORDS_VERSION);

  // Campaign to be updated with the enhanced value.
  // Note: After Setting the enhanced value to true,
  //     setting it back to false will generate an ApiError.

  // Create the forwardCompatibilityMap.
  $enhancedKey = "Campaign.enhanced";
  $mapEntries = MapUtils::GetMapEntries(
      array($enhancedKey => "true"),
      "String_StringMapEntry");

  // Create the modification of the campaign.
  $campaign = new Campaign();
  $campaign->id = $campaignId;
  $campaign->forwardCompatibilityMap = $mapEntries;

  // Create operation.
  $operation = new CampaignOperation();
  $operation->operator = "SET";
  $operation->operand = $campaign;

  // Change campaign.
  $result = $campaignService->mutate(array($operation));

  if (count($result->value)) {
    foreach ($result->value as $campaign) {
      // Get a native map from the ForwawrdCombatilityMaps.
      $map = MapUtils::GetMap(
          $campaign->forwardCompatibilityMap);

      $enhanced = "false";
      if (isset($map[$enhancedKey])) {
        $enhanced = $map[$enhancedKey];
      }

      printf("Campaign with ID %d has been set enhanced to '%s'.\n",
          $campaign->id, $enhanced);
    }

    return true;
  }
  print "No campaign was changed.";
}

// Don't run the example if the file is being included.
if (__FILE__ != realpath($_SERVER["PHP_SELF"])) {
  return;
}

try {
  // Get AdWordsUser from credentials in "../auth.ini"
  // relative to the AdWordsUser.php file's directory.
  $user = new AdWordsUser();

  // Log every SOAP XML request and response.
  $user->LogAll();

  // Run the example.
  SetCampaignEnhancedExample($user, $campaignId);
} catch (Exception $e) {
  printf("An error has occurred: %s\n", $e->getMessage());
}
