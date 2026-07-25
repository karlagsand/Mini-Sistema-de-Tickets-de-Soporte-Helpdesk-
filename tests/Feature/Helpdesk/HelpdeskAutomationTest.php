<?php

namespace Tests\Feature\Helpdesk;

use App\Models\Category;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatus;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PrioritySeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TicketStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpdeskAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            CategorySeeder::class,
            PrioritySeeder::class,
            TicketStatusSeeder::class,
        ]);
    }

    public function test_guest_cannot_access_protected_modules(): void
    {
        $this->get(route('tickets.index'))
            ->assertRedirect(route('login'));

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_access_admin_modules(): void
    {
        $admin = $this->createUserByRole('Administrador');

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('categories.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('priorities.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('ticket-statuses.index'))
            ->assertOk();
    }

    public function test_standard_user_cannot_access_admin_modules(): void
    {
        $user = $this->createUserByRole('Usuario');

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('priorities.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('ticket-statuses.index'))
            ->assertForbidden();
    }

    public function test_authenticated_user_can_create_ticket_with_initial_status_and_history(): void
    {
        $user = $this->createUserByRole('Usuario');
        $category = Category::query()->firstOrFail();
        $priority = Priority::query()->firstOrFail();
        $newStatus = TicketStatus::query()->where('slug', 'nuevo')->firstOrFail();

        $response = $this->actingAs($user)->post(route('tickets.store'), [
            'subject' => 'Falla en impresora',
            'description' => 'La impresora no responde al enviar trabajos.',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
        ]);

        $response->assertRedirect();

        $ticket = Ticket::query()->where('subject', 'Falla en impresora')->first();

        $this->assertNotNull($ticket);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'subject' => 'Falla en impresora',
            'created_by' => $user->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $newStatus->id,
        ]);

        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'previous_status_id' => null,
            'new_status_id' => $newStatus->id,
            'changed_by' => $user->id,
        ]);
    }

    public function test_ticket_creation_validates_required_fields(): void
    {
        $user = $this->createUserByRole('Usuario');

        $response = $this->from(route('tickets.create'))
            ->actingAs($user)
            ->post(route('tickets.store'), [
                'subject' => '',
                'description' => '',
                'category_id' => '',
                'priority_id' => '',
            ]);

        $response->assertRedirect(route('tickets.create'));
        $response->assertSessionHasErrors([
            'subject',
            'description',
            'category_id',
            'priority_id',
        ]);

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_admin_can_update_ticket_status_and_register_history(): void
    {
        $admin = $this->createUserByRole('Administrador');
        $user = $this->createUserByRole('Usuario');
        $ticket = $this->createTicket($user);

        $resolvedStatus = TicketStatus::query()->where('slug', 'resuelto')->firstOrFail();
        $newStatusId = TicketStatus::query()->where('slug', 'nuevo')->value('id');

        $response = $this->actingAs($admin)->put(route('tickets.update', $ticket), [
            'status_id' => $resolvedStatus->id,
            'assigned_to' => '',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertEquals($resolvedStatus->id, $ticket->status_id);
        $this->assertNotNull($ticket->resolved_at);

        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'previous_status_id' => $newStatusId,
            'new_status_id' => $resolvedStatus->id,
            'changed_by' => $admin->id,
        ]);
    }

    public function test_standard_user_cannot_update_tickets(): void
    {
        $user = $this->createUserByRole('Usuario');
        $ticket = $this->createTicket($user);

        $resolvedStatus = TicketStatus::query()->where('slug', 'resuelto')->firstOrFail();

        $this->actingAs($user)
            ->put(route('tickets.update', $ticket), [
                'status_id' => $resolvedStatus->id,
                'assigned_to' => '',
            ])
            ->assertForbidden();

        $ticket->refresh();

        $this->assertNotEquals($resolvedStatus->id, $ticket->status_id);
    }

    public function test_user_can_only_see_own_ticket(): void
    {
        $owner = $this->createUserByRole('Usuario');
        $otherUser = $this->createUserByRole('Usuario');

        $ticket = $this->createTicket($owner);

        $this->actingAs($owner)
            ->get(route('tickets.show', $ticket))
            ->assertOk();

        $this->actingAs($otherUser)
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_agent_can_view_unassigned_or_own_assigned_ticket_only(): void
    {
        $creator = $this->createUserByRole('Usuario');
        $agentOne = $this->createUserByRole('Agente');
        $agentTwo = $this->createUserByRole('Agente');

        $unassignedTicket = $this->createTicket($creator);
        $assignedTicket = $this->createTicket($creator, [
            'assigned_to' => $agentOne->id,
        ]);

        $this->actingAs($agentOne)
            ->get(route('tickets.show', $assignedTicket))
            ->assertOk();

        $this->actingAs($agentOne)
            ->get(route('tickets.show', $unassignedTicket))
            ->assertOk();

        $this->actingAs($agentTwo)
            ->get(route('tickets.show', $assignedTicket))
            ->assertForbidden();
    }

    public function test_comments_are_stored_with_expected_visibility_by_role(): void
{
    $admin = $this->createUserByRole('Administrador');
    $agent = $this->createUserByRole('Agente');
    $user = $this->createUserByRole('Usuario');

    $ticket = $this->createTicket($user, [
        'assigned_to' => $agent->id,
    ]);

    $this->actingAs($user)->post(route('tickets.comments.store', $ticket), [
        'comment' => 'Necesito seguimiento de este caso.',
    ])->assertRedirect(route('tickets.show', $ticket));

    $this->actingAs($agent)->post(route('tickets.comments.store', $ticket), [
        'comment' => 'Se revisará en mesa técnica.',
        'is_internal' => '1',
    ])->assertRedirect(route('tickets.show', $ticket));

    $this->actingAs($admin)->post(route('tickets.comments.store', $ticket), [
        'comment' => 'Comentario de supervisión.',
        'is_internal' => '1',
    ])->assertRedirect(route('tickets.show', $ticket));

    $publicComment = TicketComment::query()
        ->where('ticket_id', $ticket->id)
        ->where('user_id', $user->id)
        ->first();

    $agentComment = TicketComment::query()
        ->where('ticket_id', $ticket->id)
        ->where('user_id', $agent->id)
        ->first();

    $adminComment = TicketComment::query()
        ->where('ticket_id', $ticket->id)
        ->where('user_id', $admin->id)
        ->first();

    $this->assertNotNull($publicComment);
    $this->assertNotNull($agentComment);
    $this->assertNotNull($adminComment);

    $this->assertFalse((bool) $publicComment->is_internal);
    $this->assertTrue((bool) $agentComment->is_internal);
    $this->assertTrue((bool) $adminComment->is_internal);
}

    public function test_admin_can_access_dashboard(): void
    {
        $admin = $this->createUserByRole('Administrador');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_agent_can_access_dashboard(): void
{
    $agent = $this->createUserByRole('Agente');

    $this->actingAs($agent)
        ->get(route('dashboard'))
        ->assertOk();
}

    private function createUserByRole(string $roleName): User
    {
        $roleId = Role::query()->where('name', $roleName)->value('id');

        return User::factory()->create([
            'role_id' => $roleId,
        ]);
    }

    private function createTicket(User $creator, array $overrides = []): Ticket
    {
        $newStatusId = TicketStatus::query()->where('slug', 'nuevo')->value('id');

        return Ticket::query()->create(array_merge([
            'folio' => 'HD-' . now()->format('Ymd') . '-' . strtoupper(fake()->bothify('??##??')),
            'subject' => 'Incidencia de prueba',
            'description' => 'Descripción de prueba del ticket.',
            'category_id' => Category::query()->firstOrFail()->id,
            'priority_id' => Priority::query()->firstOrFail()->id,
            'status_id' => $newStatusId,
            'created_by' => $creator->id,
            'assigned_to' => null,
            'opened_at' => now(),
        ], $overrides));
    }
}