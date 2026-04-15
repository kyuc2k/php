<?php
require 'config.php';

$token = trim($_GET['token'] ?? '');
if ($token === '') {
    http_response_code(404);
    header('Location: 404.php');
    exit();
}

$stmt = $conn->prepare("SELECT cp.parsed_data, cp.raw_text, u.name AS owner_name, u.avatar AS owner_picture FROM cv_profiles cp JOIN users u ON u.id = cp.user_id WHERE cp.token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    header('Location: 404.php');
    exit();
}

$cv = json_decode($row['parsed_data'], true) ?? [];

function sec(string $key): string {
    global $cv;
    return trim($cv[$key] ?? '');
}

function nl2p(string $text): string {
    if ($text === '') return '';
    $paras = array_filter(array_map('trim', explode("\n\n", $text)));
    return implode('', array_map(fn($p) => '<p>' . nl2br(htmlspecialchars($p)) . '</p>', $paras));
}

function nl2li(string $text): string {
    if ($text === '') return '';
    $lines = array_filter(array_map('trim', explode("\n", $text)));
    return '<ul>' . implode('', array_map(fn($l) => '<li>' . htmlspecialchars($l) . '</li>', $lines)) . '</ul>';
}

$name         = sec('name')    ?: $row['owner_name'];
$cvPhotoFile  = __DIR__ . '/uploads/cv_photos/' . $token . '.jpg';
$ownerPicture = file_exists($cvPhotoFile)
    ? 'uploads/cv_photos/' . $token . '.jpg'
    : ($row['owner_picture'] ?? '');
$email   = sec('email');
$phone   = sec('phone');
$address = sec('address');
$linkedin = sec('linkedin');
$github   = sec('github');
$website  = sec('website');

$sections = [
    'about'          => ['label' => 'Giới thiệu',       'icon' => 'fa-user'],
    'experience'     => ['label' => 'Kinh nghiệm',      'icon' => 'fa-briefcase'],
    'education'      => ['label' => 'Học vấn',           'icon' => 'fa-graduation-cap'],
    'skills'         => ['label' => 'Kỹ năng',           'icon' => 'fa-code'],
    'projects'       => ['label' => 'Dự án',             'icon' => 'fa-folder-open'],
    'certifications' => ['label' => 'Chứng chỉ',         'icon' => 'fa-certificate'],
    'languages'      => ['label' => 'Ngoại ngữ',         'icon' => 'fa-language'],
    'interests'      => ['label' => 'Sở thích',          'icon' => 'fa-heart'],
    'references'     => ['label' => 'Tài liệu tham khảo','icon' => 'fa-address-book'],
];

