<?php

namespace App\Services;

use App\Models\BugReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubIssueService
{
    private string $token;

    private string $owner;

    private string $repo;

    private string $apiUrl;

    public function __construct()
    {
        $this->token = config('services.github.token');
        $this->owner = config('services.github.owner', 'xjanova');
        $this->repo = config('services.github.repo', 'xmanstudio');
        $this->apiUrl = "https://api.github.com/repos/{$this->owner}/{$this->repo}";
    }

    /**
     * Create a GitHub issue from a bug report
     *
     * @param BugReport $report
     * @return array|null Returns issue data or null on failure
     */
    public function createIssue(BugReport $report): ?array
    {
        if ($report->isPostedToGitHub()) {
            Log::warning("Bug report #{$report->id} already posted to GitHub as issue #{$report->github_issue_number}");
            return null;
        }

        try {
            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Accept' => 'application/vnd.github+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ])
                ->post("{$this->apiUrl}/issues", [
                    'title' => $report->getGitHubIssueTitle(),
                    'body' => $report->getGitHubIssueBody(),
                    'labels' => $report->getGitHubLabels(),
                ]);

            if ($response->successful()) {
                $issueData = $response->json();

                $report->markAsPostedToGitHub(
                    $issueData['number'],
                    $issueData['html_url']
                );

                Log::info("Created GitHub issue #{$issueData['number']} for bug report #{$report->id}");

                return $issueData;
            }

            Log::error("Failed to create GitHub issue for bug report #{$report->id}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while creating GitHub issue for bug report #{$report->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Update an existing GitHub issue
     *
     * @param BugReport $report
     * @param array $data
     * @return array|null
     */
    public function updateIssue(BugReport $report, array $data): ?array
    {
        if (!$report->isPostedToGitHub()) {
            Log::warning("Bug report #{$report->id} not yet posted to GitHub");
            return null;
        }

        try {
            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Accept' => 'application/vnd.github+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ])
                ->patch("{$this->apiUrl}/issues/{$report->github_issue_number}", $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Failed to update GitHub issue #{$report->github_issue_number}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while updating GitHub issue #{$report->github_issue_number}", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Add a comment to a GitHub issue
     *
     * @param BugReport $report
     * @param string $comment
     * @return array|null
     */
    public function addComment(BugReport $report, string $comment): ?array
    {
        if (!$report->isPostedToGitHub()) {
            Log::warning("Bug report #{$report->id} not yet posted to GitHub");
            return null;
        }

        try {
            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Accept' => 'application/vnd.github+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ])
                ->post("{$this->apiUrl}/issues/{$report->github_issue_number}/comments", [
                    'body' => $comment,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Failed to add comment to GitHub issue #{$report->github_issue_number}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while adding comment to GitHub issue #{$report->github_issue_number}", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Close a GitHub issue
     *
     * @param BugReport $report
     * @param string|null $reason
     * @return array|null
     */
    public function closeIssue(BugReport $report, ?string $reason = null): ?array
    {
        if ($reason) {
            $this->addComment($report, $reason);
        }

        return $this->updateIssue($report, [
            'state' => 'closed',
        ]);
    }

    /**
     * Add labels to a GitHub issue
     *
     * @param BugReport $report
     * @param array $labels
     * @return array|null
     */
    public function addLabels(BugReport $report, array $labels): ?array
    {
        if (!$report->isPostedToGitHub()) {
            Log::warning("Bug report #{$report->id} not yet posted to GitHub");
            return null;
        }

        try {
            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Accept' => 'application/vnd.github+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ])
                ->post("{$this->apiUrl}/issues/{$report->github_issue_number}/labels", [
                    'labels' => $labels,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while adding labels to GitHub issue #{$report->github_issue_number}", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create multiple GitHub issues in batch
     *
     * @param \Illuminate\Support\Collection $reports
     * @return array
     */
    public function createBatchIssues($reports): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($reports as $report) {
            $issue = $this->createIssue($report);

            if ($issue) {
                $results['success'][] = [
                    'report_id' => $report->id,
                    'issue_number' => $issue['number'],
                    'issue_url' => $issue['html_url'],
                ];
            } else {
                $results['failed'][] = $report->id;
            }

            // Rate limiting: Sleep for 1 second between requests
            sleep(1);
        }

        return $results;
    }
}
