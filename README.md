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