$activeSections = array_filter($sections, fn($_, $k) => sec($k) !== '', ARRAY_FILTER_USE_BOTH);
$extractionFailed = empty($activeSections);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($name) ?> — CV</title>
    <link href="https://fonts.googleapis.com/css2?family=Calibre:wght@300;400;500;600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy:      #0a192f;
            --navy-mid:  #112240;
            --navy-light:#233554;
            --slate:     #8892b0;
            --slate-mid: #a8b2d8;
            --slate-light:#ccd6f6;
            --white:     #e6f1ff;
            --teal:      #64ffda;
            --sidebar-w: 340px;
        }

        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        html { scroll-behavior: smooth; font-size: 16px; }

        body {
            font-family: 'Inter', 'Calibre', 'San Francisco', 'SF Pro Text', -apple-system, sans-serif;
            background: var(--navy);
            color: var(--slate);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* ─── Layout wrapper ─── */
        .cv-wrapper {
            display: grid;
            grid-template-columns: var(--sidebar-w) 1fr;
            min-height: 100vh;
            max-width: 1280px;
            margin: 0 auto;
        }

        /* ─── LEFT PANEL ─── */
        .panel-left {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            padding: 60px 40px 40px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--navy-light);
        }

        .panel-left::-webkit-scrollbar { width: 4px; }
        .panel-left::-webkit-scrollbar-thumb { background: var(--navy-light); border-radius: 2px; }

        /* Avatar */
        .avatar-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--teal);
            margin-bottom: 20px;
            display: block;
        }

        .avatar {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--teal) 0%, #0d7377 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; font-weight: 700;
            color: var(--navy);
            margin-bottom: 20px;
            flex-shrink: 0;
            border: 2px solid rgba(100,255,218,.25);
        }

        .left-name {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--white);
            line-height: 1.2;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }

        .left-role {
            font-size: 0.9rem;
            color: var(--teal);
            font-weight: 500;
            margin-bottom: 20px;
            letter-spacing: 0.02em;
        }

        .left-bio {
            font-size: 0.82rem;
            color: var(--slate);
            line-height: 1.7;
            margin-bottom: 28px;
        }

        /* Contact */
        .contact-list { margin-bottom: 32px; }

        .contact-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8rem;
            color: var(--slate);
            margin-bottom: 10px;
            word-break: break-all;
        }

        .contact-row i {
            color: var(--teal);
            width: 14px;
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .contact-row a {
            color: var(--slate);
            text-decoration: none;
            transition: color .2s;
        }

        .contact-row a:hover { color: var(--teal); }

        /* Nav */
        .nav-section { flex: 1; }

        .nav-section ul { list-style: none; }

        .nav-section ul li { margin-bottom: 4px; }

        .nav-section ul li a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 8px 0;
            color: var(--slate);
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: all .2s;
            position: relative;
        }

        .nav-section ul li a::before {
            content: '';
            display: block;
            width: 28px;
            height: 1px;
            background: var(--slate);
            transition: all .2s;
            flex-shrink: 0;
        }

        .nav-section ul li a:hover,
        .nav-section ul li a.active {
            color: var(--white);
        }

        .nav-section ul li a:hover::before,
        .nav-section ul li a.active::before {
            width: 56px;
            background: var(--white);
        }

        .nav-section ul li a.active {
            color: var(--white);
        }

        /* Social icons row */
        .social-row {
            display: flex;
            gap: 16px;
            margin-top: 32px;
        }

        .social-row a {
            color: var(--slate);
            font-size: 1rem;
            transition: color .2s, transform .2s;
        }

        .social-row a:hover {
            color: var(--teal);
            transform: translateY(-2px);
        }

        /* ─── RIGHT PANEL ─── */
        .panel-right {
            padding: 60px 60px 120px;
            overflow-x: hidden;
        }

        /* Section */
        .cv-section {
            margin-bottom: 72px;
            scroll-margin-top: 60px;
        }

        .cv-section-heading {
            display: flex;
            align-items: center;
            gap: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--slate-light);
            letter-spacing: 0.05em;
            margin-bottom: 28px;
            white-space: nowrap;
        }

        .cv-section-heading .heading-num {
            color: var(--teal);
            font-size: 0.85rem;
            font-weight: 400;
            margin-right: 8px;
            font-family: 'SF Mono', 'Fira Code', monospace;
        }

        .cv-section-heading::after {
            content: '';
            display: block;
            height: 1px;
            background: var(--navy-light);
            flex: 1;
            margin-left: 16px;
        }

        /* Content text */
        .cv-body p {
            font-size: 0.93rem;
            color: var(--slate);
            line-height: 1.8;
            margin-bottom: 14px;
        }

        .cv-body ul {
            list-style: none;
            padding: 0;
        }

        .cv-body ul li {
            position: relative;
            padding-left: 18px;
            font-size: 0.92rem;
            color: var(--slate);
            line-height: 1.7;
            margin-bottom: 8px;
        }

        .cv-body ul li::before {
            content: '▹';
            position: absolute;
            left: 0;
            color: var(--teal);
            font-size: 0.8rem;
            top: 2px;
        }

        /* Entry card (experience/education/projects) */
        .entry-card {
            background: var(--navy-mid);
            border: 1px solid var(--navy-light);
            border-radius: 6px;
            padding: 22px 24px;
            margin-bottom: 16px;
            transition: border-color .2s, transform .2s;
        }

        .entry-card:hover {
            border-color: var(--teal);
            transform: translateX(4px);
        }

        .entry-header { margin-bottom: 4px; }

        .entry-title {
            font-size: 0.97rem;
            font-weight: 600;
            color: var(--slate-light);
            margin-bottom: 4px;
        }

        .entry-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .entry-company {
            font-size: 0.82rem;
            color: var(--teal);
            font-weight: 500;
        }

        .entry-date {
            font-size: 0.78rem;
            color: var(--slate);
            font-family: 'SF Mono', 'Fira Code', monospace;
        }

        /* Skills */
        .skills-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .skill-tag {
            background: rgba(100,255,218,.08);
            color: var(--teal);
            border: 1px solid rgba(100,255,218,.2);
            border-radius: 4px;
            padding: 5px 14px;
            font-size: 0.78rem;
            font-family: 'SF Mono', 'Fira Code', monospace;
            letter-spacing: 0.03em;
            transition: background .2s;
        }

        .skill-tag:hover { background: rgba(100,255,218,.15); }

        /* Grouped skills */
        .skills-categories { display: flex; flex-direction: column; gap: 20px; }

        .skill-group-label {
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--slate-mid);
            margin-bottom: 10px;
        }

        /* Print button */
        .print-fab {
            position: fixed;
            bottom: 32px; right: 32px;
            background: var(--teal);
            color: var(--navy);
            width: 48px; height: 48px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 20px rgba(100,255,218,.25);
            transition: all .2s;
            z-index: 999;
        }

        .print-fab:hover { transform: scale(1.1); box-shadow: 0 8px 28px rgba(100,255,218,.4); }

        /* Failed extraction */
        .failed-box {
            text-align: center;
            padding: 80px 24px;
        }

        .failed-box .failed-icon { font-size: 3rem; margin-bottom: 16px; }

        .failed-box h2 {
            font-size: 1.3rem;
            color: var(--slate-light);
            margin-bottom: 12px;
        }

        .failed-box p {
            color: var(--slate);
            max-width: 460px;
            margin: 0 auto 24px;
            line-height: 1.8;
            font-size: 0.88rem;
        }

        .failed-box code {
            background: var(--navy-mid);
            color: var(--teal);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'SF Mono', monospace;
            font-size: 0.82rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: transparent;
            color: var(--teal);
            border: 1px solid var(--teal);
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            transition: all .2s;
        }

        .back-btn:hover { background: rgba(100,255,218,.08); }

        /* ─── Responsive ─── */
        @media (max-width: 900px) {
            .cv-wrapper { grid-template-columns: 1fr; }
            .panel-left {
                position: relative;
                height: auto;
                padding: 40px 28px 28px;
                border-right: none;
                border-bottom: 1px solid var(--navy-light);
            }
            .nav-section { display: none; }
            .panel-right { padding: 40px 28px 80px; }
        }

        @media print {
            .panel-left { position: relative; height: auto; }
            .nav-section { display: none; }
            .print-fab { display: none; }
            .cv-wrapper { grid-template-columns: 220px 1fr; }
        }
    </style>
