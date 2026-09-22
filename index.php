<?php
require_once __DIR__ . '/security_headers.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/eventi_functions.php';

$eventiFuturi = $conn->query(
    "SELECT * FROM eventi WHERE stato = 'pubblicato' AND (data_evento IS NULL OR data_evento >= CURDATE()) ORDER BY data_evento ASC"
)->fetch_all(MYSQLI_ASSOC);

$eventiPassati = $conn->query(
    "SELECT * FROM eventi WHERE stato = 'pubblicato' AND data_evento IS NOT NULL AND data_evento < CURDATE() ORDER BY data_evento DESC LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

$extraCssFiles = ['/assets/css/home-index.css', '/assets/css/eventi.css'];
$pageTitle = "Moki Club Numana — SUP, Windsurf, Surf & Foil a Numana";
$seoMeta = [
    'description'    => 'Moki Club Numana: centro sportivo water sports sul mare di Numana. SUP, Windsurf, Surf e Foil. Iscriviti al club o prenota il rimessaggio della tua tavola.',
    'keywords'       => 'moki club numana, sup numana, windsurf numana, surf numana, foil numana, water sports numana, club sportivo numana, rimessaggio tavola numana, iscrizione sup marche',
    'canonical'      => 'https://mokiclub.infinityfreeapp.com/',
    'og_title'       => 'Moki Club Numana — SUP, Windsurf, Surf & Foil',
    'og_description' => 'Centro water sports a Numana. Iscriviti al club o prenota il rimessaggio della tua tavola. SUP, Windsurf, Surf e Foil sul mare delle Marche.',
    'og_image'       => 'https://mokiclub.infinityfreeapp.com/assets/img/sfondo.jpg',
    'schema'         => json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'SportsClub',
        'name'            => 'Moki Club Numana',
        'description'     => 'Centro sportivo water sports a Numana: SUP, Windsurf, Surf e Foil.',
        'url'             => 'https://mokiclub.infinityfreeapp.com/',
        'logo'            => 'https://mokiclub.infinityfreeapp.com/assets/img/moki.jpg',
        'image'           => 'https://mokiclub.infinityfreeapp.com/assets/img/sfondo.jpg',
        'sport'           => ['SUP', 'Windsurf', 'Surf', 'Foil'],
        'address'         => [
            '@type'           => 'PostalAddress',
            'addressLocality' => 'Numana',
            'addressRegion'   => 'AN',
            'addressCountry'  => 'IT',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
];
require_once __DIR__ . "/includes/header.php";
?>


<section class="hero">
    <div class="hero-overlay">
        <div class="hero-content">
            <h1>Benvenuto al Moki Club Numana</h1>
            <p>Centro sportivo water sports: SUP\WIND\SURF\FOIL</p>

            <div class="button-container">
                <a href="/cliente/iscrizione.php" class="button type1 main-button home-cta-button">
                    <span class="btn-txt">Iscriviti al Club</span>
                </a>
                <a href="/cliente/rimessaggio.php" class="button type1 secondary-button home-cta-button">
                    <span class="btn-txt">Prenota Rimessaggio</span>
                </a>
            </div>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="stats-container">
        <div class="stat-item stat-delay-1">
            <span class="stat-number">100%</span>
            <span class="stat-label">Ocean Friendly</span>
        </div>
        <div class="stat-item stat-delay-2">
            <span class="stat-number">100%</span>
            <span class="stat-label">Water Sports</span>
        </div>
        <div class="stat-item stat-delay-3">
            <span class="stat-number">11°</span>
            <span class="stat-label">stagione</span>
        </div>
    </div>
</section>

<!-- ===== SEZIONE PRESENTAZIONE CLUB ===== -->
<section class="moki-about-section">
    <h2 class="moki-about-title">Il Nostro Spirito</h2>
    <p class="moki-about-subtitle">Legno, mare e passione artigianale alla fine del porto di Numana</p>

    <div class="moki-about-grid">
        <div class="moki-about-card">
            <div class="moki-about-icon">🌺</div>
            <h3>Chi Siamo</h3>
            <p>Il Moki Club Numana nasce dalla passione di Carlo Rotelli, fondatore della linea professionale di tavole Moki, per il mare e gli sport acquatici. Siamo alla fine del porto di Numana, direttamente sulla spiaggia: vista mozzafiato su Sirolo, Sassi Neri e le Due Sorelle.</p>
            <p>Stile quasi hawaiano, legno lavorato a mano e atmosfera artigianale: anche la doccia nasce da una vera tavola da SUP scavata.</p>
        </div>

        <div class="moki-about-card">
            <div class="moki-about-icon">🏄</div>
            <h3>Cosa Facciamo</h3>
            <p>Rimessaggio per tavole da SUP e attrezzatura da windsurf, in totale sicurezza. Noleggio tavole da SUP per tutti i livelli, dai principianti fino a due modelli professionali in carbon bamboo.</p>
            <p>Lezioni di SUP durante l'estate e corsi di windsurf verso settembre, per chiudere la stagione cavalcando il vento del Conero.</p>
        </div>

        <div class="moki-about-card">
            <div class="moki-about-icon">🌊</div>
            <h3>Perché Sceglierci</h3>
            <p>Perché da noi il mare si vive, non si guarda soltanto. Siamo 100% ocean friendly: raccogliamo i rifiuti durante le uscite e curiamo ogni giorno il locale e la spiaggia che ci ospita.</p>
            <p>Un ambiente accogliente, artigianale e genuino, pronto a farti scoprire il mare delle Marche in modo diverso.</p>
        </div>
    </div>
</section>

<!-- ===== SEZIONE I NOSTRI EVENTI ===== -->
<section class="home-eventi-section">
    <h2 class="home-eventi-title">I Nostri Eventi</h2>
    <p class="home-eventi-subtitle">Uscite, giornate speciali e appuntamenti del Moki Club Numana</p>

    <?php if (!empty($eventiFuturi)): ?>
    <div class="home-eventi-gruppo">
        <h3 class="home-eventi-gruppo-title">Prossimi eventi</h3>
        <div class="home-eventi-grid">
            <?php foreach ($eventiFuturi as $ev): ?>
            <a href="/cliente/evento.php?slug=<?php echo urlencode($ev['slug']); ?>" class="home-evento-card">
                <?php if (!empty($ev['immagine_copertina'])): ?>
                    <img src="/<?php echo htmlspecialchars($ev['immagine_copertina']); ?>" alt="<?php echo htmlspecialchars($ev['titolo']); ?>">
                <?php else: ?>
                    <div class="home-evento-card-noimg">🌊</div>
                <?php endif; ?>
                <div class="home-evento-card-body">
                    <h4><?php echo htmlspecialchars($ev['titolo']); ?></h4>
                    <?php if (!empty($ev['data_evento'])): ?><p>📅 <?php echo htmlspecialchars(formattaDataIt($ev['data_evento'])); ?></p><?php endif; ?>
                    <span class="home-evento-cta">Prenota il tuo posto →</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($eventiPassati)): ?>
    <div class="home-eventi-gruppo">
        <h3 class="home-eventi-gruppo-title">Eventi passati</h3>
        <div class="home-eventi-grid home-eventi-grid-passati">
            <?php foreach ($eventiPassati as $ev): ?>
            <div class="home-evento-card home-evento-card-passato">
                <?php if (!empty($ev['immagine_copertina'])): ?>
                    <img src="/<?php echo htmlspecialchars($ev['immagine_copertina']); ?>" alt="<?php echo htmlspecialchars($ev['titolo']); ?>">
                <?php else: ?>
                    <div class="home-evento-card-noimg">🌊</div>
                <?php endif; ?>
                <div class="home-evento-card-body">
                    <h4><?php echo htmlspecialchars($ev['titolo']); ?></h4>
                    <?php if (!empty($ev['data_evento'])): ?><p>📅 <?php echo htmlspecialchars(formattaDataIt($ev['data_evento'])); ?></p><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($eventiFuturi) && empty($eventiPassati)): ?>
        <p class="home-eventi-empty">Nessun evento in programma al momento. Torna presto a trovarci!</p>
    <?php endif; ?>
