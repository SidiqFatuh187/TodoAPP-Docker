<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeadlineTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public static function timezones(): array
    {
        return [
            'WIB' => ['Asia/Jakarta', '2026-11-12 00:30:00', '2026-11-12 02:45:00'],
            'WITA' => ['Asia/Makassar', '2026-11-11 23:30:00', '2026-11-12 01:45:00'],
            'WIT' => ['Asia/Jayapura', '2026-11-11 22:30:00', '2026-11-12 00:45:00'],
        ];
    }

    public static function zones(): array
    {
        return [
            'WIB' => ['Asia/Jakarta'],
            'WITA' => ['Asia/Makassar'],
            'WIT' => ['Asia/Jayapura'],
        ];
    }

    #[DataProvider('timezones')]
    public function test_deadline_round_trip_uses_canonical_storage_and_local_presentation(
        string $timezone,
        string $storedDeadline,
        string $updatedDeadline,
    ): void {
        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-11-11 08:00:00', 'Asia/Jakarta'));
        $user = User::factory()->create(['timezone' => $timezone]);
        $payload = ['title' => 'Local midnight task', 'priority' => 'medium', 'deadline' => '2026-11-12T00:30'];

        $this->actingAs($user)->post(route('todo.store'), $payload)
            ->assertSessionHasNoErrors()->assertRedirect(route('todo.index'));
        $todo = Todo::where('user_id', $user->id)->sole();
        $this->assertSame($storedDeadline, $todo->getRawOriginal('deadline'));
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', date_default_timezone_get());
        $this->assertSame('Asia/Jakarta', $todo->deadline->timezoneName);

        $this->get(route('todo.edit', $todo->id))->assertOk()->assertSee('value="2026-11-12T00:30"', false);
        $this->get(route('todo.show', $todo->id))->assertOk()->assertSee('12 Nov 2026, 00:30');
        $this->get(route('todo.index'))->assertOk()->assertSee('12 Nov 2026');
        $this->get(route('dashboard'))->assertOk()->assertSee('12 Nov');
        $this->assertSame($storedDeadline, $todo->fresh()->getRawOriginal('deadline'));

        $payload['status'] = 'pending';
        $payload['deadline'] = '2026-11-12T02:45';
        $this->put(route('todo.update', $todo->id), $payload)
            ->assertSessionHasNoErrors()->assertRedirect(route('todo.index'));
        $this->assertSame($updatedDeadline, $todo->fresh()->getRawOriginal('deadline'));
        $this->get(route('todo.edit', $todo->id))->assertOk()->assertSee('value="2026-11-12T02:45"', false);

        $user->update(['timezone' => 'Asia/Jayapura']);
        $localized = Carbon::parse($updatedDeadline, 'Asia/Jakarta')->setTimezone('Asia/Jayapura')->format('Y-m-d\TH:i');
        $this->get(route('todo.edit', $todo->id))->assertOk()->assertSee('value="'.$localized.'"', false);
        $this->assertSame($updatedDeadline, $todo->fresh()->getRawOriginal('deadline'));

        $otherUser = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $this->actingAs($otherUser)->get(route('dashboard'))->assertOk();
        $this->assertSame($updatedDeadline, $todo->fresh()->getRawOriginal('deadline'));
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', date_default_timezone_get());
    }

    #[DataProvider('zones')]
    public function test_due_date_groups_use_user_day_boundaries(string $timezone): void
    {
        $this->withoutVite();
        $localNow = Carbon::parse('2026-11-12 00:15:00', $timezone);
        $this->travelTo($localNow->copy()->setTimezone('Asia/Jakarta'));
        $user = User::factory()->create(['timezone' => $timezone]);
        $start = $localNow->copy()->startOfDay()->setTimezone('Asia/Jakarta');
        $deadlines = [
            'overdue' => $start->copy()->subSecond(),
            'today' => $start->copy(),
            'tomorrow' => $start->copy()->addDay(),
            'later' => $start->copy()->addDays(2),
        ];
        $tasks = [];
        foreach ($deadlines as $group => $deadline) {
            $tasks[$group] = Todo::create([
                'user_id' => $user->id,
                'title' => $group,
                'priority' => 'medium',
                'status' => 'pending',
                'deadline' => $deadline,
            ]);
        }
        Todo::create([
            'user_id' => $user->id, 'title' => 'Completed', 'status' => 'completed',
            'priority' => 'medium', 'deadline' => $start,
        ]);
        Todo::create([
            'user_id' => $user->id, 'title' => 'Undated', 'status' => 'pending', 'priority' => 'medium',
        ]);
        $response = $this->actingAs($user)->get(route('due-dates.index'))->assertOk();
        foreach ($tasks as $group => $task) {
            $response->assertViewHas($group, fn ($todos) => $todos->modelKeys() === [$task->id]);
            $this->assertSame($deadlines[$group]->format('Y-m-d H:i:s'), $task->fresh()->getRawOriginal('deadline'));
        }
        $response->assertSee('12 Nov 2026, 00:00');
        $this->get(route('dashboard'))->assertOk()->assertViewHas('overdueTasks', 2);
    }

    #[DataProvider('zones')]
    public function test_create_validates_the_users_local_calendar_day(string $timezone): void
    {
        $localNow = Carbon::parse('2026-11-12 00:45:00', $timezone);
        $this->travelTo($localNow->copy()->setTimezone('Asia/Jakarta'));
        $user = User::factory()->create(['timezone' => $timezone]);
        $payload = ['title' => 'Date validation', 'priority' => 'medium', 'deadline' => '2026-11-11T23:59'];

        $this->actingAs($user)->post(route('todo.store'), $payload)->assertSessionHasErrors('deadline');
        $this->assertDatabaseCount('todos', 0);
        $payload['deadline'] = '2026-11-12T00:30';
        $this->post(route('todo.store'), $payload)->assertSessionHasNoErrors()->assertRedirect(route('todo.index'));
        $this->assertDatabaseCount('todos', 1);
    }
}
