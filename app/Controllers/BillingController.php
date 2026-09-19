<?php
require_once ROOT_DIR . '/app/Controllers/FinanceBaseController.php';

/**
 * Billing Setup: what each class pays in an academic year, split by new/old students and
 * by student type (Regular, Ward, Scholarship…), usually as dated installments.
 *
 * Saving always re-syncs the enrolled students' own copies of the bills (their invoices),
 * so a price change reaches every unpaid bill and the ledger records the difference.
 */
class BillingController extends FinanceBaseController {

    public function index(): void {
        $this->guard(['finance.manage']);
        $year = $this->selectedYear();
        $classes = $this->classes();
        $classId = (int)($_GET['class'] ?? 0);
        $class = null;
        foreach ($classes as $c) { if ((int)$c['id'] === $classId) { $class = $c; } }

        $bills = ['new' => [], 'old' => []];
        $typeTotals = [];
        $types = $this->studentTypes(false);
        $typeNames = array_column($types, 'name', 'id');
        if ($year && $class) {
            $rows = $this->db->fetchAll(
                "SELECT b.*, GROUP_CONCAT(t.student_type_id) type_ids FROM fee_bills b LEFT JOIN fee_bill_types t ON t.bill_id=b.id
                 WHERE b.tenant_id=? AND b.academic_year_id=? AND b.class_id=? GROUP BY b.id ORDER BY b.category, b.sort_order, b.start_date, b.id",
                [$this->tid, $year['id'], $class['id']]
            );
            foreach ($rows as $r) {
                $r['type_ids'] = $r['type_ids'] ? array_map('intval', explode(',', $r['type_ids'])) : [];
                $bills[$r['category']][] = $r;
            }
            // What a student of each type owes for the year, per category and currency.
            foreach (['new', 'old'] as $cat) {
                foreach ($types as $t) {
                    foreach ($bills[$cat] as $b) {
                        if ($b['applies_all'] || in_array((int)$t['id'], $b['type_ids'], true)) {
                            $typeTotals[$t['name']][$cat][$b['currency']] = ($typeTotals[$t['name']][$cat][$b['currency']] ?? 0) + (float)$b['amount'];
                        }
                    }
                }
            }
        }
        $descriptions = $year ? array_column($this->db->fetchAll(
            "SELECT description, COUNT(*) n FROM fee_bills WHERE tenant_id=? AND academic_year_id=? GROUP BY description ORDER BY description",
            [$this->tid, $year['id']]), 'n', 'description') : [];
        $enrolled = ($year && $class) ? (int)($this->db->fetchOne(
            "SELECT COUNT(*) c FROM enrollments WHERE tenant_id=? AND academic_year_id=? AND class_id=? AND status='active'",
            [$this->tid, $year['id'], $class['id']])['c'] ?? 0) : 0;

        $this->financeView('billing', [
            'pageTitle' => 'Billing Setup', 'year' => $year, 'years' => $this->years(), 'classes' => $classes,
            'class' => $class, 'bills' => $bills, 'types' => $types, 'typeNames' => $typeNames,
            'typeTotals' => $typeTotals, 'descriptions' => $descriptions, 'enrolled' => $enrolled,
        ]);
    }

    private function back(int $yearId, int $classId = 0, string $tab = ''): never {
        $this->redirect('/school/finance/billing?year=' . $yearId . ($classId ? '&class=' . $classId : '') . ($tab ? '&tab=' . $tab : ''));
    }

