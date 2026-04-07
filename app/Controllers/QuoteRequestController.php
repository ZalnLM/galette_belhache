<?php
declare(strict_types=1);

class QuoteRequestController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        $requests = $this->db->query(
            'SELECT * FROM quote_requests WHERE user_id = ? ORDER BY created_at DESC',
            [(int)$user['id']]
        )->fetchAll();

        View::render('quote_requests/index', [
            'pageTitle' => 'Mes demandes',
            'requests' => $requests,
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $user = Auth::user();
        $selectedFormulas = $_POST['selected_formulas'] ?? [];
        $guestCounts = $_POST['formula_guest_count'] ?? [];
        $eventName = trim((string)($_POST['event_name'] ?? ''));
        $eventType = trim((string)($_POST['event_type'] ?? ''));
        $eventDate = trim((string)($_POST['event_date'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $comment = trim((string)($_POST['comment'] ?? ''));

        if ($eventName === '' || $eventDate === '' || $address === '' || $phone === '') {
            Flash::set('danger', 'Merci de completer les informations de l evenement.');
            header('Location: /');
            exit;
        }

        if (!is_array($selectedFormulas) || $selectedFormulas === []) {
            Flash::set('danger', 'Selectionne au moins une formule.');
            header('Location: /');
            exit;
        }

        $formulaIds = array_values(array_unique(array_map('intval', $selectedFormulas)));
        $placeholders = implode(',', array_fill(0, count($formulaIds), '?'));
        $formulas = $this->db->query(
            "SELECT * FROM formulas WHERE id IN ($placeholders) AND is_active = 1",
            $formulaIds
        )->fetchAll();

        $formulasById = [];
        foreach ($formulas as $formula) {
            $formulasById[(int)$formula['id']] = $formula;
        }

        $totalGuests = 0;
        $selectedRows = [];
        foreach ($formulaIds as $formulaId) {
            if (!isset($formulasById[$formulaId])) {
                continue;
            }

            $guestCount = (int)($guestCounts[$formulaId] ?? 0);
            $formula = $formulasById[$formulaId];
            if ($guestCount < (int)$formula['minimum_guests']) {
                Flash::set('danger', 'Le nombre de convives est insuffisant pour au moins une formule.');
                header('Location: /');
                exit;
            }

            $selectedRows[] = [
                'formula_id' => $formulaId,
                'guest_count' => $guestCount,
            ];
            $totalGuests += $guestCount;
        }

        if ($selectedRows === []) {
            Flash::set('danger', 'Aucune formule valide n a ete retenue.');
            header('Location: /');
            exit;
        }

        $this->db->query(
            "INSERT INTO quote_requests
                (user_id, event_name, event_type, event_date, total_guests, address, phone, comment, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'demande_recue')",
            [(int)$user['id'], $eventName, $eventType, $eventDate, $totalGuests, $address, $phone, $comment]
        );
        $requestId = $this->db->lastInsertId();

        foreach ($selectedRows as $row) {
            $formula = $formulasById[$row['formula_id']];
            $this->db->query(
                "INSERT INTO quote_request_formulas
                    (quote_request_id, formula_id, guest_count, price_per_person_snapshot)
                 VALUES (?, ?, ?, ?)",
                [$requestId, $row['formula_id'], $row['guest_count'], $formula['price_per_person']]
            );
        }

        if ($comment !== '') {
            $this->db->query(
                'INSERT INTO quote_messages (quote_request_id, sender_user_id, is_admin, body) VALUES (?, ?, 0, ?)',
                [$requestId, (int)$user['id'], $comment]
            );
        }

        Flash::set('success', 'Ta demande de devis a bien ete envoyee.');
        header('Location: /demande-devis/' . $requestId);
        exit;
    }

    public function show(string $id): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        $request = $this->db->query(
            'SELECT qr.*, u.first_name, u.last_name, u.email
             FROM quote_requests qr
             JOIN users u ON u.id = qr.user_id
             WHERE qr.id = ?',
            [(int)$id]
        )->fetch();

        if (!$request) {
            http_response_code(404);
            exit('Demande introuvable.');
        }

        if (!Auth::isAdmin() && (int)$request['user_id'] !== (int)$user['id']) {
            http_response_code(403);
            exit('Acces refuse.');
        }

        $formulas = $this->db->query(
            'SELECT qrf.*, f.name
             FROM quote_request_formulas qrf
             JOIN formulas f ON f.id = qrf.formula_id
             WHERE qrf.quote_request_id = ?
             ORDER BY f.name ASC',
            [(int)$id]
        )->fetchAll();

        $messages = $this->db->query(
            'SELECT qm.*, u.first_name, u.last_name
             FROM quote_messages qm
             JOIN users u ON u.id = qm.sender_user_id
             WHERE qm.quote_request_id = ?
             ORDER BY qm.created_at ASC',
            [(int)$id]
        )->fetchAll();

        $quote = $this->db->query(
            'SELECT * FROM quotes WHERE quote_request_id = ? ORDER BY id DESC LIMIT 1',
            [(int)$id]
        )->fetch();
        $quoteLines = [];
        if ($quote) {
            $quoteLines = $this->db->query(
                'SELECT * FROM quote_lines WHERE quote_id = ? ORDER BY id ASC',
                [(int)$quote['id']]
            )->fetchAll();
            unset($quote['internal_cost_total']);
        }

        View::render('quote_requests/show', [
            'pageTitle' => 'Demande de devis',
            'request' => $request,
            'formulas' => $formulas,
            'messages' => $messages,
            'quote' => $quote,
            'quoteLines' => $quoteLines,
        ]);
    }

    public function storeMessage(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $user = Auth::user();
        $body = trim((string)($_POST['body'] ?? ''));

        if ($body === '') {
            Flash::set('danger', 'Le message est vide.');
            header('Location: /demande-devis/' . (int)$id);
            exit;
        }

        $request = $this->db->query('SELECT * FROM quote_requests WHERE id = ?', [(int)$id])->fetch();
        if (!$request) {
            http_response_code(404);
            exit('Demande introuvable.');
        }

        if (!Auth::isAdmin() && (int)$request['user_id'] !== (int)$user['id']) {
            http_response_code(403);
            exit('Acces refuse.');
        }

        $this->db->query(
            'INSERT INTO quote_messages (quote_request_id, sender_user_id, is_admin, body) VALUES (?, ?, ?, ?)',
            [(int)$id, (int)$user['id'], Auth::isAdmin() ? 1 : 0, $body]
        );

        Flash::set('success', 'Message envoye.');
        header('Location: /demande-devis/' . (int)$id);
        exit;
    }

    public function showQuote(string $id): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        $quote = $this->db->query(
            'SELECT q.*, qr.event_name, qr.event_type, qr.event_date AS request_event_date, qr.total_guests, qr.address, qr.phone,
                    qr.status AS request_status, qr.id AS request_id, qr.user_id, u.first_name, u.last_name, u.email
             FROM quotes q
             JOIN quote_requests qr ON qr.id = q.quote_request_id
             JOIN users u ON u.id = qr.user_id
             WHERE q.id = ?',
            [(int)$id]
        )->fetch();

        if (!$quote) {
            http_response_code(404);
            exit('Devis introuvable.');
        }

        if (!Auth::isAdmin() && (int)$quote['user_id'] !== (int)$user['id']) {
            http_response_code(403);
            exit('Acces refuse.');
        }

        $quoteLines = $this->db->query(
            'SELECT * FROM quote_lines WHERE quote_id = ? ORDER BY id ASC',
            [(int)$id]
        )->fetchAll();
        unset($quote['internal_cost_total']);

        View::render('quotes/document', [
            'pageTitle' => 'Devis final',
            'quote' => $quote,
            'quoteLines' => $quoteLines,
            'showInternalCost' => false,
            'backLink' => '/demande-devis/' . (int)$quote['request_id'],
            'backLabel' => 'Retour au dossier',
        ]);
    }
}
