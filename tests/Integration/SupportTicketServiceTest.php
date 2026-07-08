<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Database;
use App\Core\Model;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\SupportTicket\Models\SupportTicket;
use App\Features\SupportTicket\Models\SupportTicketMessage;
use App\Features\SupportTicket\Services\SupportTicketService;
use PDO;
use PHPUnit\Framework\TestCase;

final class SupportTicketServiceTest extends TestCase
{
    private static PDO $pdo;

    private static string $applicantRoleId;

    private static string $adminId;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connection();

        $role = Role::findByName('Applicant');
        self::$applicantRoleId = $role['id'] ?? Model::uuid();
        if ($role === null) {
            Role::insert(['id' => self::$applicantRoleId, 'name' => 'Applicant', 'description' => 'Applicant', 'permissions' => '[]']);
        }

        $adminRole = Role::findByName('Admin');
        $adminRoleId = $adminRole['id'] ?? Model::uuid();
        if ($adminRole === null) {
            Role::insert(['id' => $adminRoleId, 'name' => 'Admin', 'description' => 'Admin', 'permissions' => '["*"]']);
        }

        self::$adminId = User::insert([
            'email' => 'ticket-admin@example.com',
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'A', 'last_name' => 'D',
            'role_id' => $adminRoleId,
            'is_active' => 1,
        ]);
    }

    protected function setUp(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['support_ticket_messages', 'support_tickets', 'notifications'] as $table) {
            self::$pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function makeApplicant(string $suffix): string
    {
        return User::insert([
            'email' => "ticket-user-{$suffix}@example.com",
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'U', 'last_name' => $suffix,
            'role_id' => self::$applicantRoleId,
            'is_active' => 1,
        ]);
    }

    public function test_create_ticket_starts_open(): void
    {
        $userId = $this->makeApplicant('a');
        $service = new SupportTicketService();

        $ticket = $service->create($userId, 'Cannot upload document', 'The upload button does nothing.', 'DOCUMENTS');

        $this->assertSame('OPEN', $ticket['status']);
        $this->assertSame('DOCUMENTS', $ticket['category']);
    }

    public function test_staff_reply_moves_ticket_to_pending_user_response(): void
    {
        $userId = $this->makeApplicant('b');
        $service = new SupportTicketService();
        $ticket = $service->create($userId, 'x', 'y', 'OTHER');

        $service->reply($ticket['id'], self::$adminId, 'Please clear your browser cache.', true);

        $this->assertSame('PENDING_USER_RESPONSE', SupportTicket::find($ticket['id'])['status']);
    }

    public function test_user_reply_moves_ticket_to_in_progress(): void
    {
        $userId = $this->makeApplicant('c');
        $service = new SupportTicketService();
        $ticket = $service->create($userId, 'x', 'y', 'OTHER');
        $service->reply($ticket['id'], self::$adminId, 'staff reply', true);

        $service->reply($ticket['id'], $userId, 'Still not working', false);

        $this->assertSame('IN_PROGRESS', SupportTicket::find($ticket['id'])['status']);
    }

    public function test_internal_note_does_not_change_ticket_status_or_appear_to_the_requester(): void
    {
        $userId = $this->makeApplicant('d');
        $service = new SupportTicketService();
        $ticket = $service->create($userId, 'x', 'y', 'OTHER');

        $service->reply($ticket['id'], self::$adminId, 'internal note for the team', true, true);

        $this->assertSame('OPEN', SupportTicket::find($ticket['id'])['status']);

        $publicMessages = SupportTicketMessage::forTicket($ticket['id'], false);
        $allMessages = SupportTicketMessage::forTicket($ticket['id'], true);

        $this->assertCount(0, $publicMessages);
        $this->assertCount(1, $allMessages);
    }

    public function test_assign_sets_assignee_and_moves_to_in_progress(): void
    {
        $userId = $this->makeApplicant('e');
        $service = new SupportTicketService();
        $ticket = $service->create($userId, 'x', 'y', 'OTHER');

        $updated = $service->assign($ticket['id'], self::$adminId);

        $this->assertSame(self::$adminId, $updated['assigned_to_id']);
        $this->assertSame('IN_PROGRESS', $updated['status']);
    }

    public function test_update_status_rejects_invalid_value(): void
    {
        $userId = $this->makeApplicant('f');
        $service = new SupportTicketService();
        $ticket = $service->create($userId, 'x', 'y', 'OTHER');

        $this->expectException(\InvalidArgumentException::class);
        $service->updateStatus($ticket['id'], 'NOT_A_STATUS');
    }

    public function test_closing_a_ticket_removes_it_from_the_open_queue(): void
    {
        $userId = $this->makeApplicant('g');
        $service = new SupportTicketService();
        $ticket = $service->create($userId, 'x', 'y', 'OTHER');

        $service->updateStatus($ticket['id'], 'CLOSED');

        $ids = array_column(SupportTicket::allOpenOrdered(), 'id');
        $this->assertNotContains($ticket['id'], $ids);
    }
}
