<?php
/**
 * Client portal helpers — project tracking, wallet, invoices, messages,
 * notifications. All functions here operate on the currently logged-in
 * client (the public `users` table), not the admin/staff side.
 */

require_once __DIR__ . '/functions.php';

function client_require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: /pages/login.php');
        exit;
    }
    return $user;
}

function client_projects(int $userId): array
{
    $pdo = get_db();
    if (!$pdo) {
        return [];
    }
    $stmt = $pdo->prepare('SELECT * FROM client_projects WHERE user_id = :uid ORDER BY created_at DESC');
    $stmt->execute(['uid' => $userId]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($projects) {
        $stmt = $pdo->prepare('SELECT * FROM project_milestones WHERE project_id = :pid ORDER BY sort_order ASC, id ASC');
        foreach ($projects as &$project) {
            $stmt->execute(['pid' => $project['id']]);
            $project['milestones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($project);
    }

    return $projects;
}

function wallet_balance(int $userId): float
{
    $pdo = get_db();
    if (!$pdo) {
        return 0.0;
    }
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END), 0) FROM wallet_transactions WHERE user_id = :uid");
    $stmt->execute(['uid' => $userId]);
    return (float) $stmt->fetchColumn();
}

function wallet_transactions(int $userId): array
{
    $pdo = get_db();
    if (!$pdo) {
        return [];
    }
    $stmt = $pdo->prepare('SELECT * FROM wallet_transactions WHERE user_id = :uid ORDER BY created_at DESC, id DESC');
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function client_invoices(int $userId): array
{
    $pdo = get_db();
    if (!$pdo) {
        return [];
    }
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE user_id = :uid ORDER BY created_at DESC');
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function invoice_by_id(int $invoiceId): ?array
{
    $pdo = get_db();
    if (!$pdo) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = :id');
    $stmt->execute(['id' => $invoiceId]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$invoice) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM invoice_items WHERE invoice_id = :id ORDER BY sort_order ASC, id ASC');
    $stmt->execute(['id' => $invoiceId]);
    $invoice['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $invoice;
}

function client_messages(int $userId): array
{
    $pdo = get_db();
    if (!$pdo) {
        return [];
    }
    $stmt = $pdo->prepare('SELECT * FROM client_messages WHERE user_id = :uid ORDER BY created_at ASC, id ASC');
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function unread_message_count(int $userId, string $from = 'admin'): int
{
    $pdo = get_db();
    if (!$pdo) {
        return 0;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_messages WHERE user_id = :uid AND sender = :from AND is_read = 0");
    $stmt->execute(['uid' => $userId, 'from' => $from]);
    return (int) $stmt->fetchColumn();
}

function mark_messages_read(int $userId, string $from): void
{
    $pdo = get_db();
    if (!$pdo) {
        return;
    }
    $stmt = $pdo->prepare('UPDATE client_messages SET is_read = 1 WHERE user_id = :uid AND sender = :from AND is_read = 0');
    $stmt->execute(['uid' => $userId, 'from' => $from]);
}

function client_notifications(int $userId, int $limit = 0): array
{
    $pdo = get_db();
    if (!$pdo) {
        return [];
    }
    $sql = 'SELECT * FROM client_notifications WHERE user_id = :uid ORDER BY created_at DESC, id DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function unread_notification_count(int $userId): int
{
    $pdo = get_db();
    if (!$pdo) {
        return 0;
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM client_notifications WHERE user_id = :uid AND is_read = 0');
    $stmt->execute(['uid' => $userId]);
    return (int) $stmt->fetchColumn();
}

function notify_user(int $userId, string $type, string $title, string $body = '', string $link = ''): void
{
    $pdo = get_db();
    if (!$pdo) {
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO client_notifications (user_id, type, title, body, link) VALUES (:uid, :type, :title, :body, :link)');
    $stmt->execute(['uid' => $userId, 'type' => $type, 'title' => $title, 'body' => $body, 'link' => $link]);
}

function format_money(float $amount, string $currency = 'USD'): string
{
    $symbols = ['USD' => '$', 'EUR' => '€', 'EGP' => 'E£', 'GBP' => '£'];
    $symbol = $symbols[$currency] ?? ($currency . ' ');
    return $symbol . number_format($amount, 2);
}

function generate_invoice_number(PDO $pdo): string
{
    $year = date('Y');
    $count = (int) $pdo->query("SELECT COUNT(*) FROM invoices WHERE invoice_number LIKE 'INV-{$year}-%'")->fetchColumn();
    do {
        $count++;
        $number = sprintf('INV-%s-%04d', $year, $count);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM invoices WHERE invoice_number = :n');
        $stmt->execute(['n' => $number]);
    } while ((int) $stmt->fetchColumn() > 0);
    return $number;
}

function project_status_label(string $status): string
{
    $labels = [
        'planning' => t('project_status_planning'),
        'in_progress' => t('project_status_in_progress'),
        'review' => t('project_status_review'),
        'completed' => t('project_status_completed'),
        'on_hold' => t('project_status_on_hold'),
    ];
    return $labels[$status] ?? $status;
}

function invoice_status_label(string $status): string
{
    $labels = [
        'draft' => t('invoice_status_draft'),
        'sent' => t('invoice_status_sent'),
        'paid' => t('invoice_status_paid'),
        'overdue' => t('invoice_status_overdue'),
        'cancelled' => t('invoice_status_cancelled'),
    ];
    return $labels[$status] ?? $status;
}
