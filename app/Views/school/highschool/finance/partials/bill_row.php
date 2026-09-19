<?php // One editable bill row. Expects $b (bill), $i (row key), $types, $currencies. ?>
<tr>
  <td>
    <input type="hidden" name="bills[<?= $i ?>][id]" value="<?= htmlspecialchars((string)$b['id']) ?>">
    <input type="text" name="bills[<?= $i ?>][description]" class="form-control" maxlength="150" value="<?= htmlspecialchars($b['description']) ?>" placeholder="e.g. Tuition: 2nd Installment" list="descList">
  </td>
  <td><input type="number" name="bills[<?= $i ?>][amount]" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars((string)$b['amount']) ?>" style="text-align:right;"></td>
  <td>
    <select name="bills[<?= $i ?>][currency]" class="form-control">
      <?php foreach ($currencies as $cur): ?><option value="<?= $cur ?>" <?= $b['currency'] === $cur ? 'selected' : '' ?>><?= $cur ?></option><?php endforeach; ?>
    </select>
  </td>
  <td><input type="date" name="bills[<?= $i ?>][start_date]" class="form-control" value="<?= htmlspecialchars((string)$b['start_date']) ?>"></td>
  <td><input type="date" name="bills[<?= $i ?>][end_date]" class="form-control" value="<?= htmlspecialchars((string)$b['end_date']) ?>"></td>
  <td>
    <details class="type-pick">
      <summary>All types</summary>
      <div class="type-menu">
        <label><input type="checkbox" name="bills[<?= $i ?>][types][]" value="all" <?= $b['applies_all'] ? 'checked' : '' ?>> <strong>All types</strong></label>
        <?php foreach ($types as $t): if (!$t['is_active'] && !in_array((int)$t['id'], $b['type_ids'], true)) continue; ?>
          <label><input type="checkbox" name="bills[<?= $i ?>][types][]" value="<?= $t['id'] ?>" data-name="<?= htmlspecialchars($t['name']) ?>" <?= !$b['applies_all'] && in_array((int)$t['id'], $b['type_ids'], true) ? 'checked' : '' ?>> <?= htmlspecialchars($t['name']) ?></label>
        <?php endforeach; ?>
      </div>
    </details>
  </td>
  <td style="text-align:center;"><input type="checkbox" name="bills[<?= $i ?>][once_per_year]" value="1" <?= $b['once_per_year'] ? 'checked' : '' ?> title="Charged once per year even if the student changes class"></td>
  <td><button type="button" class="btn btn-sm btn-danger" title="Remove (takes effect when you save)" onclick="this.closest('tr').remove()">✕</button></td>
</tr>
