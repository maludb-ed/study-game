<?php /* Ask Me Anything page. Vars: $history (list of ['question','answer']). */ ?>
<!-- [ page-header ] start -->
<div class="page-header" id="ama-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Ask Me Anything</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item">Ask Me Anything</li>
        </ul>
    </div>
</div>
<!-- [ page-header ] end -->
<!-- [ Main Content ] start -->
<div class="main-content" id="ama-content" data-screen="ama">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Your study group's memory</h5>
                </div>
                <div class="card-body">
                    <p class="fs-12 text-muted" id="ama-hint">
                        Ask about the records ("who's closest to 720 on CCAO-F?") or the activity
                        ("when did we last play?"). The assistant reads the same MCP servers your
                        own AI tools can connect to from <a href="/settings/mcp"
                        hx-get="/settings/mcp" hx-target="#page-content" hx-swap="innerHTML"
                        hx-push-url="/settings/mcp">MCP Access</a>.
                    </p>
                    <div id="ama-thread">
                        <?php foreach ($history as $exchange): ?>
                            <?= view('ama/partials/exchange.php', $exchange) ?>
                        <?php endforeach; ?>
                    </div>
                    <form id="ama-form" action="/ama/ask" method="post"
                          hx-post="/ama/ask" hx-target="#ama-thread" hx-swap="beforeend"
                          hx-indicator="#ama-indicator" hx-on::after-request="this.reset(); document.getElementById('ama-field-question').focus()">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <div class="input-group mt-3">
                            <input type="text" name="question" id="ama-field-question" class="form-control"
                                   placeholder="Ask anything about the group's study record…" maxlength="2000" required autofocus>
                            <button type="submit" id="ama-send-btn" class="btn btn-primary">
                                <i class="feather-send"></i>
                            </button>
                        </div>
                        <div id="ama-indicator" class="htmx-indicator fs-12 text-muted mt-2">
                            <i class="feather-loader me-1"></i>Reading the memories…
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->
