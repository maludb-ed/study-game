<?php
/**
 * MCP Access screen (SaaS Plus+): the two read-server URLs + bearer-token management.
 * Vars: $tokens, $createdToken (?array ['row','plaintext'] — shown once), $user,
 *       $recordsUrl, $activityUrl, $errors.
 */
?>
<!-- [ page-header ] start -->
<div class="page-header" id="settings-mcp-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">MCP Access</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item">Settings</li>
            <li class="breadcrumb-item">MCP Access</li>
        </ul>
    </div>
</div>
<!-- [ page-header ] end -->
<!-- [ Main Content ] start -->
<div class="main-content" id="settings-mcp-content" data-screen="settings-mcp">
    <div class="row">
        <div class="col-lg-7">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Connect your own AI tools</h5>
                </div>
                <div class="card-body">
                    <p class="fs-12 text-muted">
                        Your study group's memories are yours. Point Claude Desktop, Claude Code, or any
                        MCP client at these read-only endpoints with a bearer token from this page —
                        then ask your own Claude "am I ready to book CCAO-F?" from anywhere.
                    </p>
                    <table class="table table-sm" id="settings-mcp-endpoints">
                        <tbody>
                            <tr>
                                <td class="text-muted">Record memory</td>
                                <td><code id="settings-mcp-records-url"><?= e($recordsUrl) ?></code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Activity memory</td>
                                <td><code id="settings-mcp-activity-url"><?= e($activityUrl) ?></code></td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="fs-11 text-muted mb-0">
                        Claude Code: <code>claude mcp add --transport http cert-arena-records &lt;records URL&gt; --header "Authorization: Bearer &lt;token&gt;"</code>
                    </p>
                </div>
            </div>
            <?php if ($createdToken !== null): ?>
                <div class="alert alert-success" id="settings-mcp-created">
                    <h6 class="fs-13 fw-bold"><i class="feather-key me-2"></i>Token created — copy it now</h6>
                    <p class="fs-12 mb-1">This is the only time it will be shown:</p>
                    <code class="fs-12 user-select-all" id="settings-mcp-created-token"><?= e($createdToken['plaintext']) ?></code>
                </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger fs-12" id="settings-mcp-errors">
                    <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">New token</h5>
                </div>
                <div class="card-body">
                    <form id="settings-mcp-create-form" action="/settings/mcp/save" method="post"
                          hx-post="/settings/mcp/save" hx-target="#page-content" hx-swap="innerHTML"
                          class="d-flex flex-wrap gap-2 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <div>
                            <label class="form-label fs-12" for="settings-mcp-field-label">Label</label>
                            <input type="text" name="label" id="settings-mcp-field-label" class="form-control"
                                   placeholder="Ed — Claude Desktop" maxlength="80" minlength="3" required>
                        </div>
                        <div>
                            <label class="form-label fs-12" for="settings-mcp-field-server">Scope</label>
                            <select name="server" id="settings-mcp-field-server" class="form-select">
                                <option value="both">Records + Activity</option>
                                <option value="records">Records only</option>
                                <option value="activity">Activity only</option>
                            </select>
                        </div>
                        <button type="submit" id="settings-mcp-create-btn" class="btn btn-primary">
                            <i class="feather-plus me-2"></i>Create Token
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Tokens</h5>
                </div>
                <div class="card-body custom-card-action p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="settings-mcp-tokens-table">
                            <thead class="thead-light">
                                <tr><th>Label</th><th>Scope</th><th>Last used</th><th class="text-end">Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php if ($tokens === []): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">No tokens yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($tokens as $token): ?>
                                    <tr id="settings-mcp-token-row-<?= e($token['id']) ?>" <?= $token['revoked_at'] !== null ? 'class="opacity-50"' : '' ?>>
                                        <td>
                                            <?= e($token['label']) ?>
                                            <small class="text-muted d-block"><?= e($token['created_by_name'] ?? '—') ?></small>
                                        </td>
                                        <td><span class="badge bg-soft-primary text-primary"><?= e($token['server']) ?></span></td>
                                        <td>
                                            <?php if ($token['revoked_at'] !== null): ?>
                                                <span class="badge bg-soft-secondary text-secondary">Revoked</span>
                                            <?php else: ?>
                                                <small class="text-muted"><?= e($token['last_used_at'] !== null ? fmt_datetime($token['last_used_at']) : 'never') ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($token['revoked_at'] === null && ($user['role'] === 'admin' || (int) $token['created_by'] === (int) $user['id'])): ?>
                                                <button type="button" id="settings-mcp-token-row-<?= e($token['id']) ?>-revoke-btn"
                                                        class="btn btn-sm btn-light-brand text-danger"
                                                        hx-post="/settings/mcp/revoke" hx-vals='{"id": <?= (int) $token['id'] ?>}'
                                                        hx-target="#page-content" hx-swap="innerHTML"
                                                        hx-confirm="Revoke '<?= e($token['label']) ?>'? Clients using it will lose access.">
                                                    Revoke
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->
