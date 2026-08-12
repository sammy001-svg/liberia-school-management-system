-- ============================================================
-- Budget module: allow an "Other" line type alongside income and expense,
-- for contingency, capital projects and miscellaneous planned items that
-- don't sit naturally on either side.
--
-- Other lines draw their actual from recorded expenses (matched on
-- category, like expense lines) and count toward outgoings in the net,
-- but are grouped and totalled separately so they stay visible.
--
-- Run AFTER add_budget_module.sql.
-- ============================================================

ALTER TABLE budget_lines
    MODIFY line_type ENUM('income','expense','other') NOT NULL;
