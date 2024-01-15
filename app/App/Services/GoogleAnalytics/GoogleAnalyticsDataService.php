<?php
// TODO: Maybe move this to the Google Analytics service folder

namespace DDD\App\Services\GoogleAnalytics;

use Google\ApiCore\CredentialsWrapper;
use Google\ApiCore\ApiException;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use DDD\Domain\Connections\Connection;
use DDD\App\Facades\Google\GoogleAuth;

class GoogleAnalyticsDataService
{
    /**
     * Run a report
     * 
     * Docs: https://cloud.google.com/php/docs/reference/analytics-data/latest/Google.Analytics.Data.V1beta.BetaAnalyticsDataClient#_runReport
     * PHP Client: https://github.com/googleapis/php-analytics-data/blob/master/samples/V1beta/BetaAnalyticsDataClient/run_report.php
     */
    public function runReport(Connection $connection)
    {
        $client = new BetaAnalyticsDataClient(['credentials' => $this->setupCredentials($connection)]);

        // Prepare the request
        $request = (new RunReportRequest())
            ->setProperty($connection->uid)
            ->setDateRanges([
                new DateRange([
                    'start_date' => '7daysAgo',
                    'end_date' => 'today',
                ]),
            ])
            // ->setDimensions([
            //     new Dimension([
            //         'name' => 'date',
            //     ]),
            // ])
            ->setMetrics([
                new Metric([
                    'name' => 'activeUsers',
                ]),
                new Metric([
                    'name' => 'eventCount',
                ]),
                new Metric([
                    'name' => 'newUsers',
                ]),
            ]);
            // ->setOrderbys([
            //     new OrderBy([
            //         'dimension' => new DimensionOrderBy([
            //             'dimension_name' => 'date'
            //         ])
            //     ])
	        // ]);

        // Call the API and handle any network failures.
        try {
            $response = $client->runReport($request);

            return json_decode($response->serializeToJsonString());
        } catch (ApiException $ex) {
            abort(500, 'Call failed with message: %s' . $ex->getMessage());
        }
    }

    /**
     * Setup credentials for Analytics Data Client
     * 
     * https://stackoverflow.com/questions/73334495/how-to-use-access-tokens-with-google-admin-api-for-ga4-properties 
     */
    // TODO: Should this be a constructor, or a standalone class or helper?
    private function setupCredentials(Connection $connection)
    {
        $validConnection = GoogleAuth::validateConnection($connection);

        $credentials = CredentialsWrapper::build([
            'keyFile' => [
                'type'          => 'authorized_user',
                'client_id'     => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $validConnection->token['access_token'],
            ],
            'scopes'  => [
                'https://www.googleapis.com/auth/analytics',
                'https://www.googleapis.com/auth/analytics.readonly',
            ]
        ]);

        return $credentials;
    }
}