</section>
<!-- ===== FINE SEZIONE I NOSTRI EVENTI ===== -->

<!-- ===== SEZIONE CONTATTI & DOVE SIAMO ===== -->
<!-- Incolla questo blocco subito prima di <?php include 'includes/footer.php'; ?> -->

<style>
/* ---------- CONTACT SECTION ---------- */
.contact-section {
    padding: 80px 20px;
    background: #0a0a0a;
    color: #fff;
}

.contact-section-title {
    text-align: center;
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 10px;
    color: #fff;
}

.contact-section-subtitle {
    text-align: center;
    color: rgba(255,255,255,0.5);
    font-size: 0.95rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 50px;
}

.contact-card {
    display: flex;
    flex-wrap: wrap;
    max-width: 1100px;
    margin: 0 auto;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 48px rgba(0,0,0,0.5);
}

/* --- info panel --- */
.contact-info-panel {
    flex: 1 1 320px;
    background: #111;
    padding: 48px 40px;
    display: flex;
    flex-direction: column;
    gap: 28px;
    border-right: 1px solid rgba(255,255,255,0.07);
}

.contact-info-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.contact-icon {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    background: rgba(255,255,255,0.06);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.contact-icon svg {
    width: 20px;
    height: 20px;
    fill: none;
    stroke: #4fc8e4;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.contact-info-text {
    display: flex;
    flex-direction: column;
}

.contact-info-label {
    font-size: 0.72rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.4);
    margin-bottom: 3px;
}

