<?php
/**
 * Bottom Navigation - Componente Compartilhado
 * Recebe $activePage para destacar o item ativo
 * Uso: <?php $activePage = 'inicio'; include 'partials/bottom-nav.php'; ?>
 */
$navItems = [
    ['id' => 'inicio', 'icon' => 'home', 'label' => 'Inicio', 'href' => 'dashboard.php'],
    ['id' => 'rotas', 'icon' => 'map', 'label' => 'Mapa', 'href' => 'mapa.php'],
    ['id' => 'ponto', 'icon' => 'schedule', 'label' => 'Ponto', 'href' => 'ponto.php', 'fab' => true],
    ['id' => 'clientes', 'icon' => 'people', 'label' => 'Clientes', 'href' => 'consultar.php'],
    ['id' => 'ajustes', 'icon' => 'settings', 'label' => 'Ajustes', 'href' => 'ajustes.php'],
];
?>
<style>
    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 40;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-top: 1px solid rgba(0, 0, 0, 0.06);
        padding-bottom: env(safe-area-inset-bottom, 0px);
    }

    @media (prefers-color-scheme: dark) {
        .bottom-nav {
            background: rgba(23, 23, 23, 0.95);
            border-top-color: rgba(255, 255, 255, 0.05);
        }
    }

    .bottom-nav-inner {
        display: flex;
        justify-content: space-around;
        align-items: center;
        height: 68px;
        max-width: 480px;
        margin: 0 auto;
        padding: 0 8px;
    }

    .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 6px 12px;
        min-width: 64px;
        text-decoration: none;
        color: #9ca3af;
        transition: all 0.2s ease;
        -webkit-tap-highlight-color: transparent;
        position: relative;
    }

    @media (prefers-color-scheme: dark) {
        .nav-item {
            color: #6b7280;
        }
    }

    .nav-item:active {
        transform: scale(0.92);
    }

    .nav-item .nav-icon {
        font-size: 22px;
        line-height: 1;
        transition: all 0.2s ease;
        position: relative;
    }

    .nav-item .nav-label {
        font-size: 11px;
        font-weight: 500;
        line-height: 1;
        transition: all 0.2s ease;
    }

    /* Item ativo - pill indicator */
    .nav-item.active .nav-icon-wrap {
        background: var(--color-primary, #6366f1);
        border-radius: 16px;
        padding: 4px 16px;
    }

    .nav-item.active .nav-icon {
        color: #fff;
        font-variation-settings: 'FILL' 1;
    }

    .nav-item.active .nav-label {
        color: var(--color-primary, #6366f1);
        font-weight: 700;
    }

    /* FAB - Botao central elevado */
    .nav-item-fab {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
        padding: 0 12px 6px;
        min-width: 64px;
        text-decoration: none;
        -webkit-tap-highlight-color: transparent;
    }

    .nav-fab-circle {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: var(--color-primary, #6366f1);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
        margin-top: -22px;
        transition: all 0.2s ease;
        position: relative;
    }

    .nav-fab-circle .nav-icon {
        font-size: 26px;
        color: #fff;
        font-variation-settings: 'FILL' 1;
    }

    .nav-item-fab:active .nav-fab-circle {
        transform: scale(0.92);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
    }

    .nav-item-fab.active .nav-fab-circle {
        background: var(--color-primary, #6366f1);
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.45);
    }

    .nav-item-fab .nav-label {
        font-size: 11px;
        font-weight: 500;
        color: #9ca3af;
        line-height: 1;
        transition: all 0.2s ease;
    }

    .nav-item-fab.active .nav-label {
        color: var(--color-primary, #6366f1);
        font-weight: 700;
    }

    @media (prefers-color-scheme: dark) {
        .nav-item-fab .nav-label {
            color: #6b7280;
        }
        .nav-item-fab.active .nav-label {
            color: var(--color-primary, #6366f1);
        }
        .nav-fab-circle {
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }
    }

    /* Badge de notificacao */
    .nav-badge {
        position: absolute;
        top: -2px;
        right: -4px;
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        border: 2px solid #fff;
        display: none;
    }

    @media (prefers-color-scheme: dark) {
        .nav-badge {
            border-color: #171717;
        }
    }

    .nav-badge.show {
        display: block;
    }

    /* Spacer para conteudo nao ficar atras do nav */
    .bottom-nav-spacer {
        height: calc(68px + env(safe-area-inset-bottom, 0px));
    }
</style>

<div class="bottom-nav-spacer"></div>
<nav class="bottom-nav">
    <div class="bottom-nav-inner">
        <?php foreach ($navItems as $item): ?>
            <?php $isActive = (isset($activePage) && $activePage === $item['id']); ?>
            <?php if (!empty($item['fab'])): ?>
                <!-- FAB central -->
                <a href="<?= $item['href'] ?>" class="nav-item-fab <?= $isActive ? 'active' : '' ?>">
                    <div class="nav-fab-circle">
                        <span class="material-symbols-outlined nav-icon"><?= $item['icon'] ?></span>
                    </div>
                    <span class="nav-label"><?= $item['label'] ?></span>
                </a>
            <?php else: ?>
                <!-- Item normal -->
                <a href="<?= $item['href'] ?>" class="nav-item <?= $isActive ? 'active' : '' ?>">
                    <div class="nav-icon-wrap">
                        <span class="material-symbols-outlined nav-icon" id="nav-badge-<?= $item['id'] ?>"><?= $item['icon'] ?></span>
                        <span class="nav-badge" id="badge-<?= $item['id'] ?>"></span>
                    </div>
                    <span class="nav-label"><?= $item['label'] ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</nav>
