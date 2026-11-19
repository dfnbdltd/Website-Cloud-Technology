<?php

namespace Tests\Unit;

use App\Services\ResellerClubClient;
use App\Support\DomainActivity;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ResellerClub "actions" -> Latest Activity.
 *
 * The fixtures below are the real field names and timestamp format returned by
 * /api/actions/search-current.json and /api/actions/search-archived.json
 * (tested in Postman against a live reseller account).
 */
class DomainActivityTest extends TestCase
{
    private const ORDER_ID = 127547539;

    private function currentResponse()
    {
        return [
            'recsonpage' => '2',
            'recsindb' => '2',
            '1' => [
                'eaqid' => '9001',
                'entityid' => (string) self::ORDER_ID,
                'entitytypeid' => '1',
                'actiontype' => 'ModContact',
                'actiontypedesc' => 'Modification of Contact Details of anannainnbd.com',
                'actionstatus' => 'Success',
                'actionstatusdesc' => 'Modified registrant organization consent for same contacts',
                'actionadded' => '1794308804',
                'actionstarted' => '1794308804',
                'actioncompleted' => '1794308804',
                'priority' => '0',
            ],
            '2' => [
                'eaqid' => '9002',
                'entityid' => (string) self::ORDER_ID,
                'entitytypeid' => '1',
                'actiontype' => 'ModContact',
                'actiontypedesc' => 'Modification of Contact Details of anannainnbd.com',
                'actionstatus' => 'Success',
                'actionstatusdesc' => 'Modified registrant organization consent for same contacts',
                'actionadded' => '1794308848',
                'actionstarted' => '1794308848',
                'actioncompleted' => '1794308848',
                'priority' => '0',
            ],
        ];
    }

    private function archivedResponse()
    {
        return [
            'recsonpage' => '1',
            'recsindb' => '1',
            '1' => [
                'eaqid' => '8001',
                'entityid' => (string) self::ORDER_ID,
                'entitytypeid' => '1',
                'actiontype' => 'RegistrantOrgConsentUpdation',
                'actiontypedesc' => 'Modification of registrant organization consent for anannainnbd.com',
                'actionstatus' => 'Success',
                'actionstatusdesc' => 'Modified registrant organization consent',
                'actionadded' => '1793859165',
                'actionstarted' => '1793859165',
                'actioncompleted' => '1793859165',
                'priority' => '0',
            ],
        ];
    }

    private function client(array $responses, $enabled = true)
    {
        config([
            'services.resellerclub.enabled' => $enabled,
            'services.resellerclub.auth_userid' => 'user-123',
            'services.resellerclub.api_key' => 'key-123',
            'services.resellerclub.base_url' => 'https://httpapi.com/api',
        ]);

        return new class($responses) extends ResellerClubClient {
            public $calls = [];
            private $responses;

            public function __construct($responses)
            {
                parent::__construct();
                $this->responses = $responses;
            }

            protected function request(
                $path,
                array $parameters = [],
                $method = 'GET',
                $allowScalar = false,
                $ipv4Only = false,
                $requireSuccessStatus = false
            ) {
                $this->calls[] = compact('path', 'parameters', 'method');
                $response = isset($this->responses[$path]) ? $this->responses[$path] : null;

                return is_callable($response) ? $response($parameters) : $response;
            }
        };
    }

    protected function setUp(): void
    {
        parent::setUp();
        ResellerClubClient::forgetDomainActions(self::ORDER_ID);
        ResellerClubClient::forgetDomainActions(self::ORDER_ID + 1);
    }

    public function testParsesTheRealResponseShape()
    {
        $parsed = DomainActivity::parseResponse($this->currentResponse());

        $this->assertSame(2, $parsed['total']);
        $this->assertCount(2, $parsed['actions']);

        $first = $parsed['actions'][0];
        $this->assertSame('9001', $first['id']);
        $this->assertSame('ModContact', $first['type']);
        $this->assertSame('Modification of Contact Details of anannainnbd.com', $first['title']);
        $this->assertSame('Modified registrant organization consent for same contacts', $first['description']);
        $this->assertSame('completed', $first['state']);
        $this->assertSame('Completed', $first['state_label']);
        $this->assertSame(1794308804, $first['completed_at']);
        $this->assertSame(1794308804, $first['at']);
        // 1794308804 is 2026-11-10 11:06:44 UTC, the time ResellerClub's panel shows.
        $this->assertSame('2026-11-10 11:06:44', gmdate('Y-m-d H:i:s', $first['at']));
    }

