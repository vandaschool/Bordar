<?php

declare(strict_types=1);

namespace App\Features\SupportTicket\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Features\SupportTicket\Models\SupportTicket;
use App\Features\SupportTicket\Models\SupportTicketMessage;
use App\Features\SupportTicket\Services\SupportTicketService;

final class SupportTicketController extends Controller
{
    private SupportTicketService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SupportTicketService();
    }

    public function index(): void
    {
        $this->render('SupportTicket::index', [
            'tickets' => SupportTicket::forUser(Auth::id()),
            'categories' => SupportTicketService::CATEGORIES,
            'status' => Session::flash('status'),
            'error' => Session::flash('error'),
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();

        $subject = (string) Request::input('subject', '');
        $description = (string) Request::input('description', '');
        $category = (string) Request::input('category', 'OTHER');

        if (trim($subject) === '' || trim($description) === '') {
            Session::flash('error', 'عنوان و توضیحات تیکت الزامی است.');
            $this->redirect('/tickets');

            return;
        }

        $ticket = $this->service->create(Auth::id(), $subject, $description, $category);
        $this->redirect('/tickets/' . $ticket['id']);
    }

    private function loadOwnTicket(string $id): ?array
    {
        $ticket = SupportTicket::find($id);

        if ($ticket === null || $ticket['user_id'] !== Auth::id()) {
            return null;
        }

        return $ticket;
    }

    public function show(string $id): void
    {
        $ticket = $this->loadOwnTicket($id);

        if ($ticket === null) {
            $this->redirect('/tickets');

            return;
        }

        $this->render('SupportTicket::show', [
            'ticket' => $ticket,
            'messages' => SupportTicketMessage::forTicket($id, false),
            'isStaff' => false,
        ]);
    }

    public function reply(string $id): void
    {
        $this->requireCsrf();

        $ticket = $this->loadOwnTicket($id);

        if ($ticket === null) {
            $this->redirect('/tickets');

            return;
        }

        $message = (string) Request::input('message', '');

        if (trim($message) !== '') {
            $this->service->reply($id, Auth::id(), $message, false);
        }

        $this->redirect('/tickets/' . $id);
    }
}
