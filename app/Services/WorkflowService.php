<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductDataProfile;
use App\Models\ProductWorkflow;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkflowService
{
    public function listWorkflows(User $user, int $productId, array $filters = []): LengthAwarePaginator
    {
        $query = ProductWorkflow::byUser($user->id)
            ->where('product_id', $productId)
            ->orderByDesc('updated_at');

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (! empty($filters['app'])) {
            $query->where('target_app_package', $filters['app']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function storeWorkflow(User $user, int $productId, array $data): ProductWorkflow
    {
        return ProductWorkflow::create([
            'user_id' => $user->id,
            'product_id' => $productId,
            'name' => $data['name'],
            'target_app_package' => $data['target_app_package'] ?? '',
            'target_app_name' => $data['target_app_name'] ?? '',
            'steps_json' => $data['steps_json'],
            'device_id' => $data['device_id'] ?? null,
            'app_version' => $data['app_version'] ?? null,
            'local_id' => $data['local_id'] ?? null,
        ]);
    }

    public function updateWorkflow(ProductWorkflow $workflow, array $data): ProductWorkflow
    {
        $workflow->update(array_filter([
            'name' => $data['name'] ?? null,
            'target_app_package' => $data['target_app_package'] ?? null,
            'target_app_name' => $data['target_app_name'] ?? null,
            'steps_json' => $data['steps_json'] ?? null,
        ], fn ($v) => $v !== null));

        return $workflow->fresh();
    }

    public function deleteWorkflow(ProductWorkflow $workflow): bool
    {
        return $workflow->delete();
    }

    public function bulkImportWorkflows(User $user, int $productId, array $workflowsData): array
    {
        $imported = 0;
        $skipped = 0;

        foreach ($workflowsData as $data) {
            if (empty($data['name']) || empty($data['steps_json'])) {
                $skipped++;

                continue;
            }

            // Check duplicate by local_id
            if (! empty($data['local_id'])) {
                $existing = ProductWorkflow::byUser($user->id)
                    ->where('product_id', $productId)
                    ->where('local_id', $data['local_id'])
                    ->first();

                if ($existing) {
                    $this->updateWorkflow($existing, $data);
                    $imported++;

                    continue;
                }
            }

            $this->storeWorkflow($user, $productId, $data);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    public function listProfiles(User $user, int $productId): LengthAwarePaginator
    {
        return ProductDataProfile::byUser($user->id)
            ->where('product_id', $productId)
            ->orderByDesc('updated_at')
            ->paginate(50);
    }

    public function storeProfile(User $user, int $productId, array $data): ProductDataProfile
    {
        return ProductDataProfile::create([
            'user_id' => $user->id,
            'product_id' => $productId,
            'name' => $data['name'],
            'category' => $data['category'] ?? '',
            'fields_json' => $data['fields_json'],
            'device_id' => $data['device_id'] ?? null,
            'local_id' => $data['local_id'] ?? null,
        ]);
    }

    public function updateProfile(ProductDataProfile $profile, array $data): ProductDataProfile
    {
        $profile->update(array_filter([
            'name' => $data['name'] ?? null,
            'category' => $data['category'] ?? null,
            'fields_json' => $data['fields_json'] ?? null,
        ], fn ($v) => $v !== null));

        return $profile->fresh();
    }

    public function bulkImportProfiles(User $user, int $productId, array $profilesData): array
    {
        $imported = 0;
        $skipped = 0;

        foreach ($profilesData as $data) {
            if (empty($data['name']) || empty($data['fields_json'])) {
                $skipped++;

                continue;
            }

            if (! empty($data['local_id'])) {
                $existing = ProductDataProfile::byUser($user->id)
                    ->where('product_id', $productId)
                    ->where('local_id', $data['local_id'])
                    ->first();

                if ($existing) {
                    $this->updateProfile($existing, $data);
                    $imported++;

                    continue;
                }
            }

            $this->storeProfile($user, $productId, $data);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    public function getProductBySlug(string $slug): ?Product
    {
        return Product::where('slug', $slug)->first();
    }
}