    /** Saves every bill of one class+year+category in one go (the table on screen). */
    public function save(): void {
        $this->guard(['finance.manage']);
        $yearId = (int)($_POST['academic_year_id'] ?? 0);
        $classId = (int)($_POST['class_id'] ?? 0);
        $category = ($_POST['category'] ?? 'new') === 'old' ? 'old' : 'new';
        if (!$this->db->fetchOne("SELECT id FROM academic_years WHERE id=? AND tenant_id=?", [$yearId, $this->tid])
            || !$this->db->fetchOne("SELECT id FROM classes WHERE id=? AND tenant_id=?", [$classId, $this->tid])) {
            $this->flash('danger', 'Choose an academic year and class first.');
            $this->redirect('/school/finance/billing');
        }
        $validTypes = array_map('intval', array_column($this->studentTypes(false), 'id'));
        $rows = $_POST['bills'] ?? [];
        $keep = [];
        $errors = [];
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            foreach (array_values($rows) as $i => $r) {
                $desc = trim((string)($r['description'] ?? ''));
                if ($desc === '' && trim((string)($r['amount'] ?? '')) === '') { continue; }
                if ($desc === '') { $errors[] = 'Row ' . ($i + 1) . ': a description is required.'; continue; }
                $amount = (float)($r['amount'] ?? 0);
                if ($amount < 0) { $errors[] = "\"{$desc}\": the amount can't be negative."; continue; }
                $start = ($r['start_date'] ?? '') ?: null;
                $end = ($r['end_date'] ?? '') ?: null;
                if ($start && $end && $end < $start) { $errors[] = "\"{$desc}\": the end date is before the start date."; continue; }
                $typeIds = array_values(array_intersect(array_map('intval', (array)($r['types'] ?? [])), $validTypes));
                $all = in_array('all', (array)($r['types'] ?? []), true) || !$typeIds ? 1 : 0;
                $data = [mb_substr($desc, 0, 150), $amount, $this->currencyOrDefault($r['currency'] ?? null), $start, $end, $all, !empty($r['once_per_year']) ? 1 : 0, $i];
                $id = (int)($r['id'] ?? 0);
                if ($id && $this->db->fetchOne("SELECT id FROM fee_bills WHERE id=? AND tenant_id=? AND academic_year_id=? AND class_id=? AND category=?", [$id, $this->tid, $yearId, $classId, $category])) {
                    $this->db->execute("UPDATE fee_bills SET description=?, amount=?, currency=?, start_date=?, end_date=?, applies_all=?, once_per_year=?, sort_order=? WHERE id=?", array_merge($data, [$id]));
                } else {
                    $id = (int)$this->db->insert("INSERT INTO fee_bills (description, amount, currency, start_date, end_date, applies_all, once_per_year, sort_order, tenant_id, academic_year_id, class_id, category)
                                                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?)", array_merge($data, [$this->tid, $yearId, $classId, $category]));
                }
                $this->db->execute("DELETE FROM fee_bill_types WHERE bill_id=?", [$id]);
                if (!$all) {
                    foreach ($typeIds as $t) { $this->db->execute("INSERT INTO fee_bill_types (bill_id, student_type_id) VALUES (?,?)", [$id, $t]); }
                }
                $keep[] = $id;
            }
            if ($errors) { throw new \DomainException(implode(' ', $errors)); }
            // Rows removed on screen are deleted; students' paid copies survive the sync.
            $params = [$this->tid, $yearId, $classId, $category];
            $notIn = $keep ? ' AND id NOT IN (' . implode(',', array_map('intval', $keep)) . ')' : '';
            $this->db->execute("DELETE FROM fee_bills WHERE tenant_id=? AND academic_year_id=? AND class_id=? AND category=?{$notIn}", $params);
            $synced = Finance::syncClass($this->db, $this->tid, $yearId, $classId);
            $pdo->commit();
        } catch (\DomainException $e) {
            $pdo->rollBack();
            $this->flash('danger', $e->getMessage());
            $this->back($yearId, $classId, $category);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        $this->flash('success', 'Billing saved' . ($synced ? " — {$synced} enrolled student(s) updated to the new bills." : '.'));
        $this->back($yearId, $classId, $category);
    }

    /** Copies one category (or the whole class) to other classes/categories in the same year. */
    public function copy(): void {
        $this->guard(['finance.manage']);
        $yearId = (int)($_POST['academic_year_id'] ?? 0);
        $fromClass = (int)($_POST['class_id'] ?? 0);
        $fromCat = ($_POST['category'] ?? 'new') === 'old' ? 'old' : 'new';
        $scope = ($_POST['scope'] ?? 'category') === 'class' ? 'class' : 'category';
        $targetCat = ($_POST['target_category'] ?? $fromCat) === 'old' ? 'old' : 'new';
        $targets = array_map('intval', (array)($_POST['target_classes'] ?? []));
        $replace = !empty($_POST['replace']);
        $validClasses = array_map('intval', array_column($this->classes(), 'id'));
        $targets = array_values(array_intersect($targets, $validClasses));
        if (!$targets) { $this->flash('danger', 'Choose at least one class to copy to.'); $this->back($yearId, $fromClass, $fromCat); }

        $cats = $scope === 'class' ? ['new' => 'new', 'old' => 'old'] : [$fromCat => $targetCat];
        $copied = 0;
        foreach ($targets as $to) {
            foreach ($cats as $src => $dst) {
                if ($to === $fromClass && $src === $dst) { continue; }
                $copied += $this->copyBills($yearId, $fromClass, $src, $yearId, $to, $dst, $replace, 0);
            }
            Finance::syncClass($this->db, $this->tid, $yearId, $to);
        }
        $this->flash('success', "Copied {$copied} bill(s) to " . count($targets) . ' class(es).');
        $this->back($yearId, $fromClass, $fromCat);
    }

    /** Copies every class's bills from an earlier year, moving the dates forward by the gap between the years. */
    public function carryForward(): void {
        $this->guard(['finance.manage']);
        $toYear = $this->db->fetchOne("SELECT * FROM academic_years WHERE id=? AND tenant_id=?", [(int)($_POST['academic_year_id'] ?? 0), $this->tid]);
        $fromYear = $this->db->fetchOne("SELECT * FROM academic_years WHERE id=? AND tenant_id=?", [(int)($_POST['from_year_id'] ?? 0), $this->tid]);
        if (!$toYear || !$fromYear || $toYear['id'] === $fromYear['id']) {
            $this->flash('danger', 'Choose a different year to copy the bills from.');
            $this->redirect('/school/finance/billing');
        }
        $shiftDays = (int)round((strtotime($toYear['start_date']) - strtotime($fromYear['start_date'])) / 86400);
        $copied = 0;
        $classIds = array_column($this->db->fetchAll("SELECT DISTINCT class_id FROM fee_bills WHERE tenant_id=? AND academic_year_id=?", [$this->tid, $fromYear['id']]), 'class_id');
        foreach ($classIds as $cid) {
            foreach (['new', 'old'] as $cat) {
                $copied += $this->copyBills((int)$fromYear['id'], (int)$cid, $cat, (int)$toYear['id'], (int)$cid, $cat, false, $shiftDays);
            }
            Finance::syncClass($this->db, $this->tid, (int)$toYear['id'], (int)$cid);
        }
        $this->flash('success', "Carried forward {$copied} bill(s) from {$fromYear['name']} into {$toYear['name']}. Review the amounts and dates for each class.");
        $this->redirect('/school/finance/billing?year=' . $toYear['id']);
    }

