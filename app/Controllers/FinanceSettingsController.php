<?php
require_once ROOT_DIR . '/app/Controllers/FinanceBaseController.php';

/** Finance Settings: currencies, approval switches and the expense / collection category lists. */
class FinanceSettingsController extends FinanceBaseController {

    public function index(): void {
        $this->guard(['finance.manage', 'settings.manage']);
        $cats = [];
        foreach (['expense', 'collection'] as $kind) {
            $table = $kind === 'expense' ? 'expenses' : 'incomes';
            $cats[$kind] = $this->db->fetchAll(
                "SELECT c.*, (SELECT COUNT(*) FROM {$table} t WHERE t.tenant_id=c.tenant_id AND t.category=c.name) AS uses
                 FROM finance_categories c WHERE c.tenant_id=? AND c.kind=? ORDER BY c.is_active DESC, c.name", [$this->tid, $kind]);
        }
        $this->financeView('finance_settings', ['pageTitle' => 'Finance Settings', 'cats' => $cats, 'types' => $this->studentTypes(false)]);
    }

    public function save(): void {
        $this->guard(['finance.manage', 'settings.manage']);
        $def = array_key_exists($_POST['default_currency'] ?? '', Finance::CURRENCIES) ? $_POST['default_currency'] : 'LRD';
        $sec = !empty($_POST['allow_secondary']) && array_key_exists($_POST['secondary_currency'] ?? '', Finance::CURRENCIES) && $_POST['secondary_currency'] !== $def
            ? $_POST['secondary_currency'] : '';
        Finance::saveSetting($this->db, $this->tid, 'default_currency', $def);
        Finance::saveSetting($this->db, $this->tid, 'secondary_currency', $sec);
        foreach (['payment_approval', 'expense_approval', 'payment_audit', 'daily_receipts', 'block_arrears_enrollment'] as $k) {
            Finance::saveSetting($this->db, $this->tid, $k, !empty($_POST[$k]) ? '1' : '0');
        }
        // Keep the school's general currency label (used on older screens) in step.
        $this->db->execute("UPDATE tenants SET currency=? WHERE id=?", [$def, $this->tid]);
        $this->flash('success', 'Finance settings saved.');
        $this->redirect('/school/finance/settings');
    }

    /** Add / rename / switch off categories; renaming also renames every past entry. */
    public function saveCategories(): void {
        $this->guard(['finance.manage', 'settings.manage']);
        $kind = ($_POST['kind'] ?? '') === 'collection' ? 'collection' : 'expense';
        $table = $kind === 'expense' ? 'expenses' : 'incomes';
        foreach ((array)($_POST['cats'] ?? []) as $id => $c) {
            $row = $this->db->fetchOne("SELECT * FROM finance_categories WHERE id=? AND tenant_id=? AND kind=?", [(int)$id, $this->tid, $kind]);
            if (!$row) { continue; }
            $name = mb_substr(trim((string)($c['name'] ?? '')), 0, 120);
            $active = !empty($c['active']) ? 1 : 0;
            if ($name !== '' && $name !== $row['name']) {
                if ($this->db->fetchOne("SELECT id FROM finance_categories WHERE tenant_id=? AND kind=? AND name=? AND id<>?", [$this->tid, $kind, $name, $id])) {
                    // Renaming onto an existing category is a merge.
                    $this->db->execute("UPDATE {$table} SET category=? WHERE tenant_id=? AND category=?", [$name, $this->tid, $row['name']]);
                    $this->db->execute("DELETE FROM finance_categories WHERE id=?", [$id]);
                    continue;
                }
                $this->db->execute("UPDATE {$table} SET category=? WHERE tenant_id=? AND category=?", [$name, $this->tid, $row['name']]);
                $this->db->execute("UPDATE budget_lines SET category=? WHERE tenant_id=? AND category=?", [$name, $this->tid, $row['name']]);
            }
            $this->db->execute("UPDATE finance_categories SET name=?, is_active=? WHERE id=?", [$name !== '' ? $name : $row['name'], $active, $id]);
        }
        foreach ((array)($_POST['new'] ?? []) as $n) {
            $n = trim((string)$n);
            if ($n !== '') { $this->db->execute("INSERT IGNORE INTO finance_categories (tenant_id,kind,name) VALUES (?,?,?)", [$this->tid, $kind, mb_substr($n, 0, 120)]); }
        }
        $this->flash('success', ucfirst($kind) . ' categories saved.');
        $this->redirect('/school/finance/settings#' . $kind);
    }

    /** Merge several categories into one (moves every entry that used them). */
    public function merge(): void {
        $this->guard(['finance.manage', 'settings.manage']);
        $kind = ($_POST['kind'] ?? '') === 'collection' ? 'collection' : 'expense';
        $table = $kind === 'expense' ? 'expenses' : 'incomes';
        $target = trim((string)($_POST['target'] ?? ''));
        $sources = array_filter(array_map('trim', (array)($_POST['sources'] ?? [])), fn($s) => $s !== '' && $s !== $target);
        if ($target === '' || !$sources) { $this->flash('danger', 'Tick the categories to merge and choose the one to keep.'); $this->redirect('/school/finance/settings#' . $kind); }
        $in = implode(',', array_fill(0, count($sources), '?'));
        $n = $this->db->execute("UPDATE {$table} SET category=? WHERE tenant_id=? AND category IN ($in)", array_merge([$target, $this->tid], $sources));
        $this->db->execute("DELETE FROM finance_categories WHERE tenant_id=? AND kind=? AND name IN ($in)", array_merge([$this->tid, $kind], $sources));
        $this->db->execute("INSERT IGNORE INTO finance_categories (tenant_id,kind,name) VALUES (?,?,?)", [$this->tid, $kind, $target]);
        $this->flash('success', "Merged into \"{$target}\" — {$n} record(s) moved.");
        $this->redirect('/school/finance/settings#' . $kind);
    }
}
