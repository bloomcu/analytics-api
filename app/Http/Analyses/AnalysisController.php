<?php

namespace DDD\Http\Analyses;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Analyses\Resources\AnalysisResource;
use DDD\Domain\Analyses\Requests\AnalysisUpdateRequest;
use DDD\Domain\Analyses\Analysis;
use DDD\Domain\Analyses\Actions\Step5AnalyzeBiggestOpportunity;
use DDD\Domain\Analyses\Actions\Step4CalculateStepRatios;
use DDD\Domain\Analyses\Actions\Step3CalculateStepConversionRates;
use DDD\Domain\Analyses\Actions\Step2NormalizeFunnelSteps;
use DDD\Domain\Analyses\Actions\Step1AnalyzeOverallConversionRate;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;
use DDD\App\Controllers\Controller;

class AnalysisController extends Controller
{
    public function index(Organization $organization, Dashboard $dashboard)
    {
        return AnalysisResource::collection($dashboard->analyses);
    }

    public function store(Organization $organization, Dashboard $dashboard, Request $request)
    {   
        // return $request->comparisonFunnels;

        // Setup time period (later accrept this as a parameter from the request)
        $period = match ('last28Days') {
            'yesterday' => [
                'startDate' => now()->subDays(1)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ],
            'last7Days' => [
                'startDate' => now()->subDays(7)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ],
            'last28Days' => [
                'startDate' => now()->subDays(28)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ]
        };

        // Create a new analysis
        $analysis = $dashboard->analyses()->create([
            'subject_funnel_id' => $request->subjectFunnelId,
            'in_progress' => 1,
            'start_date' => now()->subDays(28), // 28 days ago
            'end_date' => now()->subDays(1), // yesterday
        ]);

        // Bail early if subject funnel has no steps
        if (count($analysis->subjectFunnel->steps) === 0) {
            return;
        }

        // Bail early if dashboard has no funnels
        if (count($analysis->dashboard->funnels) === 0) {
            return;
        }

        // Get subject funnel report
        // $subjectFunnelReport = GoogleAnalyticsData::funnelReport(
        //     connection: $analysis->subjectFunnel->connection, 
        //     startDate: $period['startDate'], 
        //     endDate: $period['endDate'],
        //     steps: $analysis->subjectFunnel->steps->toArray(),
        // );

        // return $subjectFunnelReport;

        // Add period to report
        // $subjectFunnelReport['period'] = $period['startDate'] . ' - ' . $period['endDate'];

        // Build array of comparison funnel reports
        // $comparisonFunnelReports = [];
        // foreach ($analysis->dashboard->funnels as $key => $funnel) {
        //     if ($key === 0) continue; // Skip subject funnel (already processed above)

        //     $report = GoogleAnalyticsData::funnelReport(
        //         connection: $funnel->connection, 
        //         startDate: $period['startDate'], 
        //         endDate: $period['endDate'],
        //         steps: $funnel->steps->toArray(),
        //     );

        //     $report['funnel_name'] = $funnel['name'];
        //     $report['period'] = $period['startDate'] . ' - ' . $period['endDate'];

        //     array_push($comparisonFunnelReports, $report);
        // }

        // TODO: Step 1 is broken because the overall conversion rate of each funnel is computed on the frontend
        // Step1AnalyzeOverallConversionRate::run($analysis, $request->subjectFunnel, $request->comparisonFunnels);

        // Step2NormalizeFunnelSteps::run($analysis, $subjectFunnelReport, $comparisonFunnelReports);
        // Step3CalculateStepConversionRates::run($analysis);
        // Step4CalculateStepRatios::run($analysis);
        Step5AnalyzeBiggestOpportunity::run($analysis, $request->subjectFunnel, $request->comparisonFunnels);

        return new AnalysisResource($analysis);
    }

    public function show(Organization $organization, Dashboard $dashboard, Analysis $analysis)
    {
        return new AnalysisResource($analysis);
    }

    public function update(Organization $organization, Dashboard $dashboard, Analysis $analysis, AnalysisUpdateRequest $request)
    {
        $analysis->update($request->validated());

        return new AnalysisResource($analysis);
    }

    // public function destroy(Organization $organization, Dashboard $dashboard, Analysis $analysis)
    // {
    //     $analysis->delete();

    //     return new AnalysisResource($analysis);
    // }
}