    public function testOnlyNumberedRecordsAreActions()
    {
        $parsed = DomainActivity::parseResponse($this->currentResponse());

        // recsonpage / recsindb are counters, never rows.
        foreach ($parsed['actions'] as $action) {
            $this->assertNotSame('', $action['id']);
        }
        $this->assertCount(2, $parsed['actions']);
    }

    public function testAnOrderWithNoActionsParsesToAnEmptyListNotAnError()
    {
        $parsed = DomainActivity::parseResponse(['recsonpage' => '0', 'recsindb' => '0']);

        $this->assertSame([], $parsed['actions']);
        $this->assertSame(0, $parsed['total']);
        $this->assertSame(['total' => null, 'actions' => []], DomainActivity::parseResponse([]));
    }

    public function testAnErrorResponseIsNotMistakenForNoActivity()
    {
        $this->assertNull(DomainActivity::parseResponse(['status' => 'ERROR', 'message' => 'Invalid credentials']));
        $this->assertNull(DomainActivity::parseResponse(null));
        $this->assertNull(DomainActivity::parseResponse('nope'));
    }

    public function testStatesFollowTheActionStatus()
    {
        $base = ['eaqid' => '1', 'actiontype' => 'ModNS', 'actiontypedesc' => 'Modification of Nameservers'];

        $failed = DomainActivity::normalize($base + [
            'actionstatus' => 'Failed',
            'actionstatusdesc' => 'Registry error',
            'actionadded' => '1794308804',
            'actioncompleted' => '1794308810',
        ]);
        $this->assertSame('failed', $failed['state']);
        $this->assertSame('Registry error', $failed['description']);

        $pending = DomainActivity::normalize($base + [
            'actionstatus' => 'Pending',
            'actionstatusdesc' => 'Waiting',
            'actionadded' => '1794308804',
            'actionstarted' => '0',
            'actioncompleted' => '0',
        ]);
        $this->assertSame('pending', $pending['state']);
        $this->assertNull($pending['completed_at']);
        $this->assertNull($pending['started_at']);
        $this->assertSame(1794308804, $pending['at']);
    }

    public function testMergeDropsRepeatsAndSortsNewestFirst()
    {
        $current = DomainActivity::parseResponse($this->currentResponse())['actions'];
        $archived = DomainActivity::parseResponse($this->archivedResponse())['actions'];

        // The boundary action can be returned by both endpoints.
        $merged = DomainActivity::merge($current, $archived, [$current[0]]);

        $this->assertSame(['9002', '9001', '8001'], array_column($merged, 'id'));
    }

    public function testClientReadsCurrentAndArchivedAndMergesThem()
    {
        $client = $this->client([
            '/actions/search-current.json' => $this->currentResponse(),
            '/actions/search-archived.json' => $this->archivedResponse(),
        ]);

        $actions = $client->domainActions(self::ORDER_ID);

        $this->assertSame(['9002', '9001', '8001'], array_column($actions, 'id'));
        $this->assertSame(
            ['/actions/search-current.json', '/actions/search-archived.json'],
            array_column($client->calls, 'path')
        );
        $this->assertSame(self::ORDER_ID, $client->calls[0]['parameters']['order-id']);
        $this->assertSame(1, $client->calls[0]['parameters']['page-no']);
        $this->assertSame('GET', $client->calls[0]['method']);
    }

    public function testClientCachesTheListAndSkipsResellerClubTheSecondTime()
    {
        $client = $this->client([
            '/actions/search-current.json' => $this->currentResponse(),
            '/actions/search-archived.json' => $this->archivedResponse(),
        ]);

        $client->domainActions(self::ORDER_ID);
        $callsAfterFirst = count($client->calls);
        $second = $client->domainActions(self::ORDER_ID);

        $this->assertCount($callsAfterFirst, $client->calls);
        $this->assertCount(3, $second);

        Cache::forget(ResellerClubClient::domainActionsCacheKey(self::ORDER_ID));
        $client->domainActions(self::ORDER_ID);
        $this->assertGreaterThan($callsAfterFirst, count($client->calls));
    }

    public function testClientReturnsNullAndCachesNothingWhenBothEndpointsFail()
    {
        $client = $this->client([
            '/actions/search-current.json' => null,
            '/actions/search-archived.json' => null,
        ]);

        // Nothing could be read, so this must not look like "no activity".
        $this->assertNull($client->domainActions(self::ORDER_ID));
        $this->assertNull(Cache::get(ResellerClubClient::domainActionsCacheKey(self::ORDER_ID)));

        $errored = $this->client([
            '/actions/search-current.json' => ['status' => 'ERROR', 'message' => 'denied'],
            '/actions/search-archived.json' => ['status' => 'ERROR', 'message' => 'Invalid credentials'],
        ]);
        $this->assertNull($errored->domainActions(self::ORDER_ID));
    }