.contact-info-value {
    font-size: 0.95rem;
    color: #fff;
    text-decoration: none;
    transition: color .2s;
}

.contact-info-value:hover {
    color: #4fc8e4;
}

/* social row */
.contact-social-row {
    display: flex;
    gap: 12px;
    margin-top: 4px;
}

.contact-social-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-decoration: none;
    transition: opacity .2s, transform .15s;
}

.contact-social-btn:hover {
    opacity: 0.85;
    transform: translateY(-1px);
}

.contact-social-btn.instagram {
    background: linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
    color: #fff;
}

.contact-social-btn.whatsapp {
    background: #25D366;
    color: #fff;
}

.contact-social-btn svg {
    width: 16px;
    height: 16px;
    fill: #fff;
}

/* divider */
.contact-divider {
    border: none;
    border-top: 1px solid rgba(255,255,255,0.07);
    margin: 4px 0;
}

/* --- map panel --- */
.contact-map-panel {
    flex: 1.4 1 400px;
    min-height: 420px;
    position: relative;
}

.contact-map-panel iframe {
    width: 100%;
    height: 100%;
    min-height: 420px;
    display: block;
    border: 0;
    filter: grayscale(20%) contrast(1.05);
}

/* --- responsive --- */
@media (max-width: 768px) {
    .contact-card {
        flex-direction: column;
    }

    .contact-info-panel {
        border-right: none;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        padding: 36px 24px;
    }

    .contact-map-panel iframe {
        min-height: 280px;
    }

    .contact-section-title {
        font-size: 1.5rem;
    }
}
</style>

<section class="contact-section">
    <h2 class="contact-section-title">Contatti & Dove Siamo</h2>
    <p class="contact-section-subtitle">Vieni a trovarci a Numana</p>

    <div class="contact-card">

        <!-- INFO -->
        <div class="contact-info-panel">

            <!-- Telefono -->
            <div class="contact-info-item">
                <div class="contact-icon">
                    <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.07 12 19.79 19.79 0 0 1 1 3.18 2 2 0 0 1 2.98 1h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 5.45 5.45l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <div class="contact-info-text">
                    <span class="contact-info-label">Telefono</span>
                    <a href="tel:+393332028111" class="contact-info-value">+39 333 202 8111</a>
                </div>
            </div>

            <!-- Email -->
            <div class="contact-info-item">
                <div class="contact-icon">
                    <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <div class="contact-info-text">
                    <span class="contact-info-label">Email</span>
                    <a href="mailto:infomokiclubnumana@gmail.com" class="contact-info-value">infomokiclubnumana@gmail.com</a>
                </div>
            </div>

            <!-- Orari -->
            <div class="contact-info-item">
                <div class="contact-icon">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="contact-info-text">
                    <span class="contact-info-label">Orari di apertura</span>
                    <span class="contact-info-value">Tutti i giorni · 08:00 – 19:00</span>
                </div>
            </div>

            <!-- Indirizzo -->
            <div class="contact-info-item">
                <div class="contact-icon">
                    <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <div class="contact-info-text">
                    <span class="contact-info-label">Dove siamo</span>
                    <span class="contact-info-value">Moki Club Numana, Numana (AN)</span>
                </div>
            </div>

            <hr class="contact-divider">

            <!-- Social -->
            <div class="contact-social-row">
                <a href="https://www.instagram.com/mokiclubnumana/" target="_blank" rel="noopener" class="contact-social-btn instagram">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.334 3.608 1.308.975.975 1.246 2.242 1.308 3.608.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.062 1.366-.334 2.633-1.308 3.608-.975.975-2.242 1.246-3.608 1.308-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.366-.062-2.633-.334-3.608-1.308-.975-.975-1.246-2.242-1.308-3.608C2.175 15.584 2.163 15.204 2.163 12s.012-3.584.07-4.85c.062-1.366.334-2.633 1.308-3.608C4.516 2.497 5.783 2.226 7.15 2.163 8.416 2.105 8.796 2.163 12 2.163zm0-2.163C8.741 0 8.332.013 7.052.072 5.197.157 3.355.673 2.014 2.014.673 3.355.157 5.197.072 7.052.013 8.332 0 8.741 0 12c0 3.259.013 3.668.072 4.948.085 1.855.601 3.697 1.942 5.038 1.341 1.341 3.183 1.857 5.038 1.942C8.332 23.987 8.741 24 12 24c3.259 0 3.668-.013 4.948-.072 1.855-.085 3.697-.601 5.038-1.942 1.341-1.341 1.857-3.183 1.942-5.038.059-1.28.072-1.689.072-4.948 0-3.259-.013-3.668-.072-4.948-.085-1.855-.601-3.697-1.942-5.038C20.645.673 18.803.157 16.948.072 15.668.013 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zm0 10.162a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
                    Instagram
                </a>
                <a href="https://wa.me/393332028111" target="_blank" rel="noopener" class="contact-social-btn whatsapp">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                    WhatsApp
                </a>
            </div>

        </div>

        <!-- MAPPA -->
        <div class="contact-map-panel">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1411.4400418784753!2d13.621772574341701!3d43.51255744487085!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x13327f002a3a0c71%3A0xb7dd4fac123983b1!2sMoki%20Club%20Numana!5e1!3m2!1sit!2sit!4v1781076404918!5m2!1sit!2sit"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Moki Club Numana - Dove siamo">
            </iframe>
        </div>

    </div>
