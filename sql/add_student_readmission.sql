-- ============================================================
-- Re-admission of existing ("old") students.
--
-- Re-admitting promotes a student into a new class and issues a fresh
-- admission number on the SAME students row, so grades, attendance,
-- invoices and the fee ledger all stay attached. The number they held
-- before is kept here so the old one can still be traced.
-- ============================================================

ALTER TABLE students
    ADD COLUMN previous_admission_no VARCHAR(50) DEFAULT NULL;