    /**
     * The reported bug: an order whose last action is older than 24 hours has nothing
     * in "current", which ResellerClub answers badly. The archived history must still show.
     */
    public function testHistoryOlderThan24HoursStillShowsWhenCurrentCannotBeRead()
    {
        $client = $this->client([
            '/actions/search-current.json' => null,
            '/actions/search-archived.json' => $this->archivedResponse(),
        ]);

        $actions = $client->domainActions(self::ORDER_ID);

        $this->assertSame(['8001'], array_column($actions, 'id'));
        // A one-endpoint list is never the "last good" copy.
        $this->assertNull(Cache::get(ResellerClubClient::domainActionsLastGoodCacheKey(self::ORDER_ID)));
    }

    public function testRecentActivityStillShowsWhenArchivedCannotBeRead()
    {
        $client = $this->client([
            '/actions/search-current.json' => $this->currentResponse(),
            '/actions/search-archived.json' => null,
        ]);

        $this->assertSame(['9002', '9001'], array_column($client->domainActions(self::ORDER_ID), 'id'));
    }

    public function testANoRecordsErrorIsAnEmptyEndpointNotAFailure()
    {
        $this->assertSame(['total' => 0, 'actions' => []], DomainActivity::parseResponse(['status' => 'ERROR', 'message' => 'No records found']));
        $this->assertSame(['total' => 0, 'actions' => []], DomainActivity::parseResponse(['status' => 'ERROR', 'message' => 'No actions found for the given criteria']));
        $this->assertNull(DomainActivity::parseResponse(['status' => 'ERROR', 'message' => 'Invalid credentials']));
        $this->assertNull(DomainActivity::parseResponse(['status' => 'ERROR', 'message' => 'You are not authorised to perform this action']));

        $client = $this->client([
            '/actions/search-current.json' => ['status' => 'ERROR', 'message' => 'No records found'],
            '/actions/search-archived.json' => $this->archivedResponse(),
        ]);

        $this->assertSame(['8001'], array_column($client->domainActions(self::ORDER_ID), 'id'));
        // An empty answer is a real answer: one call per endpoint, no retry, and the list is cached as complete.
        $this->assertCount(2, $client->calls);
        $this->assertNotNull(Cache::get(ResellerClubClient::domainActionsLastGoodCacheKey(self::ORDER_ID)));
    }

    public function testOpeningManageClearsOnlyTheShortCopyAndKeepsTheOutageFallback()
    {
        $good = $this->client([
            '/actions/search-current.json' => $this->currentResponse(),
            '/actions/search-archived.json' => $this->archivedResponse(),
        ]);
        $good->domainActions(self::ORDER_ID);

        ResellerClubClient::forgetDomainActionsCache(self::ORDER_ID);

        $this->assertNull(Cache::get(ResellerClubClient::domainActionsCacheKey(self::ORDER_ID)));
        $this->assertNotNull(Cache::get(ResellerClubClient::domainActionsLastGoodCacheKey(self::ORDER_ID)));

        // ResellerClub is down at that moment: the fallback copy still shows the history.
        $down = $this->client([
            '/actions/search-current.json' => null,
            '/actions/search-archived.json' => null,
        ]);
        $this->assertCount(3, $down->domainActions(self::ORDER_ID));
    }

    public function testProbeReportsEachEndpointSeparately()
    {
        $client = $this->client([
            '/actions/search-current.json' => null,
            '/actions/search-archived.json' => $this->archivedResponse(),
        ]);

        $this->assertSame([
            '/actions/search-current.json' => null,
            '/actions/search-archived.json' => 1,
        ], $client->probeDomainActions(self::ORDER_ID));
        $this->assertNull($client->probeDomainActions('abc'));
    }

    public function testClientReturnsAnEmptyListWhenTheOrderHasNoActions()
    {
        $client = $this->client([
            '/actions/search-current.json' => ['recsonpage' => '0', 'recsindb' => '0'],
            '/actions/search-archived.json' => ['recsonpage' => '0', 'recsindb' => '0'],
        ]);

        $this->assertSame([], $client->domainActions(self::ORDER_ID));
    }

    public function testClientRejectsBadOrderIdsAndUnconfiguredAccountsWithoutCallingTheApi()
    {
        $client = $this->client([]);
        foreach ([null, '', 'abc', 0, '12x', -4] as $bad) {
            $this->assertNull($client->domainActions($bad));
        }
        $this->assertSame([], $client->calls);

        $off = $this->client([], false);
        $this->assertNull($off->domainActions(self::ORDER_ID + 1));
        $this->assertSame([], $off->calls);
    }

