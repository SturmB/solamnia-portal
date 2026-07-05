<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

// ponytail: one-shot Mailchimp cutover import — delete this command (and its test) once the real export is imported and Mailchimp is cancelled.
#[Signature('newsletter:import-mailchimp {subscribed} {unsubscribed?}')]
#[Description('One-shot import of the Mailchimp contact export into Subscribers.')]
class ImportMailchimp extends Command
{
    public function handle(): int
    {
        $this->import($this->argument('subscribed'), optedOut: false);

        if ($unsubscribed = $this->argument('unsubscribed')) {
            $this->import($unsubscribed, optedOut: true);
        }

        return self::SUCCESS;
    }

    /**
     * Read a Mailchimp CSV, yielding each data row keyed by its column header.
     *
     * @return iterable<int, array<string, string>>
     */
    private function rows(string $path): iterable
    {
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            yield array_combine($headers, $row);
        }

        fclose($handle);
    }

    private function import(string $path, bool $optedOut): void
    {
        foreach ($this->rows($path) as $row) {
            // ponytail: exact-string email match — fine for this one clean, lowercase export. If Subscriber ever takes writes from elsewhere, normalize email on the model instead.
            Subscriber::firstOrCreate(
                ['email' => $row['Email Address']],
                [
                    'name' => trim($row['First Name'].' '.$row['Last Name']),
                    'unsubscribed_at' => $optedOut ? now() : null,
                ],
            );
        }
    }
}
