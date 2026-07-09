<?php

namespace App\Console\Commands;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\Subscriber;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Signature('campaigns:send-due')]
#[Description('Send any due, not-yet-sent Campaign to every opted-in Subscriber.')]
class SendDueCampaigns extends Command
{
    public function handle(): int
    {
        $campaigns = Campaign::whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->whereNull('sent_at')
            ->get();

        foreach ($campaigns as $campaign) {
            $this->send($campaign);
        }

        return self::SUCCESS;
    }

    private function send(Campaign $campaign): void
    {
        // Idempotency claim — this UPDATE is atomic in the DB. Only the sweep that
        // flips sent_at from NULL to now() gets $claimed === 1; a concurrent or repeat
        // run sees 0 rows affected and bails. We stamp BEFORE sending (per ADR-0003's
        // no-retry rule): a claimed campaign is never sent twice, even if the send below
        // dies partway.
        //
        // ponytail: a failed send leaves sent_at stamped + recipient_count null, so status()
        // reads 'sent' with no count — a failed send looks like a sent one in the panel. The
        // failure signal is the Pushover alert below, not the table (per ADR-0003). Add a
        // CampaignStatus::Failed state only if the quarterly send ever needs UI-visible failure.
        $claimed = Campaign::whereKey($campaign)
            ->whereNull('sent_at')
            ->update(['sent_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        try {
            $subscribers = Subscriber::whereNull('unsubscribed_at')->get();

            foreach ($subscribers as $subscriber) {
                Mail::to($subscriber->email)->send(new CampaignMail($campaign));
            }

            $campaign->recipient_count = $subscribers->count();
            $campaign->save();
        } catch (Throwable $e) {
            $this->notify(
                title: "Campaign send FAILED: {$campaign->subject}",
                message: "Error: {$e->getMessage()}",
                priority: 1
            );
            report($e);

            return;
        }

        // Notify AFTER the try so a Pushover blip can't reclassify a good send as failed.
        // (Http::post only throws on a connection failure, not on a non-2xx from Pushover.)
        $this->notify(
            title: "Campaign sent: {$campaign->subject}",
            message: "Sent to {$subscribers->count()} subscribers."
        );
    }

    private function notify(string $title, string $message, int $priority = 0): void
    {
        $token = config('services.pushover.token');
        $user = config('services.pushover.user');

        if (! $token || ! $user) {
            return;
        }

        Http::post('https://api.pushover.net/1/messages.json', [
            'token' => $token,
            'user' => $user,
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
        ]);
    }
}
