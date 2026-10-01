<?php
$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<grid>
    <div class="panel" style="--cw:12;--cw-sm:12;--cw-xs:12">
        <div class="panel__header"><h3>Administratiemodules</h3></div>
        <div class="panel__body">
            <p>Installeer of update modules uit het ingestelde GitHub-account. De schakelaar bepaalt welke geïnstalleerde modules op deze installatie actief zijn.</p>
            <?php if ($message !== '') { ?><p role="status"><?=$escape($message)?></p><?php } ?>
            <?php if ($warning !== '') { ?><p role="alert"><?=$escape($warning)?> De lokaal aanwezige modules blijven hieronder zichtbaar.</p><?php } ?>
            <?php if ($error !== '') { ?><p role="alert"><?=$escape($error)?></p><?php } ?>
            <?=$table?>
        </div>
        <div class="panel__footer">Na installatie of een update met gewijzigde metadata: voer een bevoegde <code>force_aw=true</code>-scan uit.</div>
    </div>
</grid>
