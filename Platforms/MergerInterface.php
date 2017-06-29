<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 * @author André Kolell <andre.kolell@gmail.com>
 */
namespace Piwik\Plugins\AOM\Platforms;

interface MergerInterface
{
    /**
     * Tries to match a visit with imported platform data.
     *
     * @param int $idsite
     * @param string $date
     * @param array $aomAdParams
     * @return MergerPlatformDataOfVisit
     */
    public function getPlatformDataOfVisit($idsite, $date, array $aomAdParams);

    /**
     * Allocates the cost of the platform row to all matching visits.
     *
     * If there are no matching visits, an artificial visit is being created. This visit won't have a piwik_idvisit.
     * All cost will be allocated to this visit. If a real visit occurs later, all cost is removed from the artificial
     * visit and assigned to the real visit, but the artificial visit will stay updated in the database.
     *
     * @param string $platformName
     * @param int $platformRowId
     * @param string $platformKey
     * @param array $platformData
     * @return mixed
     */
    public function allocateCostOfPlatformRow($platformName, $platformRowId, $platformKey, array $platformData);
}