</section>
<!-- ===== FINE SEZIONE CONTATTI ===== -->

<!-- ===== SEZIONE FAQ - DOMANDE FREQUENTI ===== -->
<style>
/* ---------- FAQ SECTION ---------- */
/* Tema chiaro "sabbiolina", coerente con il resto del sito.
   Stacco netto rispetto al nero di .contact-section. */
.faq-section {
    position: relative;
    background: #f4ede1;
    padding: 80px 20px 90px;
    color: #2b2b2b;
    --faq-accent-1: #1c5d7a;   /* blu oceano */
    --faq-accent-2: #a9713f;   /* legno */
    --faq-accent-3: #6fb21b;   /* verde Moki */
}

.faq-section-title {
    text-align: center;
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 10px;
    color: #1a1a1a;
}

.faq-section-subtitle {
    text-align: center;
    color: #6b6b6b;
    font-size: 0.95rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 50px;
}

.faq-wrapper {
    max-width: 900px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 44px;
}

.faq-group {
    --faq-accent: var(--faq-accent-1);
}

.faq-group:nth-of-type(2) { --faq-accent: var(--faq-accent-2); }
.faq-group:nth-of-type(3) { --faq-accent: var(--faq-accent-3); }
.faq-group:nth-of-type(4) { --faq-accent: var(--faq-accent-1); }
.faq-group:nth-of-type(5) { --faq-accent: var(--faq-accent-2); }
.faq-group:nth-of-type(6) { --faq-accent: var(--faq-accent-3); }
.faq-group:nth-of-type(7) { --faq-accent: var(--faq-accent-1); }

.faq-group-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--faq-accent);
    margin-bottom: 16px;
    padding-left: 4px;
}

.faq-group-icon {
    font-size: 1.1rem;
    line-height: 1;
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.faq-item {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.08);
    border-left: 3px solid rgba(0,0,0,0.08);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    transition: border-color .2s, box-shadow .2s;
}

