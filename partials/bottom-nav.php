<?php
/**
 * Bottom Navigation - Componente Compartilhado
 * Recebe $activePage para destacar o item ativo
 * Uso: <?php $activePage = 'inicio'; include 'partials/bottom-nav.php'; ?>
 */
$navItems = [
    ['id' => 'inicio', 'icon' => 'home', 'label' => 'Início', 'href' => 'dashboard.php'],
    ['id' => 'rotas', 'icon' => 'map', 'label' => 'Rotas', 'href' => 'mapa.php'],
    ['id' => 'ponto', 'icon' => 'schedule', 'label' => 'Ponto', 'href' => 'ponto.php'],
    ['id' => 'clientes', 'icon' => 'people', 'label' => 'Clientes', 'href' => 'consultar.php'],
    ['id' => 'ajustes', 'icon' => 'settings', 'label' => 'Ajustes', 'href' => 'ajustes.php'],
];
?>
<nav class="fixed bottom-0 left-0 right-0 bg-white/90 dark:bg-black/90 ios-blur border-t border-gray-200/50 dark:border-white/10 safe-bottom z-40">
    <div class="flex justify-around items-center h-16 max-w-lg mx-auto px-2">
        <?php foreach ($navItems as $item): ?>
            <?php $isActive = (isset($activePage) && $activePage === $item['id']); ?>
            <a href="<?= $item['href'] ?>" class="flex flex-col items-center justify-center gap-0.5 px-3 py-1 min-w-[64px] <?= $isActive ? 'text-primary' : 'text-gray-400 dark:text-gray-500' ?>">
                <span class="material-symbols-outlined text-2xl" style="<?= $isActive ? "font-variation-settings: 'FILL' 1;" : '' ?>"><?= $item['icon'] ?></span>
                <span class="text-[10px] font-medium"><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