    public function testClientWalksEveryPageOfALongHistory()
    {
        $rows = function ($from, $count) {
            $page = ['recsonpage' => (string) $count, 'recsindb' => '51'];
            for ($i = 0; $i < $count; $i++) {
                $page[(string) ($i + 1)] = [
                    'eaqid' => (string) (1000 + $from + $i),
                    'entityid' => (string) self::ORDER_ID,
                    'actiontype' => 'ModNS',
                    'actiontypedesc' => 'Change ' . ($from + $i),
                    'actionstatus' => 'Success',
                    'actionadded' => (string) (1794000000 + $from + $i),
                    'actioncompleted' => (string) (1794000000 + $from + $i),
                ];
            }

            return $page;
        };

        $client = $this->client([
            '/actions/search-current.json' => function ($parameters) use ($rows) {
                return $parameters['page-no'] === 1 ? $rows(0, 50) : $rows(50, 1);
            },
            '/actions/search-archived.json' => ['recsonpage' => '0', 'recsindb' => '0'],
        ]);

        $actions = $client->domainActions(self::ORDER_ID);

        $this->assertCount(51, $actions);
        $this->assertSame('Change 50', $actions[0]['title']);
        $currentCalls = array_filter($client->calls, function ($call) {
            return $call['path'] === '/actions/search-current.json';
        });
        $this->assertSame([1, 2], array_values(array_column(array_column($currentCalls, 'parameters'), 'page-no')));
    }

    /**
     * Seen in production: search-current answered a bare HTTP 500 once and was
     * fine on the next call. One retry must be enough to hide that.
     */
    public function testClientRetriesOnceSoATransientFailureDoesNotHideTheActivity()
    {
        $attempts = 0;
        $current = $this->currentResponse();

        $client = $this->client([
            '/actions/search-current.json' => function () use (&$attempts, $current) {
                $attempts++;

                return $attempts === 1 ? null : $current;
            },
            '/actions/search-archived.json' => $this->archivedResponse(),
        ]);

        $actions = $client->domainActions(self::ORDER_ID);

        $this->assertSame(['9002', '9001', '8001'], array_column($actions, 'id'));
        $this->assertSame(2, $attempts);
    }

    public function testClientGivesUpAfterTheConfiguredAttempts()
    {
        $attempts = 0;
        $client = $this->client([
            '/actions/search-current.json' => function () use (&$attempts) {
                $attempts++;

                return null;
            },
            '/actions/search-archived.json' => null,
        ]);

        $this->assertNull($client->domainActions(self::ORDER_ID));
        $this->assertSame(ResellerClubClient::ACTIONS_ATTEMPTS, $attempts);
    }

    public function testClientFallsBackToTheLastListItReadWhenResellerClubFailsLater()
    {
        $good = $this->client([
            '/actions/search-current.json' => $this->currentResponse(),
            '/actions/search-archived.json' => $this->archivedResponse(),
        ]);
        $this->assertCount(3, $good->domainActions(self::ORDER_ID));

        // The short cache expires, then ResellerClub starts failing.
        Cache::forget(ResellerClubClient::domainActionsCacheKey(self::ORDER_ID));
        $failing = $this->client([
            '/actions/search-current.json' => null,
            '/actions/search-archived.json' => null,
        ]);

        $this->assertSame(['9002', '9001', '8001'], array_column($failing->domainActions(self::ORDER_ID), 'id'));
    }

    public function testChangingTheDomainDropsTheFallbackSoANewActionIsNeverHidden()
    {
        $good = $this->client([
            '/actions/search-current.json' => $this->currentResponse(),
            '/actions/search-archived.json' => $this->archivedResponse(),
        ]);
        $good->domainActions(self::ORDER_ID);

        // A change made through our site clears both copies...
        ResellerClubClient::forgetDomainActions(self::ORDER_ID);
        $this->assertNull(Cache::get(ResellerClubClient::domainActionsCacheKey(self::ORDER_ID)));
        $this->assertNull(Cache::get(ResellerClubClient::domainActionsLastGoodCacheKey(self::ORDER_ID)));

        // ...so a failure now is reported honestly instead of showing a list without the change.
        $failing = $this->client([
            '/actions/search-current.json' => null,
            '/actions/search-archived.json' => null,
        ]);
        $this->assertNull($failing->domainActions(self::ORDER_ID));
    }
}
