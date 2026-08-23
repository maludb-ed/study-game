<?php
/**
 * The question form's exam-dependent rows (S6): domain select + scenario select.
 * Swapped whole via /questions/dependent-fields when the exam changes.
 * Vars: $domains, $scenarios, $domainSelected, $scenarioSelected.
 */
?>
<div id="question-form-field-dependent">
    <div class="mb-4 row" id="question-form-field-domain-row">
        <label class="col-lg-4 col-form-label" id="question-form-field-domain-label" for="question-form-field-domain">Domain</label>
        <div class="col-lg-8">
            <select name="domain_id" id="question-form-field-domain" class="form-select" required>
                <option value=""><?= $domains === [] ? 'Choose the exam first…' : 'Choose a domain…' ?></option>
                <?php foreach ($domains as $domain): ?>
                    <option value="<?= e($domain['id']) ?>" <?= (int) $domainSelected === (int) $domain['id'] ? 'selected' : '' ?>>
                        <?= e($domain['name']) ?> (<?= e($domain['weight_pct']) ?>%)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mb-4 row" id="question-form-field-scenario-row">
        <label class="col-lg-4 col-form-label" for="question-form-field-scenario">Scenario
            <small class="text-muted d-block">Optional — links this question to a scenario round</small></label>
        <div class="col-lg-8">
            <select name="scenario_id" id="question-form-field-scenario" class="form-select">
                <option value="">No scenario</option>
                <?php foreach ($scenarios as $scenario): ?>
                    <option value="<?= e($scenario['id']) ?>" <?= (int) $scenarioSelected === (int) $scenario['id'] ? 'selected' : '' ?>>
                        <?= e($scenario['title']) ?><?= $scenario['status'] === 'draft' ? ' (draft)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
