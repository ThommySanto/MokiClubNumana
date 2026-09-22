<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/security_utils.php';
if (!isset($pageTitle)) {
    $pageTitle = "Moki SUP Club";
}

app_start_secure_session();
$csrfToken = app_csrf_token();

function app_asset_url(string $assetPath): string
{
    $normalizedPath = '/' . ltrim($assetPath, '/');
    $absolutePath = dirname(__DIR__) . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);

    if (is_file($absolutePath)) {
        return $normalizedPath . '?v=' . (string) filemtime($absolutePath);
    }

    return $normalizedPath;
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <?php if (!empty($seoMeta) && is_array($seoMeta)): ?>
    <!-- ═══ SEO META ═══ -->
    <?php if (!empty($seoMeta['description'])): ?>
    <meta name="description" content="<?php echo htmlspecialchars($seoMeta['description'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if (!empty($seoMeta['keywords'])): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($seoMeta['keywords'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <meta name="robots" content="index, follow">
    <meta name="author" content="Moki Club Numana">

    <!-- ═══ CANONICAL ═══ -->
    <?php if (!empty($seoMeta['canonical'])): ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($seoMeta['canonical'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <!-- ═══ OPEN GRAPH (Facebook, WhatsApp, LinkedIn) ═══ -->
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="Moki Club Numana">
    <meta property="og:title"       content="<?php echo htmlspecialchars($seoMeta['og_title'] ?? $pageTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seoMeta['og_description'] ?? $seoMeta['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url"         content="<?php echo htmlspecialchars($seoMeta['canonical'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image"       content="<?php echo htmlspecialchars($seoMeta['og_image'] ?? 'https://mokiclub.infinityfreeapp.com/assets/img/sfondo.jpg', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:locale"      content="it_IT">

    <!-- ═══ TWITTER CARD ═══ -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?php echo htmlspecialchars($seoMeta['og_title'] ?? $pageTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seoMeta['og_description'] ?? $seoMeta['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image"       content="<?php echo htmlspecialchars($seoMeta['og_image'] ?? 'https://mokiclub.infinityfreeapp.com/assets/img/sfondo.jpg', ENT_QUOTES, 'UTF-8'); ?>">

    <!-- ═══ SCHEMA.ORG JSON-LD (SportsClub) ═══ -->
    <?php if (!empty($seoMeta['schema'])): ?>
    <script type="application/ld+json">
    <?php echo $seoMeta['schema']; ?>
    </script>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="/assets/img/moki.jpg">

    <!-- CSS base -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('/assets/css/style.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('/assets/css/neumorphism.css'), ENT_QUOTES, 'UTF-8'); ?>">

    <!-- CSS legale e cookie banner (sempre incluso) -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('/assets/css/legal-pages.css'), ENT_QUOTES, 'UTF-8'); ?>">

    <!-- CSS mobile responsive (sempre incluso) -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('/assets/css/mobile.css'), ENT_QUOTES, 'UTF-8'); ?>">

    <?php if (!empty($extraCssFiles) && is_array($extraCssFiles)): ?>
        <?php foreach ($extraCssFiles as $cssFile): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url((string) $cssFile), ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>
    <?php endif; ?>

</head>

<?php $bodyAttributes = $bodyAttributes ?? ''; ?>
<body<?php echo $bodyAttributes !== '' ? ' ' . $bodyAttributes : ''; ?>>

    <!-- COOKIE BANNER -->
    <?php include __DIR__ . '/cookie-banner.php'; ?>

    <header class="site-header">
        <div class="header-container">

            <!-- LOGO -->
            <div class="logo-area">
                <a href="/">
                    <img src="/assets/img/moki.jpg" class="logo" alt="Moki Club Numana">
                    <span class="site-name">Moki Club Numana</span>
                </a>
            </div>

            <!-- NAV DESKTOP (visibile solo sopra 768px) -->
            <nav class="nav-desktop">
                <?php if (isset($_SESSION["admin"])): ?>
                    <a href="/admin/dashboard.php" class="admin-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                        Dashboard
                    </a>
                    <a href="/admin/logout.php" class="admin-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Logout
                    </a>
                <?php else: ?>
                    <a href="/admin/login.php" class="admin-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Gestione Admin
                    </a>
                <?php endif; ?>
            </nav>

            <!-- HAMBURGER BUTTON (visibile solo sotto 768px) -->
            <button
                id="nav-hamburger"
                class="nav-hamburger"
                aria-label="Apri menu"
                aria-expanded="false"
                aria-controls="nav-drawer"
                type="button">
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
            </button>

        </div>
    </header>

    <!-- OVERLAY scuro dietro il drawer -->
    <div id="nav-overlay" class="nav-overlay" aria-hidden="true"></div>

    <!-- DRAWER MOBILE -->
    <div id="nav-drawer" class="nav-drawer" role="dialog" aria-label="Menu navigazione">
        <div class="nav-drawer-inner">
            <?php if (isset($_SESSION["admin"])): ?>
                <a href="/admin/dashboard.php" class="nav-drawer-link">
                    <span class="nav-drawer-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                    </span>
                    <span>Dashboard</span>
                </a>
                <a href="/admin/logout.php" class="nav-drawer-link nav-drawer-link--danger">
                    <span class="nav-drawer-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </span>
                    <span>Logout</span>
                </a>
            <?php else: ?>
                <a href="/admin/login.php" class="nav-drawer-link">
                    <span class="nav-drawer-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>
                    <span>Gestione Admin</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
