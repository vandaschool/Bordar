<?php

declare(strict_types=1);

namespace App\Features\SupportTicket\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Features\Auth\Models\User;
use App\Features\SupportTicket\Models\SupportTicket;
use App\Features\SupportTicket\Models\SupportTicketMessage;
use App\Features\SupportTicket\Services\SupportTicketService;

final class SupportTicketStaffController extends Controller
{
    private SupportTicketService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SupportTicketService();
    }

    public function index(): void
    {
        $openTickets = SupportTicket::allOpenOrdered();
        $requesters = User::findMany(array_column($openTickets, 'user_id'));

        $tickets = array_map(static function (array $t) use ($requesters) {
            $t['requester'] = $requesters[$t['user_id']] ?? null;

            return $t;
        }, $openTickets);

        $this->render('SupportTicket::staff-index', ['tickets' => $tickets]);
    }

    public function show(string $id): void
    {
        $ticket = SupportTicket::find($id);

        if ($ticket === null) {
            $this->redirect('/staff/tickets');

            return;
        }

        $this->render('SupportTicket::show', [
            'ticket' => array_merge($ticket, ['requester' => User::find($ticket['user_id'])]),
            'messages' => SupportTicketMessage::forTicket($id, true),
            'isStaff' => true,
            'statuses' => SupportTicket::STATUSES,
        ]);
    }

    public function reply(string $id): void
    {
        $this->requireCsrf();

        $message = (string) Request::input('message', '');
        $isInternal = (string) Request::input('is_internal', '0') === '1';

        if (trim($message) !== '') {
            $this->service->reply($id, Auth::id(), $message, true, $isInternal);
        }

        $this->redirect('/staff/tickets/' . $id);
    }

    public function assignToMe(string $id): void
    {
        $this->requireCsrf();

        $this->service->assign($id, Auth::id());
        $this->redirect('/staff/tickets/' . $id);
    }

    public function updateStatus(string $id): void
    {
        $this->requireCsrf();

        $status = (string) Request::input('status', '');

        try {
            $this->service->updateStatus($id, $status);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/staff/tickets/' . $id);
    }
}
