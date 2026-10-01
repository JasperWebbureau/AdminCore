<?php
$h = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
$editable = ($periodRoute ?? '') !== '';
$allowAllYears = in_array($periodRoute ?? '', ['quotes', 'expenses', 'invoices'], true);
$home = rtrim(__DOMAIN__, '/') . '/Flexgrid/AdminDashboard/dashboard';
$periodLabel = $period['year'] === null ? 'Alle jaren' : (($period['quarter'] === null ? '' : 'Q' . $period['quarter'] . ' ') . $period['year']);
$periodDescription = $period['year'] === null ? 'alle jaren' : (($period['quarter'] === null ? 'alle kwartalen' : 'kwartaal ' . $period['quarter']) . ' van ' . $period['year']);
?>
<div class="admin-shared-header">
    <div class="admin-shared-header__local">
        <?php foreach (($buttons ?? []) as $button) { ?>
            <?php if ($button instanceof \Flexgrid\Response\TemplateResponse) { echo $button; continue; } ?>
            <?php if (is_array($button)) { ?><a class="button <?=$h($button['class'] ?? 'button-secondary')?>" href="<?=$h($button['href'] ?? '')?>"><i class="<?=$h($button['icon'] ?? 'fas fa-arrow-right')?>" aria-hidden="true"></i> <?=$h($button['label'] ?? '')?></a><?php } ?>
        <?php } ?>
    </div>
    <nav class="admin-shared-header__nav" aria-label="Administratienavigatie">
        <a class="button button-secondary" href="<?=$h($home)?>"><i class="fas fa-house" aria-hidden="true"></i> Home</a>
        <details class="admin-shared-header__menu">
            <summary class="button button-secondary"><i class="fas fa-grid-2" aria-hidden="true"></i> Modules <i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
            <div class="admin-shared-header__popover" role="group" aria-label="Administratiemodules">
                <?php foreach (($links ?? []) as $link) { ?>
                    <a href="<?=$h(rtrim(__DOMAIN__, '/') . $link['path'])?>"><i class="<?=$h($link['icon'])?>" aria-hidden="true"></i> <?=$h($link['label'])?></a>
                <?php } ?>
            </div>
        </details>
        <?php if ($editable) { ?>
            <details class="admin-shared-header__period">
                <summary class="button button-secondary" aria-label="Periode wijzigen: <?=$h($periodDescription)?>"><i class="fas fa-calendar-days" aria-hidden="true"></i> <?=$h($periodLabel)?> <i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
                <div class="admin-shared-header__popover admin-shared-header__popover--period">
                    <form ajax="true" action="<?=$h($periodAction)?>" method="post">
                        <input type="hidden" name="route" value="<?=$h($periodRoute)?>" style="--cw:12">
                        <label class="admin-field" style="--cw:12"><span>Jaar</span><select name="year" data-admin-period-year><?php if ($allowAllYears) { ?><option value=""<?=$period['year'] === null ? ' selected' : ''?>>Alles</option><?php } ?><?php for ($year = min(2200, max((int)date('Y'), (int)$period['year']) + 1); $year >= 2000; $year--) { ?><option value="<?=$year?>"<?=$year === $period['year'] ? ' selected' : ''?>><?=$year?></option><?php } ?></select></label>
                        <label class="admin-field" style="--cw:12"><span>Kwartaal</span><select name="quarter" data-admin-period-quarter<?=$period['year'] === null ? ' disabled' : ''?>><option value=""<?=$period['quarter'] === null ? ' selected' : ''?>>Alles</option><?php for ($quarter = 1; $quarter <= 4; $quarter++) { ?><option value="<?=$quarter?>"<?=$quarter === $period['quarter'] ? ' selected' : ''?>>Q<?=$quarter?></option><?php } ?></select></label>
                        <button class="button button-publish" type="submit" style="--cw:12">Periode toepassen</button>
                    </form>
                </div>
            </details>
        <?php } else { ?>
            <span class="button button-secondary admin-shared-header__period-readonly" title="Deze periode is op deze pagina niet bewerkbaar" aria-label="Geselecteerde periode: <?=$h($periodDescription)?>, hier niet bewerkbaar"><i class="fas fa-calendar-days" aria-hidden="true"></i> <?=$h($periodLabel)?> <i class="fas fa-lock" aria-hidden="true"></i></span>
        <?php } ?>
    </nav>
</div>
