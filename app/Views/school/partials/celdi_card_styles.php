<?php
/**
 * Print styles for the CELDI report card, shared by the single-student view and
 * the class batch. Emitted once per document — never inside the per-student loop.
 */
?>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Times New Roman', Times, serif; background:#e5e7eb; padding:24px;
         display:flex; flex-direction:column; align-items:center; gap:24px; }

  .toolbar { display:flex; align-items:center; gap:12px; width:11.69in; max-width:100%; font-family:'Segoe UI',Arial,sans-serif; }
  .toolbar select, .toolbar button, .toolbar a.btn {
      padding:9px 16px; border-radius:8px; font-size:13px; font-family:inherit; border:1px solid #d1d5db;
      background:#fff; color:#111827; text-decoration:none; cursor:pointer; }
  .toolbar button { background:#4c1d95; color:#fff; border-color:#4c1d95; font-weight:600; }
  .toolbar .spacer { flex:1; }
  .notice { width:11.69in; max-width:100%; background:#fef3c7; border:1px solid #f59e0b; color:#78350f;
            padding:12px 16px; border-radius:8px; font-family:'Segoe UI',Arial,sans-serif; font-size:13px; }

  .sheet { width:11.69in; min-height:8.27in; background:#fff; box-shadow:0 8px 24px rgba(0,0,0,0.18);
           padding:0.4in 0.45in; position:relative; }

  /* ── Sheet 1 ───────────────────────────────────────────────────────────── */
  .face { display:grid; grid-template-columns:1fr 1fr; gap:0.4in; height:100%; }
  .panel { border:1.5px solid #111; padding:16px 18px; display:flex; flex-direction:column; }

  .panel h2 { font-size:15px; font-weight:700; text-align:center; letter-spacing:0.02em; margin-bottom:14px; }
  .certify { text-align:center; font-size:13px; margin-bottom:4px; }
  .student-name-script { text-align:center; font-size:20px; font-weight:700; margin:6px 0 12px; }
  .promo-body { font-size:13px; line-height:1.9; text-align:center; }
  .promo-opts { font-size:13px; line-height:2.1; margin:14px 0 0 6px; }
  .promo-opts .fill { display:inline-block; border-bottom:1px solid #111; min-width:150px; }
  .promo-opts .chosen { font-weight:700; }

  .sig-lines { margin-top:auto; padding-top:18px; }
  .sig-lines .line { border-bottom:1px solid #111; height:26px; }
  .sig-lines .cap { text-align:right; font-size:11px; letter-spacing:0.06em; margin:2px 0 14px; }

  .crest { text-align:center; margin-bottom:6px; }
  .crest img { height:74px; object-fit:contain; }
  .school-name { text-align:center; font-size:30px; font-weight:700; color:#5b21b6; letter-spacing:0.01em; line-height:1.1; }
  .school-meta { text-align:center; font-size:11px; line-height:1.5; margin-top:4px; }
  .doc-title { text-align:center; font-size:15px; font-weight:700; margin:12px 0 10px; letter-spacing:0.02em; }

  .ruled { border-bottom:1px solid #111; text-align:center; font-size:15px; font-weight:700; padding-bottom:2px; text-transform:uppercase; }
  .ruled-cap { text-align:center; font-size:10.5px; margin-top:2px; }
  .two-up { display:grid; grid-template-columns:1fr 1fr; gap:22px; margin-top:14px; }

  .guardians-title { text-align:center; font-size:13px; font-weight:700; margin:14px 0 4px; letter-spacing:0.02em; }
  .guardians-note { text-align:center; font-size:10.5px; margin-bottom:8px; }
  table.guardians { width:100%; border-collapse:collapse; }
  table.guardians th, table.guardians td { border:1px solid #111; font-size:11px; padding:5px 8px; }
  table.guardians th { font-weight:700; text-align:center; }
  table.guardians td.period { width:26%; }
  table.guardians td { height:24px; }

  /* ── Sheet 2 ───────────────────────────────────────────────────────────── */
  table.grid { width:100%; border-collapse:collapse; table-layout:fixed; }
  table.grid th, table.grid td { font-size:11px; text-align:center; padding:3px 2px; border:1px solid #111; }
  table.grid th { font-weight:700; }
  table.grid .subj { text-align:left; width:15%; padding-left:6px; font-size:11.5px; }
  /* The printed card is two bordered blocks with white space between them and a
     detached Yearly column. A borderless spacer column reproduces that gap while
     keeping every row in a single table, so subject rows can never drift apart. */
  table.grid .gap { border:none; width:14px; }
  table.grid .score { color:#1d4ed8; }
  table.grid .score.low { color:#dc2626; }
  table.grid .semave, table.grid .yearly { font-weight:700; }
  table.grid tr.foot td { font-weight:700; }
  table.grid tr.foot td.subj { text-align:left; }

  .method { margin-top:16px; text-align:center; }
  .method h3 { font-size:12px; font-weight:700; text-decoration:underline; letter-spacing:0.04em; margin-bottom:6px; }
  .method .bands { font-size:11px; line-height:1.7; }
  .method .bands span { margin:0 12px; white-space:nowrap; }

  @media print {
    body { background:#fff; padding:0; gap:0; display:block; }
    .toolbar, .notice { display:none; }
    .sheet { box-shadow:none; width:auto; min-height:auto; padding:0.3in 0.35in; page-break-after:always; }
    .sheet:last-of-type { page-break-after:auto; }
    @page { size:A4 landscape; margin:0.25in; }
  }
</style>
