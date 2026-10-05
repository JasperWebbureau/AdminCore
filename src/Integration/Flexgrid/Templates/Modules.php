<?php
$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<grid>
    <div class="panel" style="--cw:12;--cw-sm:12;--cw-xs:12">
        <div class="panel__header"><h3>Administratiemodules</h3></div>
        <div class="panel__body">
            <p>Installeer of update modules uit het ingestelde GitHub-account. Installatie actief geldt voor iedereen. Met Interface uitzetten verberg je een module alleen voor de gekozen CMS-gebruiker; de code blijft beschikbaar. Staat de installatie uit, dan is de interface voor iedereen geblokkeerd.</p>
            <?php if ($users !== []) { ?>
                <form method="get" action="<?=$escape($baseUrl)?>">
                    <label style="--cw:6;--cw-sm:8;--cw-xs:12">CMS-gebruiker
                        <select name="user">
                            <?php foreach ($users as $user) { ?>
                                <option value="<?=$user['id']?>"<?=$user['id'] === $selectedUserId ? ' selected' : ''?>><?=$escape($user['label'])?></option>
                            <?php } ?>
                        </select>
                    </label>
                    <div style="--cw:3;--cw-sm:4;--cw-xs:12"><button class="button button-secondary" type="submit">Toon instellingen</button></div>
                </form>
            <?php } else { ?>
                <p>Er zijn geen gewone Flexgrid-gebruikers om de interface voor in te stellen.</p>
            <?php } ?>
            <?php if ($message !== '') { ?><p role="status"><?=$escape($message)?></p><?php } ?>
            <?php if ($warning !== '') { ?><p role="alert"><?=$escape($warning)?> De lokaal aanwezige modules blijven hieronder zichtbaar.</p><?php } ?>
            <?php if ($error !== '') { ?><p role="alert"><?=$escape($error)?></p><?php } ?>
            <?=$table?>
        </div>
        <div class="panel__footer">Na installatie of een update met gewijzigde metadata: voer een bevoegde <code>force_aw=true</code>-scan uit.</div>
    </div>
</grid>
