<?php
/**
 * App shell (design-system layout-skeleton + examples/index.html).
 * Vars: $title (string), $content (page-header + main-content markup),
 *       $active (nav id, e.g. 'nav-dashboard'), $user (current user row).
 * Swap target: #page-content (.nxl-content) — partials carry .page-header + .main-content.
 */
$navItem = function (string $id, string $url, string $label) use ($active) {
    $cls = $active === $id ? ' active' : '';
    return '<li class="nxl-item' . $cls . '"><a class="nxl-link" id="' . e($id) . '" href="' . e($url) . '"'
        . ' hx-get="' . e($url) . '" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="' . e($url) . '">'
        . e($label) . '</a></li>';
};
?>
<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>" id="csrf-token-meta">
    <title><?= e(config('APP_NAME', 'Cert Arena')) ?> || <?= e($title) ?></title>
    <link rel="shortcut icon" type="image/png" href="/assets/images/favicon.png" />
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="/assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" type="text/css" href="/assets/css/theme.min.css" />
</head>

<body>
    <!--! [Start] Navigation !-->
    <nav class="nxl-navigation" id="left-sidenav">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="/" class="b-brand">
                    <img src="/assets/images/logo-full.png" alt="" class="logo logo-lg" />
                    <img src="/assets/images/logo-abbr.png" alt="" class="logo logo-sm" />
                </a>
            </div>
            <div class="navbar-content">
                <ul class="nxl-navbar">
                    <li class="nxl-item nxl-caption">
                        <label>Navigation</label>
                    </li>
                    <li class="nxl-item<?= $active === 'nav-dashboard' ? ' active' : '' ?>">
                        <a href="/" class="nxl-link" id="nav-dashboard"
                           hx-get="/" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/">
                            <span class="nxl-micon"><i class="feather-airplay"></i></span>
                            <span class="nxl-mtext">Dashboard</span>
                        </a>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link" id="nav-question-bank">
                            <span class="nxl-micon"><i class="feather-help-circle"></i></span>
                            <span class="nxl-mtext">Question Bank</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <?= $navItem('nav-question-list', '/questions/', 'Questions') ?>
                            <?= $navItem('nav-question-add', '/questions/new', 'Add Question') ?>
                            <?= $navItem('nav-question-import', '/questions/import', 'Import') ?>
                            <?= $navItem('nav-exam-list', '/exams/', 'Exams & Coverage') ?>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link" id="nav-game-night">
                            <span class="nxl-micon"><i class="feather-play"></i></span>
                            <span class="nxl-mtext">Game Night</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <?= $navItem('nav-game-new', '/games/new', 'New Game') ?>
                            <?= $navItem('nav-game-list', '/games/', 'Games') ?>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link" id="nav-analytics">
                            <span class="nxl-micon"><i class="feather-bar-chart-2"></i></span>
                            <span class="nxl-mtext">Analytics</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <?= $navItem('nav-analytics-group', '/analytics/', 'Group Readiness') ?>
                            <?= $navItem('nav-drill-list', '/analytics/drill', 'Drill List') ?>
                        </ul>
                    </li>
                    <li class="nxl-item<?= $active === 'nav-ama' ? ' active' : '' ?>">
                        <a href="/ama" class="nxl-link" id="nav-ama"
                           hx-get="/ama" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/ama">
                            <span class="nxl-micon"><i class="feather-message-circle"></i></span>
                            <span class="nxl-mtext">Ask Me Anything</span>
                        </a>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link" id="nav-settings">
                            <span class="nxl-micon"><i class="feather-settings"></i></span>
                            <span class="nxl-mtext">Settings</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <?= $navItem('nav-settings-profile', '/settings/profile', 'Profile') ?>
                            <?= $navItem('nav-settings-2fa', '/settings/2fa', 'Two-Factor Auth') ?>
                            <?= $navItem('nav-settings-mcp', '/settings/mcp', 'MCP Access') ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!--! [End] Navigation !-->
    <!--! [Start] Header !-->
    <header class="nxl-header" id="top-header">
        <div class="header-wrapper">
            <div class="header-left d-flex align-items-center gap-4">
                <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                    <div class="hamburger hamburger--arrowturn">
                        <div class="hamburger-box">
                            <div class="hamburger-inner"></div>
                        </div>
                    </div>
                </a>
                <div class="nxl-navigation-toggle">
                    <a href="javascript:void(0);" id="menu-mini-button">
                        <i class="feather-align-left"></i>
                    </a>
                    <a href="javascript:void(0);" id="menu-expend-button" style="display: none">
                        <i class="feather-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="header-right ms-auto">
                <div class="d-flex align-items-center">
                    <div class="nxl-h-item dark-light-theme">
                        <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button">
                            <i class="feather-moon"></i>
                        </a>
                        <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display: none">
                            <i class="feather-sun"></i>
                        </a>
                    </div>
                    <div class="dropdown nxl-h-item">
                        <a href="javascript:void(0);" data-bs-toggle="dropdown" role="button" data-bs-auto-close="outside" id="top-header-user-menu">
                            <img src="/assets/images/avatar/1.png" alt="user-image" class="img-fluid user-avtar me-0" />
                        </a>
                        <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-user-dropdown">
                            <div class="dropdown-header">
                                <div class="d-flex align-items-center">
                                    <img src="/assets/images/avatar/1.png" alt="user-image" class="img-fluid user-avtar" />
                                    <div>
                                        <h6 class="text-dark mb-0"><?= e($user['display_name']) ?>
                                            <?php if ($user['role'] === 'admin'): ?><span class="badge bg-soft-success text-success ms-1">ADMIN</span><?php endif; ?>
                                        </h6>
                                        <span class="fs-12 fw-medium text-muted"><?= e($user['email']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="/settings/profile" class="dropdown-item" id="top-header-profile-link">
                                <i class="feather-user"></i>
                                <span>Profile</span>
                            </a>
                            <a href="/settings/2fa" class="dropdown-item" id="top-header-2fa-link">
                                <i class="feather-shield"></i>
                                <span>Two-Factor Auth</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <form action="/logout" method="post" id="top-header-logout-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <button type="submit" class="dropdown-item" id="top-header-logout-btn">
                                    <i class="feather-log-out"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!--! [End] Header !-->
    <!--! [Start] Main !-->
    <main class="nxl-container">
        <div class="nxl-content" id="page-content">
<?= $content ?>
        </div>
        <footer class="footer pb-5" id="page-footer">
            <p class="fs-11 text-muted fw-medium text-uppercase mb-0 copyright">
                <span>Copyright © <?= date('Y') ?> — <?= e(config('APP_NAME', 'Cert Arena')) ?></span>
            </p>
        </footer>
    </main>
    <!--! [End] Main !-->
    <!--! [Start] Assistant command bar (chat-actions; stub handler until Phase 4) !-->
    <div id="assistant-bar" class="fixed-bottom bg-body border-top p-2">
        <div class="container-fluid">
            <div id="assistant-reply" class="fs-12 text-muted mb-1"></div>
            <form id="assistant-form"
                  hx-post="/assistant/message" hx-target="#assistant-reply" hx-swap="innerHTML"
                  hx-vals='js:{screen: document.querySelector("#page-content [data-screen]")?.dataset.screen || ""}'>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="feather-message-circle"></i></span>
                    <input type="text" name="message" id="assistant-input" class="form-control"
                           placeholder="Ask or tell the assistant… (Ctrl+K)" autocomplete="off">
                    <button type="submit" id="assistant-send-btn" class="btn btn-primary">
                        <i class="feather-send"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!--! [End] Assistant command bar !-->
    <script src="/assets/vendors/js/htmx.min.js"></script>
    <script>
        // CSRF on every non-GET HTMX request (php-session-auth wiring).
        document.body.addEventListener('htmx:configRequest', (e) => {
            if (e.detail.verb !== 'get') {
                e.detail.headers['X-CSRF-Token'] =
                    document.querySelector('#csrf-token-meta').content;
            }
        });
        // Per-screen re-initialisation after a swap (design-system rule).
        document.body.addEventListener('htmx:afterSwap', function (evt) {
            if (evt.detail.target.id !== 'page-content') return;
            evt.detail.target.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                new bootstrap.Tooltip(el);
            });
        });
        // Command bar: focus fast (dictation types into the focused field).
        document.addEventListener('keydown', function (e) {
            const input = document.getElementById('assistant-input');
            if ((e.key === 'k' && (e.ctrlKey || e.metaKey)) ||
                (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName))) {
                e.preventDefault();
                input.focus();
            }
        });
        document.getElementById('assistant-form').addEventListener('htmx:afterRequest', function () {
            const input = document.getElementById('assistant-input');
            input.value = '';
            input.focus();
        });
    </script>
    <script src="/assets/vendors/js/vendors.min.js"></script>
    <script src="/assets/js/common-init.min.js"></script>
    <script src="/assets/js/theme-customizer-init.min.js"></script>
</body>

</html>
