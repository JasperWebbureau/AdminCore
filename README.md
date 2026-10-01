# AdminCore

Framework-onafhankelijke basis voor de modulaire Flexgrid-administratie.

## Huidige inhoud

- verplichte `TenantId` en `TenantContext`;
- `Currency`, `Money` en `TaxRate` met integerberekeningen;
- half-up afronding voor geschaalde hoeveelheden en btw per regel;
- `PublicIdGeneratorInterface` met een cryptografische UUIDv4-implementatie;
- een dunne PDO-transactieadapter met rollback bij iedere `Throwable`;
- een frameworkvrije auditcontext en gevalideerde permission keys;
- versioned scalar-only domeinevents, een pending-eventcollectie en synchrone post-commit eventdispatch;
- een contract voor transactionele tenantgebonden nummerreeksen;
- een Autowire-entity met tenantgebonden unique index en een atomische MySQL/MariaDB-nummerreeksadapter;
- hergebruik van `Flexgrid\Utils\_Time` als injecteerbare tijdbron.
- één afgescheiden `Integration/Flexgrid`-adapter voor het gezamenlijke administratiepaneel. Deze leest uitsluitend geregistreerde, toegankelijke controllermetadata en toont daardoor alleen aanwezige modules.

De domein- en infrastructuurkern van AdminCore bevat bewust geen afhankelijkheden op Invoice, PDF of andere businessmodules. De Flexgrid-controller en template staan expliciet onder `Integration/Flexgrid`, zodat deze presentatieadapter niet door het domein wordt geïmporteerd.

## Gezamenlijk dashboardpaneel

Een administratiemodule meldt zijn link via `administrationPanel`, `administrationLabel`, `administrationRoute` en `administrationPriority` in de bestaande `@FG\Controller`-metadata. AdminCore bouwt hieruit één paneel; een ontbrekende, niet-geregistreerde of voor de gebruiker ontoegankelijke controller verschijnt niet. Modules registreren daarom geen eigen `Flexgridpanel`-template meer.

## Modulebeheer

Een Flexgrid-devuser ziet in het gezamenlijke dashboardpaneel **Modules beheren**. De detailpagina staat op `/Flexgrid/AdminCore/modules`. Zowel bekijken als wijzigen vereist `Auth::get('Flexgrid')->getIsDevUser()`; wijzigingen vereisen daarnaast POST en een geldige CSRF-token.

De catalogus toont lokale mappen en de toegankelijke `Admin*`-repositories van `__GIT_ACCOUNT__`. `__GIT_TOKEN__` is nodig voor private repositories en wordt niet in de pagina of Git-URL gezet. Installatie gebruikt een tijdelijke stagingmap en een gecontroleerde repository-URL. Updates van Git-mappen doen alleen `fetch` plus `merge --ff-only` op de standaardbranch, en stoppen bij lokale wijzigingen of een afwijkende remote. Een lokale module zonder eigen `.git`-map kan expliciet door de Git-versie worden vervangen; de complete oude map blijft dan als verborgen backup naast de modules staan. Vergelijk die backup zelf voor opruimen. Een achtergebleven stagingmap na een mislukte clone wordt niet automatisch gewist.

De schakelaar schrijft naar `Files/Config/AdminModules.json`. Een geïnstalleerde module is standaard actief; `AdminCore` kan niet uit. De status is **installatiebreed**. Controller-routes, Ajax-events, CMS-navigatie, het administratiepaneel en de entity-editor respecteren deze status. Het verwijderen van bestanden of databasegegevens hoort niet bij uitschakelen. Na een nieuwe installatie en na updates met gewijzigde metadata/entities is een bevoegde `force_aw=true`-scan nodig. De pagina voert die scan niet automatisch uit.

## Ontwerp: modulekosten per bedrijf

Installatiestatus en klantrechten zijn twee aparte zaken. Voor betaalde toegang komt later een `tenant_module_entitlement` per `(tenant_id, module_key)`, met onder meer start/einddatum, status, vaste maandprijs in credits en volgende afschrijfmaand. Gebruik de bestaande `TenantId` als stabiele bedrijfsidentiteit en een eigen `CreditManager`-account key per bedrijf; de huidige standaardaccount `default` is daarvoor niet voldoende. De creditkoop-sync moet de aankoop ook aan diezelfde bedrijfsaccount boeken.

Een maandtaak maakt per bedrijf, module en kalendermaand maximaal één charge met een unieke referentie zoals `admin-module:{tenant}:{module}:{YYYY-MM}`. Leg de prijs van die maand vast bij de charge, zodat prijswijzigingen geen historie veranderen. De huidige `CreditManager::charge()` heeft al een `external_id`-controle, maar vóór automatische abonnementen zijn een database-unique constraint en een transactionele, atomaire saldoverlaging nodig. Spreek ook expliciet af wat er bij onvoldoende saldo gebeurt (bijvoorbeeld melding plus korte respijtperiode); verwijder of deactiveer nooit stilzwijgend de installatiecode. Pas daarna de tenantgebonden entitlementcontrole toe op routes, events en bedrijfsgegevens. De bestaande globale schakelaar blijft een aparte technische noodstop.

## Vereisten

- PHP 7.3 of hoger;
- een 64-bits PHP-runtime voor veilige opslag van realistische bedragen in minor units.

## Testen

```text
php flexgrid/Modules/AdminCore/tests/run.php
php flexgrid/flexgrid/tests/Autowire/SchemaIndexDefinitionTest.php
php flexgrid/Modules/AdminCore/tests/NumberSequenceSchemaTest.php
```

De tests gebruiken geen PHPUnit of Composer en kunnen rechtstreeks vanuit de projectroot worden uitgevoerd.

De PDO-test gebruikt SQLite wanneer die driver beschikbaar is en wordt anders overgeslagen. De tweede opdracht test de generieke Autowire-indexdefinitie en de omzetting van property- naar kolomnamen. De derde opdracht is een CLI-only integratietest die de daadwerkelijk door Autowire aangemaakte MySQL/MariaDB-tabel en unique index controleert. Dezelfde PDO-instantie moet later aan repositories en `PdoTransactionManager` worden doorgegeven.

`PdoNumberSequence` gebruikt MySQL/MariaDB `INSERT ... ON DUPLICATE KEY UPDATE` binnen een reeds actieve transactie. De unieke `(tenant_id, sequence_key, period_key)`-index is onderdeel van de Autowire-entity en voorkomt dubbele reserveringen bij gelijktijdige finalisaties.
