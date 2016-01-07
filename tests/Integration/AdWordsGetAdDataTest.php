<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\AOM\tests\Integration;

use Piwik;
use Piwik\Db;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Plugins\AOM\Platforms\AdWords\AdWords;
use Piwik\Tests\Framework\Fixture;

/**
 * @group AOM
 * @group AdWordsGetAdDataTest
 * @group AOM_Integration
 * @group AOM_Merging
 * @group Plugins
 */
class AdWordsGetAdDataTest extends IntegrationTestCase
{
    /**
     * @var Fixture
     */
    public static $fixture = null; // initialized below class definition


    /** @var  Adwords */
    private  $adwords;

    public function setUp()
    {
        parent::setUp();

        // TODO: Replace StaticContainer with DI
        $logger = Piwik\Container\StaticContainer::get('Psr\Log\LoggerInterface');
        $this->adwords = new AdWords($logger);


        Db::query(
            'INSERT INTO ' . AdWords::getDataTableNameStatic()
            . ' (id_account_internal, idsite, date, account, campaign_id, campaign, ad_group_id, ad_group, '
            . 'keyword_id, keyword_placement, criteria_type, network, impressions, clicks, cost, '
            . 'conversions, ts_created) '
            . 'VALUE (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                '1000',
                '1',
                date('Y-m-d'),
                'Test Account',
                '1005',
                'Campaign 1',
                '123',
                'adGroup 1',
                '55555',
                'keywords',
                'keyword',
                'g',
                170,
                12,
                2.57,
                1,
            ]
        );

        Db::query(
            'INSERT INTO ' . AdWords::getDataTableNameStatic()
            . ' (id_account_internal, idsite, date, account, campaign_id, campaign, ad_group_id, ad_group, '
            . 'keyword_id, keyword_placement, criteria_type, network, impressions, clicks, cost, '
            . 'conversions, ts_created) '
            . 'VALUE (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                '1000',
                '1',
                '2015-05-06',
                'Test Account',
                '1006',
                'Campaign Old',
                '123',
                'adGroup 1',
                '66',
                'keywords',
                'keyword',
                'g',
                170,
                12,
                7.8,
                1,
            ]
        );


        Db::query(
            'INSERT INTO ' . AdWords::getDataTableNameStatic()
            . ' (id_account_internal, idsite, date, account, campaign_id, campaign, ad_group_id, ad_group, '
            . 'keyword_id, keyword_placement, criteria_type, network, impressions, clicks, cost, '
            . 'conversions, ts_created) '
            . 'VALUE (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                '1000',
                '1',
                date('Y-m-d'),
                'Test Account',
                '1005',
                'Campaign 2',
                '126',
                'adGroup 2',
                '55555',
                'www.test.de',
                'placement',
                'd',
                170,
                12,
                8.90,
                1,
            ]
        );

    }

    public function tearDown()
    {
        // clean up your test here if needed

        parent::tearDown();
    }

    public function testExactMatch()
    {
        $data = $this->adwords->getAdDataFromAdParams(1, ['network' => 'g', 'adGroupId' => 123, 'campaignId' => 1005, 'targetId' => 'kwd-55555']);

        $this->assertEquals('Campaign 1', $data['campaign']);
        $this->assertEquals(2.57, $data['cost']);
    }

    public function testExactMatchDisplay()
    {
        $data = $this->adwords->getAdDataFromAdParams(1, ['network' => 'd', 'adGroupId' => 126, 'campaignId' => 1005, 'placement' => 'www.test.de']);

        $this->assertEquals('Campaign 2', $data['campaign']);
        $this->assertEquals(8.9, $data['cost']);
    }

    public function testAlternativeMatch()
    {
        $data = $this->adwords->getAdDataFromAdParams(1, ['network' => 'g', 'adGroupId' => 123, 'campaignId' => 1006, 'targetId' => 'kwd-66']);

        $this->assertEquals('Campaign Old', $data['campaign']);
        $this->assertArrayNotHasKey('cost', $data);
    }
}

AdWordsGetAdDataTest::$fixture = new Piwik\Plugins\AOM\tests\Fixtures\BasicFixtures();