</head>
<body>

<div class="cv-wrapper">

    <!-- ── LEFT PANEL ── -->
    <aside class="panel-left">
        <?php if ($ownerPicture): ?>
        <img src="<?= htmlspecialchars($ownerPicture) ?>" alt="<?= htmlspecialchars($name) ?>" class="avatar avatar-img" referrerpolicy="no-referrer">
        <?php else: ?>
        <div class="avatar"><?= mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') ?></div>
        <?php endif; ?>

        <h1 class="left-name"><?= htmlspecialchars($name) ?></h1>

        <?php if ($email || $phone || $address || $linkedin || $github || $website): ?>
        <div class="contact-list">
            <?php if ($email): ?>
            <div class="contact-row"><i class="fas fa-envelope"></i><a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></div>
            <?php endif; ?>
            <?php if ($phone): ?>
            <div class="contact-row"><i class="fas fa-phone"></i><span><?= htmlspecialchars($phone) ?></span></div>
            <?php endif; ?>
            <?php if ($address): ?>
            <div class="contact-row"><i class="fas fa-map-marker-alt"></i><span><?= htmlspecialchars($address) ?></span></div>
            <?php endif; ?>
            <?php if ($linkedin): ?>
            <div class="contact-row"><i class="fab fa-linkedin"></i><a href="<?= htmlspecialchars($linkedin) ?>" target="_blank">LinkedIn</a></div>
            <?php endif; ?>
            <?php if ($github): ?>
            <div class="contact-row"><i class="fab fa-github"></i><a href="<?= htmlspecialchars($github) ?>" target="_blank">GitHub</a></div>
            <?php endif; ?>
            <?php if ($website): ?>
            <div class="contact-row"><i class="fas fa-globe"></i><a href="<?= htmlspecialchars($website) ?>" target="_blank"><?= htmlspecialchars($website) ?></a></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!$extractionFailed): ?>
        <nav class="nav-section">
            <ul>
                <?php $idx = 0; foreach ($activeSections as $key => $meta): $idx++; ?>
                <li>
                    <a href="#<?= $key ?>" class="nav-link" data-section="<?= $key ?>">
                        <?= $meta['label'] ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <div class="social-row">
            <?php if ($github): ?><a href="<?= htmlspecialchars($github) ?>" target="_blank" title="GitHub"><i class="fab fa-github"></i></a><?php endif; ?>
            <?php if ($linkedin): ?><a href="<?= htmlspecialchars($linkedin) ?>" target="_blank" title="LinkedIn"><i class="fab fa-linkedin"></i></a><?php endif; ?>
            <?php if ($email): ?><a href="mailto:<?= htmlspecialchars($email) ?>" title="Email"><i class="fas fa-envelope"></i></a><?php endif; ?>
        </div>
    </aside>

    <!-- ── RIGHT PANEL ── -->
    <main class="panel-right">

        <?php if ($extractionFailed): ?>
        <div class="failed-box">
            <div class="failed-icon">📄</div>
            <h2>Không thể đọc nội dung PDF</h2>
            <p>
                File dùng font nhúng đặc biệt — cần cài <strong style="color:var(--slate-light)">pdftotext</strong> để đọc nội dung.<br><br>
                Trên VPS: <code>yum install poppler-utils</code><br>
                Trên Windows: tải tại <a href="https://github.com/oschwartz10612/poppler-windows/releases" target="_blank" style="color:var(--teal)">poppler-windows</a>
                rồi đặt <code>pdftotext.exe</code> vào thư mục <code>poppler/</code> trong project.
            </p>
            <a href="javascript:history.back()" class="back-btn"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>

        <?php else: ?>

        <?php $sectionNum = 0; foreach ($activeSections as $key => $meta):
            $content = sec($key);
            if ($content === '') continue;
            $sectionNum++;
        ?>
        <section class="cv-section" id="<?= $key ?>">
            <h2 class="cv-section-heading">
                <span class="heading-num"><?= str_pad($sectionNum, 2, '0', STR_PAD_LEFT) ?>.</span>
                <?= $meta['label'] ?>
            </h2>

            <?php if ($key === 'skills'): ?>
            <?php
            $skillLines = array_filter(array_map('trim', explode("\n", $content)));
            $hasCategories = count(array_filter($skillLines, fn($l) => preg_match('/^[^:]{2,40}:\s*.+/', $l))) > 0;
            ?>
            <?php if ($hasCategories): ?>
            <div class="skills-categories">
                <?php foreach ($skillLines as $line):
                    if ($line === '') continue;
                    if (preg_match('/^(.{2,40}):\s*(.+)$/', $line, $m)): ?>
                <div class="skill-group">
                    <div class="skill-group-label"><?= htmlspecialchars(trim($m[1])) ?></div>
                    <div class="skills-grid">
                        <?php foreach (array_filter(array_map('trim', explode(',', $m[2]))) as $s): ?>
                        <span class="skill-tag"><?= htmlspecialchars($s) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                    <?php else: ?>
                <div class="skills-grid" style="margin-bottom:10px;">
                    <span class="skill-tag"><?= htmlspecialchars($line) ?></span>
                </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="skills-grid">
                <?php foreach ($skillLines as $skill):
                    if (strlen($skill) < 2 || strlen($skill) > 80) continue;
                    foreach (array_filter(array_map('trim', preg_split('/[,;·•]+/', $skill))) as $s): ?>
                <span class="skill-tag"><?= htmlspecialchars($s) ?></span>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php elseif (in_array($key, ['experience', 'education', 'projects'])): ?>
            <?php
                $blocks = preg_split('/\n{2,}/', trim($content));
                foreach ($blocks as $block):
                    $block = trim($block);
                    if ($block === '') continue;
                    $lines = array_values(array_filter(array_map('trim', explode("\n", $block))));
                    if (empty($lines)) continue;

                    // Detect "Title | Company | Date" structured first line
                    $firstLine  = $lines[0];
                    $parts      = array_map('trim', explode('|', $firstLine));
                    $isStructured = count($parts) >= 2;
                    $bodyLines  = array_slice($lines, $isStructured ? 1 : 0);
                    $isList     = count(array_filter($bodyLines, fn($l) => preg_match('/^[-•·*▹]/', $l))) > count($bodyLines) * 0.3;
            ?>
            <div class="entry-card">
                <?php if ($isStructured): ?>
                <div class="entry-header">
                    <div class="entry-title"><?= htmlspecialchars($parts[0]) ?></div>
                    <div class="entry-meta">
                        <?php if (!empty($parts[1])): ?>
                        <span class="entry-company"><?= htmlspecialchars($parts[1]) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($parts[2])): ?>
                        <span class="entry-date"><?= htmlspecialchars($parts[2]) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($bodyLines)): ?>
                <div class="cv-body" <?= $isStructured ? 'style="margin-top:12px;"' : '' ?>>
                    <?php if ($isList): ?>
                    <ul>
                    <?php foreach ($bodyLines as $l): ?><li><?= htmlspecialchars(ltrim($l, '-•·*▹ ')) ?></li><?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p><?= nl2br(htmlspecialchars(implode("\n", $bodyLines))) ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <?php else: ?>
            <div class="cv-body">
                <?php
                $lines = array_filter(array_map('trim', explode("\n", $content)));
                $isList = count(array_filter($lines, fn($l) => preg_match('/^[-•·*▹\d+\.]/', $l))) > count($lines) * 0.4;
                if ($isList): ?>
                <ul>
                    <?php foreach ($lines as $l): ?><li><?= htmlspecialchars(ltrim($l, '-•·*▹ ')) ?></li><?php endforeach; ?>
                </ul>
                <?php else: ?>
                    <?= nl2p($content) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </section>
        <?php endforeach; ?>
        <?php endif; ?>

    </main>
</div>

<button class="print-fab" onclick="window.print()" title="In / Lưu PDF">
    <i class="fas fa-print"></i>
</button>

<script>
const navLinks = document.querySelectorAll('.nav-link[data-section]');
const sections = document.querySelectorAll('.cv-section[id]');

const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            navLinks.forEach(l => l.classList.remove('active'));
            const a = document.querySelector(`.nav-link[data-section="${e.target.id}"]`);
            if (a) a.classList.add('active');
        }
    });
}, { rootMargin: '-20% 0px -60% 0px', threshold: 0 });

sections.forEach(s => io.observe(s));
</script>
</body>
</html>