.faq-item[open] {
    border-color: var(--faq-accent);
    border-left-color: var(--faq-accent);
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

.faq-question {
    list-style: none;
    cursor: pointer;
    padding: 20px 52px 20px 22px;
    position: relative;
    font-size: 1.02rem;
    font-weight: 600;
    line-height: 1.4;
    color: #1a1a1a;
    display: flex;
    align-items: center;
    transition: background .2s;
}

.faq-question::-webkit-details-marker {
    display: none;
}

.faq-question:hover {
    background: rgba(0,0,0,0.02);
}

.faq-question::after {
    content: "+";
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.4rem;
    font-weight: 300;
    color: var(--faq-accent);
    transition: transform .25s;
}

.faq-item[open] .faq-question::after {
    transform: translateY(-50%) rotate(45deg);
}

.faq-answer {
    padding: 2px 22px 22px 22px;
    color: #555;
    font-size: 0.97rem;
    line-height: 1.75;
}

@media (max-width: 768px) {
    .faq-section-title {
        font-size: 1.5rem;
    }

    .faq-question {
        padding: 17px 46px 17px 18px;
        font-size: 0.96rem;
    }

    .faq-answer {
        padding: 0 18px 19px;
        font-size: 0.92rem;
    }
}
</style>

<section class="faq-section">
    <h2 class="faq-section-title">Domande Frequenti</h2>
    <p class="faq-section-subtitle">Tutto quello che c'è da sapere prima di venire da noi</p>

    <div class="faq-wrapper">

        <!-- Partecipazione e livelli -->
        <div class="faq-group">
            <h3 class="faq-group-title"><span class="faq-group-icon">🏄</span>Partecipazione e livelli</h3>
            <div class="faq-list">
                <details class="faq-item">
                    <summary class="faq-question">Chi può partecipare alle attività del Moki Club?</summary>
                    <div class="faq-answer">Il club è aperto a tutti, dai principianti assoluti a chi è già esperto di SUP e windsurf. Per noleggiare l'attrezzatura o partecipare agli eventi è sufficiente che un adulto risulti iscritto al club.</div>
                </details>
                <details class="faq-item">
                    <summary class="faq-question">Serve saper nuotare?</summary>
                    <div class="faq-answer">Sì, una base di nuoto è indispensabile per la tua sicurezza in acqua. È il primo passo per vivere l'uscita con serenità e goderti davvero il mare del Conero.</div>
                </details>
                <details class="faq-item">
                    <summary class="faq-question">Non ho mai provato il SUP, posso comunque venire?</summary>
                    <div class="faq-answer">Certo, le nostre attività sono pensate per tutti i livelli. Prima di ogni uscita ti diamo gratuitamente qualche nozione base per salire in tavola e muoverti in sicurezza.</div>
                </details>
            </div>
        </div>

        <!-- Prenotazioni e noleggio -->
        <div class="faq-group">
            <h3 class="faq-group-title"><span class="faq-group-icon">📅</span>Prenotazioni e noleggio</h3>
            <div class="faq-list">
                <details class="faq-item">
                    <summary class="faq-question">Come funziona il noleggio delle tavole?</summary>
                    <div class="faq-answer">Il noleggio è disponibile solo per le tavole da SUP; per il windsurf, purtroppo, non abbiamo spazio dedicato. Se l'orario in cui vuoi uscire rientra nei nostri orari di apertura non serve nessuna prenotazione: basta presentarsi al club.</div>
                </details>
                <details class="faq-item">
                    <summary class="faq-question">Posso prenotare un'uscita all'alba o in un orario particolare?</summary>
                    <div class="faq-answer">Per richieste fuori dal nostro orario standard, come le uscite all'alba, scrivici direttamente su WhatsApp. Troveremo insieme la soluzione migliore per la tua uscita speciale.</div>
                </details>
                <details class="faq-item">
                    <summary class="faq-question">Come prenoto una lezione o un evento?</summary>
                    <div class="faq-answer">Le lezioni si prenotano scrivendoci su WhatsApp, mentre gli eventi e le uscite speciali si prenotano direttamente dal sito. In entrambi i casi accettiamo pagamenti in contanti e con carta.</div>
                </details>
                <details class="faq-item">
                    <summary class="faq-question">Posso cancellare una prenotazione?</summary>
                    <div class="faq-answer">Sì, puoi cancellare la prenotazione di un'uscita fino a 48 ore prima dell'evento programmato. Basta usare il link presente nell'email di conferma che hai ricevuto al momento della prenotazione.</div>
                </details>
            </div>
        </div>

        <!-- Cosa portare -->
        <div class="faq-group">
            <h3 class="faq-group-title"><span class="faq-group-icon">🎒</span>Cosa portare</h3>
            <div class="faq-list">
                <details class="faq-item">
                    <summary class="faq-question">Cosa devo portare per un'uscita?</summary>
                    <div class="faq-answer">Ti basta portare il costume: telefono, portafoglio e oggetti personali puoi lasciarli tranquillamente nelle cassepanche della nostra zona spogliatoio, all'ombra degli alberi dietro al club. Se preferisci tenerli con te in acqua, abbiamo alcune sacche impermeabili e custodie per telefono a disposizione.</div>
                </details>
                <details class="faq-item">
                    <summary class="faq-question">Fornite giubbotti salvagente e altra attrezzatura di sicurezza?</summary>
                    <div class="faq-answer">Sì, mettiamo a disposizione giubbotti salvagente in caso di necessità o per i più piccoli, oltre a pagaia e leash per ogni noleggio. Non disponiamo invece di mute in neoprene, quindi se ne hai bisogno ricordati di portare la tua.</div>
                </details>
                <details class="faq-item">
                    <summary class="faq-question">C'è uno spazio dove cambiarsi?</summary>
                    <div class="faq-answer">Sì, dietro al club trovi un'area ombreggiata dagli alberi con spogliatoio, doccia e spazio per lasciare i tuoi effetti personali in sicurezza.</div>
                </details>
            </div>
        </div>

        <!-- Sicurezza e meteo -->
        <div class="faq-group">
            <h3 class="faq-group-title"><span class="faq-group-icon">🌊</span>Sicurezza e condizioni meteo</h3>
            <div class="faq-list">
                <details class="faq-item">
                    <summary class="faq-question">Cosa succede se il mare è mosso?</summary>
                    <div class="faq-answer">In caso di mare mosso indirizziamo le uscite verso la laguna, restando sempre entro le boe rosse. La tua sicurezza viene prima di tutto.</div>
                </details>
                <details class="faq-item">
                    <summary class="faq-question">Si esce anche con vento forte?</summary>
                    <div class="faq-answer">Con vento forte o direzioni particolari sconsigliamo l'uscita a chi è alle prime armi e ai bambini. È sempre il responsabile presente in club a valutare, uscita per uscita, se le condizioni del mare permettono di partire in sicurezza.</div>
                </details>
            </div>
        </div>

        <!-- Rimessaggio -->
        <div class="faq-group">
            <h3 class="faq-group-title"><span class="faq-group-icon">🗝️</span>Rimessaggio e attrezzatura</h3>
            <div class="faq-list">
                <details class="faq-item">
                    <summary class="faq-question">Come funziona il rimessaggio delle tavole?</summary>
                    <div class="faq-answer">Offriamo rimessaggio con tariffe settimanali, mensili e stagionali, sia per il SUP (tavola, pagaia, pompa, leash e sacca) sia per tutta l'attrezzatura da windsurf. Scrivici su WhatsApp per un preventivo personalizzato e per verificare la disponibilità.</div>
                </details>
                <details class="faq-item">
                    <summary class="faq-question">Posso accedere al club quando voglio con l'attrezzatura in rimessaggio?</summary>
                    <div class="faq-answer">Sì, chi ha un rimessaggio attivo riceve una copia delle chiavi e ha accesso libero al locale negli orari concordati, per andare in acqua quando preferisce.</div>
                </details>
            </div>
        </div>

        <!-- Uscite ed eventi speciali -->
        <div class="faq-group">
            <h3 class="faq-group-title"><span class="faq-group-icon">🎉</span>Uscite ed eventi speciali</h3>
            <div class="faq-list">
                <details class="faq-item">
                    <summary class="faq-question">Come funzionano le uscite di gruppo?</summary>
                    <div class="faq-answer">Ogni uscita ha un numero massimo di partecipanti, che dipende dalla disponibilità delle nostre attrezzature. Chi ha già la propria tavola può comunque partecipare pagando una quota dedicata.</div>
                </details>
                <details class="faq-item">
                    <summary class="faq-question">Cosa è incluso nelle uscite speciali?</summary>
                    <div class="faq-answer">Le nostre uscite speciali includono sempre un momento conviviale con spuntini, foto e brindisi finale. La quota di partecipazione, richiesta anche a chi porta attrezzatura propria, copre le spese di cibo e organizzazione dell'evento.</div>
                </details>
            </div>
        </div>

        <!-- Iscrizione al club -->
        <div class="faq-group">
            <h3 class="faq-group-title"><span class="faq-group-icon">📝</span>Iscrizione al club</h3>
            <div class="faq-list">
                <details class="faq-item">
                    <summary class="faq-question">È obbligatorio iscriversi al club?</summary>
                    <div class="faq-answer">Sì, l'iscrizione è necessaria per noleggiare o mettere in rimessaggio l'attrezzatura, ed è il modo più semplice per entrare a far parte della famiglia Moki. Ha validità stagionale, quindi puoi rinnovarla a ogni nuova stagione.</div>
                </details>
            </div>
        </div>

    </div>
</section>
<!-- ===== FINE SEZIONE FAQ ===== -->

<?php
include 'includes/footer.php';
?>