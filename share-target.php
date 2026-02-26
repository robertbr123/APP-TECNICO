<?php
/**
 * Share Target - PWA App Capability
 * Recebe conteúdo compartilhado de outros apps Android
 * Configurado em manifest.json > share_target
 */

session_start();

// Requer autenticação
if (empty($_SESSION['user_id']) && empty($_SESSION['token'])) {
    header('Location: /login.php?redirect=share-target.php');
    exit;
}

$title   = trim($_POST['title'] ?? $_GET['title'] ?? '');
$text    = trim($_POST['text']  ?? $_GET['text']  ?? '');
$url     = trim($_POST['url']   ?? $_GET['url']   ?? '');
$hasFile = !empty($_FILES['file']['name']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#135bec">
    <title>Conteúdo recebido - Ondeline</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', sans-serif;
            background: #f6f6f8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: #135bec;
            color: white;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header span { font-size: 24px; }
        .header h1 { font-size: 18px; font-weight: 600; }
        .content { padding: 20px; flex: 1; }
        .card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .card-label {
            font-size: 11px;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .card-value {
            font-size: 15px;
            color: #1a1a1a;
            word-break: break-word;
        }
        .preview-img {
            width: 100%;
            max-height: 240px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 8px;
        }
        .actions { display: flex; flex-direction: column; gap: 12px; margin-top: 8px; }
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            border-radius: 10px;
            border: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .15s;
        }
        .btn:active { opacity: 0.8; }
        .btn-primary { background: #135bec; color: white; }
        .btn-secondary { background: #f0f0f5; color: #333; }
        .btn span { font-size: 20px; }
        .empty { color: #aaa; font-style: italic; }
    </style>
</head>
<body>

<div class="header">
    <span class="material-symbols-outlined">share</span>
    <h1>Conteúdo recebido</h1>
</div>

<div class="content">

    <?php if ($hasFile): ?>
    <div class="card">
        <div class="card-label">Arquivo recebido</div>
        <?php
            $tmpName  = $_FILES['file']['tmp_name'];
            $mimeType = $_FILES['file']['type'];
            $isImage  = str_starts_with($mimeType, 'image/');
            if ($isImage):
                $b64 = base64_encode(file_get_contents($tmpName));
        ?>
        <img class="preview-img" src="data:<?= htmlspecialchars($mimeType) ?>;base64,<?= $b64 ?>" alt="Arquivo compartilhado">
        <?php else: ?>
        <div class="card-value"><?= htmlspecialchars($_FILES['file']['name']) ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($title): ?>
    <div class="card">
        <div class="card-label">Título</div>
        <div class="card-value"><?= htmlspecialchars($title) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($text): ?>
    <div class="card">
        <div class="card-label">Texto</div>
        <div class="card-value"><?= htmlspecialchars($text) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($url): ?>
    <div class="card">
        <div class="card-label">Link</div>
        <div class="card-value"><?= htmlspecialchars($url) ?></div>
    </div>
    <?php endif; ?>

    <?php if (!$hasFile && !$title && !$text && !$url): ?>
    <div class="card">
        <div class="card-value empty">Nenhum conteúdo recebido.</div>
    </div>
    <?php endif; ?>

    <div class="actions">

        <?php if ($hasFile): ?>
        <form method="POST" action="/api/upload-foto.php" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="shared" value="1">
            <button type="submit" class="btn btn-primary" style="width:100%">
                <span class="material-symbols-outlined">upload</span>
                Enviar foto ao cliente
            </button>
        </form>
        <?php endif; ?>

        <?php if ($text || $title): ?>
        <?php
            $qs = http_build_query(array_filter([
                'prefill_name' => $title ?: $text,
                'prefill_obs'  => $text,
            ]));
        ?>
        <a href="/novo-cadastro.php?<?= $qs ?>" class="btn btn-primary">
            <span class="material-symbols-outlined">person_add</span>
            Usar em novo cadastro
        </a>
        <?php endif; ?>

        <?php if ($url): ?>
        <a href="/novo-cadastro.php?prefill_obs=<?= urlencode($url) ?>" class="btn btn-secondary">
            <span class="material-symbols-outlined">link</span>
            Adicionar link a cadastro
        </a>
        <?php endif; ?>

        <a href="/dashboard.php" class="btn btn-secondary">
            <span class="material-symbols-outlined">home</span>
            Ir para o dashboard
        </a>

    </div>
</div>

<script>
    // Registra SW se ainda não estiver registrado
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }
</script>

</body>
</html>
