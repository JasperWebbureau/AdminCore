<?php
$links = is_array($links ?? null) ? $links : [];
$h = function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
?>
<?php if ($links !== [] || !empty($canManageModules)) { ?>
    <div class="panel admin-core-panel" style="--cw:4;--cw-sm:8;--cw-xs:12">
        <div class="panel__header">
            <h4><i class="fas fa-grid-2" aria-hidden="true"></i> <?=t('admin_core_panel_title', 'Administratie')?></h4>
            <span class="dashboard-panel__count"><?=count($links)?></span>
        </div>
        <div class="panel__body">
            <nav class="dashboard-panel__links" aria-label="Administratiemodules">
                <?php foreach ($links as $link) { ?>
                    <a class="dashboard-panel__link" href="<?=$h(rtrim(__DOMAIN__, '/') . $link['path'])?>">
                        <span class="dashboard-panel__icon"><i class="<?=$h($link['icon'])?>" aria-hidden="true"></i></span>
                        <span><?=$h($link['label'])?></span>
                        <i class="fas fa-chevron-right dashboard-panel__arrow" aria-hidden="true"></i>
                    </a>
                <?php } ?>
                <?php if (!empty($canManageModules)) { ?>
                    <a class="dashboard-panel__link" href="<?=$h(rtrim(__DOMAIN__, '/') . '/Flexgrid/AdminCore/modules')?>">
                        <span class="dashboard-panel__icon"><i class="fas fa-cubes" aria-hidden="true"></i></span>
                        <span>Modules beheren</span>
                        <i class="fas fa-chevron-right dashboard-panel__arrow" aria-hidden="true"></i>
                    </a>
                <?php } ?>
            </nav>
        </div>
    </div>
<?php } ?>
