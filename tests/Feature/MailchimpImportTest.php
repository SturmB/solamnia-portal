<?php

use App\Models\Subscriber;

/**
 * Write rows to a temp CSV and return its path.
 *
 * @param  array<int, array<int, string>>  $rows
 */
function mailchimpCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'mc').'.csv';
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

it('imports subscribed contacts as opted-in Subscribers', function () {
    $subscribed = mailchimpCsv([
        ['Email Address', 'First Name', 'Last Name'],
        ['jess@example.com', 'Jess', 'Stevens'],
    ]);

    $this->artisan('newsletter:import-mailchimp', ['subscribed' => $subscribed])
        ->assertSuccessful();

    $subscriber = Subscriber::sole();

    expect($subscriber->email)->toBe('jess@example.com')
        ->and($subscriber->name)->toBe('Jess Stevens')
        ->and($subscriber->unsubscribed_at)->toBeNull();
});

it('does not create a second Subscriber for a duplicate email in the export', function () {
    $subscribed = mailchimpCsv([
        ['Email Address', 'First Name', 'Last Name'],
        ['jess@example.com', 'Jess', 'Stevens'],
        ['jess@example.com', 'Jess', 'Stevens'],
    ]);

    $this->artisan('newsletter:import-mailchimp', ['subscribed' => $subscribed])
        ->assertSuccessful();

    expect(Subscriber::where('email', 'jess@example.com')->count())->toBe(1);
});

it('imports the unsubscribed file as opted-out Subscribers', function () {
    $subscribed = mailchimpCsv([
        ['Email Address', 'First Name', 'Last Name'],
        ['jess@example.com', 'Jess', 'Stevens'],
    ]);
    $unsubscribed = mailchimpCsv([
        ['Email Address', 'First Name', 'Last Name'],
        ['josh@example.com', 'Josh', 'Carey'],
    ]);

    $this->artisan('newsletter:import-mailchimp', [
        'subscribed' => $subscribed,
        'unsubscribed' => $unsubscribed,
    ])->assertSuccessful();

    expect(Subscriber::whereNull('unsubscribed_at')->pluck('email')->all())
        ->toBe(['jess@example.com'])
        ->and(Subscriber::whereNotNull('unsubscribed_at')->pluck('email')->all())
        ->toBe(['josh@example.com']);
});

it('is idempotent and never re-subscribes an opted-out contact', function () {
    Subscriber::factory()->unsubscribed()->create(['email' => 'gone@example.com']);

    $subscribed = mailchimpCsv([
        ['Email Address', 'First Name', 'Last Name'],
        ['jess@example.com', 'Jess', 'Stevens'],
        ['gone@example.com', 'Gone', 'Away'],
    ]);

    $this->artisan('newsletter:import-mailchimp', ['subscribed' => $subscribed])
        ->assertSuccessful();
    $this->artisan('newsletter:import-mailchimp', ['subscribed' => $subscribed])
        ->assertSuccessful();

    expect(Subscriber::count())->toBe(2)
        ->and(Subscriber::firstWhere('email', 'gone@example.com')->unsubscribed_at)
        ->not->toBeNull();
});
