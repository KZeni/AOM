<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 * @author André Kolell <andre.kolell@gmail.com>
 */
namespace Piwik\Plugins\AOM\Platforms\AdWords;

use Piwik\Common;
use Piwik\Db;
use Piwik\Plugins\AOM\AOM;
use Piwik\Plugins\AOM\Platforms\AbstractMerger;
use Piwik\Plugins\AOM\Platforms\MergerInterface;
use Piwik\Plugins\AOM\Platforms\MergerPlatformDataOfVisit;

class Merger extends AbstractMerger implements MergerInterface
{
    public function getPlatformDataOfVisit($idsite, $date, array $aomAdParams)
    {
        $mergerPlatformDataOfVisit = new MergerPlatformDataOfVisit(AOM::PLATFORM_AD_WORDS);

        // To get more platform data, we need at least the gclid
        if (!array_key_exists('gclid', $aomAdParams) || !$aomAdParams['gclid']) {
            $this->logger->warning(
                'Could not find gclid in ad params although platform has been identified as AdWords.'
            );
            return $mergerPlatformDataOfVisit;
        }

        // Find Google click based on gclid
        // (a match will only be possible if AdWords is already imported but tracking event processing is delayed)
        $gclid = $aomAdParams['gclid'];
        $click = $this->findGoogleClickBasedOnGclid($idsite, $date, $gclid);

        // When there is not click, we'll not be able to retrieve any more information
        if (!$click) {
            return $mergerPlatformDataOfVisit->setPlatformData(['gclid' => $gclid]);
        }

        $mergerPlatformDataOfVisit->setPlatformKey(
            $this->getPlatformKey($click['network'], $click['campaignId'], $click['adGroupId'], $click['keywordId'])
        );

        // Get the ID of the exactly matching platform row
        $platformRowId = $this->getExactMatchPlatformRowId(
            $idsite, $date, $click['network'], $click['campaignId'], $click['adGroupId'], $click['keywordId']
        );
        if (!$platformRowId) {

            // For AdWords we do not need to search for a historical match, as all relevant information is already
            // part of the Google click record.
            return $mergerPlatformDataOfVisit->setPlatformData($click);
        }

        // Exact match
        return $mergerPlatformDataOfVisit->setPlatformData($click)->setPlatformRowId($platformRowId);
    }

    /**
     * Tries to find and return a Google click with the given gclid on the day of the visit ($date) or one day before.
     *
     * @param int $idsite
     * @param string $date
     * @param string $gclid
     * @return array
     */
    private function findGoogleClickBasedOnGclid($idsite, $date, $gclid)
    {
        // TODO: Do we have an index for this query?
        $result = Db::fetchRow(
            'SELECT account, campaign_id AS campaignId, campaign, ad_group_id AS adGroupId, ad_group AS adGroup, '
                . ' keyword_id AS keywordId, keyword_placement AS keywordPlacement, match_type AS matchType, '
                . ' ad_id AS adId, network, device'
                . ' FROM ' . Common::prefixTable('aom_adwords_gclid')
                . ' WHERE date BETWEEN DATE(?) - INTERVAL 1 DAY AND DATE(?) AND idsite = ? AND gclid = ?',
            [$date, $date, $idsite, $gclid,]
        );

        // When there is not click, we'll not be able to retrieve any more information
        if ($result) {
            $this->logger->debug('Found Google click for gclid "' . $gclid . '".');
        } else {
            $this->logger->debug('Could not find Google click for gclid "' . $gclid . '" (perhaps not yet imported).');
        }

        return $result;
    }

    /**
     * Returns the ID of the platform row when a match of Google click and platform data including cost is found.
     * False otherwise.
     *
     * TODO: Imported data should also create platform_key which would make querying easier.
     *
     * @param int $idsite
     * @param string $date
     * @param string $network
     * @param string $campaignId
     * @param string $adGroupId
     * @param string|null $keywordId
     * @return int|bool
     */
    private function getExactMatchPlatformRowId($idsite, $date, $network, $campaignId, $adGroupId, $keywordId = null)
    {
        // Display network cost are on ad group instead of keyword level
        $query = 'SELECT id FROM ' . Common::prefixTable('aom_adwords')
            . ' WHERE idsite = ? AND date = ? AND network = ? AND campaign_id = ? AND ad_group_id = ? ';

        $params = [$idsite, $date, $network, $campaignId, $adGroupId,];

        if ('d' !== $network) {
            $query .= ' AND keyword_id = ?';
            $params[] = $keywordId;
        }

        $result = Db::fetchOne($query, $params);

        if ($result) {
            $this->logger->debug(
                'Found exact match platform row ID ' . $result . ' in imported AdWords data for Google visit.'
            );
        } else {
            $this->logger->debug('Could not find exact match in imported AdWords data for Google click.');
        }

        return $result;
    }

    /**
     * @param string $network
     * @param string $campaignId
     * @param string $adGroupId
     * @param string|null $keywordId
     * @return string
     */
    private function getPlatformKey($network, $campaignId, $adGroupId, $keywordId = null)
    {
        $key = $network . '-' . $campaignId . '-' . $adGroupId;

        if ('d' !== $network) {
            $key .= '-' . $keywordId;
        }

        return $key;
    }
}