    private function copyBills(int $fromYear, int $fromClass, string $fromCat, int $toYear, int $toClass, string $toCat, bool $replace, int $shiftDays): int {
        $src = $this->db->fetchAll("SELECT * FROM fee_bills WHERE tenant_id=? AND academic_year_id=? AND class_id=? AND category=? ORDER BY sort_order, id",
            [$this->tid, $fromYear, $fromClass, $fromCat]);
        if ($replace) {
            $this->db->execute("DELETE FROM fee_bills WHERE tenant_id=? AND academic_year_id=? AND class_id=? AND category=?", [$this->tid, $toYear, $toClass, $toCat]);
        }
        $existing = array_column($this->db->fetchAll("SELECT description FROM fee_bills WHERE tenant_id=? AND academic_year_id=? AND class_id=? AND category=?",
            [$this->tid, $toYear, $toClass, $toCat]), 'description');
        $shift = fn($d) => $d ? date('Y-m-d', strtotime("{$d} {$shiftDays} days")) : null;
        $n = 0;
        foreach ($src as $b) {
            if (in_array($b['description'], $existing, true)) { continue; }
            $newId = (int)$this->db->insert(
                "INSERT INTO fee_bills (tenant_id,academic_year_id,class_id,category,description,amount,currency,start_date,end_date,applies_all,once_per_year,sort_order)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                [$this->tid, $toYear, $toClass, $toCat, $b['description'], $b['amount'], $b['currency'], $shift($b['start_date']), $shift($b['end_date']),
                 $b['applies_all'], $b['once_per_year'], $b['sort_order']]
            );
            $this->db->execute("INSERT INTO fee_bill_types (bill_id, student_type_id) SELECT ?, student_type_id FROM fee_bill_types WHERE bill_id=?", [$newId, $b['id']]);
            $n++;
        }
        return $n;
    }

    /** Renames several bill descriptions to one (e.g. merging "1st Inst." and "First Installment"). */
    public function mergeDescriptions(): void {
        $this->guard(['finance.manage']);
        $yearId = (int)($_POST['academic_year_id'] ?? 0);
        $target = trim((string)($_POST['target'] ?? ''));
        $sources = array_filter(array_map('trim', (array)($_POST['sources'] ?? [])), 'strlen');
        if ($target === '' || !$sources) {
            $this->flash('danger', 'Tick the descriptions to merge and enter the name to keep.');
            $this->back($yearId, (int)($_POST['class_id'] ?? 0));
        }
        $in = implode(',', array_fill(0, count($sources), '?'));
        $classes = array_column($this->db->fetchAll("SELECT DISTINCT class_id FROM fee_bills WHERE tenant_id=? AND academic_year_id=? AND description IN ($in)",
            array_merge([$this->tid, $yearId], $sources)), 'class_id');
        $n = $this->db->execute("UPDATE fee_bills SET description=? WHERE tenant_id=? AND academic_year_id=? AND description IN ($in)",
            array_merge([mb_substr($target, 0, 150), $this->tid, $yearId], $sources));
        foreach ($classes as $cid) { Finance::syncClass($this->db, $this->tid, $yearId, (int)$cid); }
        $this->flash('success', "{$n} bill(s) now use the description \"{$target}\".");
        $this->back($yearId, (int)($_POST['class_id'] ?? 0));
    }

    /** Adds, renames and switches off student types. */
    public function saveTypes(): void {
        $this->guard(['finance.manage']);
        foreach ((array)($_POST['types'] ?? []) as $id => $t) {
            $name = trim((string)($t['name'] ?? ''));
            if ($name === '') { continue; }
            $this->db->execute("UPDATE student_types SET name=?, is_active=? WHERE id=? AND tenant_id=?",
                [mb_substr($name, 0, 80), !empty($t['active']) ? 1 : 0, (int)$id, $this->tid]);
        }
        foreach ((array)($_POST['new_types'] ?? []) as $name) {
            $name = trim((string)$name);
            if ($name !== '') {
                $this->db->execute("INSERT IGNORE INTO student_types (tenant_id, name, sort_order) VALUES (?,?,100)", [$this->tid, mb_substr($name, 0, 80)]);
            }
        }
        $this->flash('success', 'Student types saved.');
        $this->back((int)($_POST['academic_year_id'] ?? 0), (int)($_POST['class_id'] ?? 0));
    }
}
