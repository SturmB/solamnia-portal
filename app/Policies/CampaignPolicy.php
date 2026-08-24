<?php

namespace App\Policies;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\User;
use Filament\Support\Authorization\DenyResponse;
use Illuminate\Auth\Access\Response;

class CampaignPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Campaign $campaign): bool|Response
    {
        return $this->allowUnlessSent($campaign, 'updated');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Campaign $campaign): bool|Response
    {
        return $this->allowUnlessSent($campaign, 'deleted');
    }

    private function allowUnlessSent(Campaign $campaign, string $action): bool|Response
    {
        if ($campaign->status() !== CampaignStatus::Sent) {
            return true;
        }

        return DenyResponse::make('sent', message: fn (int $failureCount, int $totalCount): string => "$failureCount / $totalCount were not $action — they've already been sent.");
    }
}
