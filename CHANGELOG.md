# Changelog

## 0.4.0 - 2026-09-21

- Eén gezamenlijk Flexgrid-dashboardpaneel toegevoegd onder de expliciete `Integration/Flexgrid`-adapter.
- Het paneel ontdekt actieve administratiemodules via gevalideerde controllermetadata en respecteert de bestaande toegangscontrole.
- Navigatielabel, route, icoon en volgorde blijven eigendom van de betreffende module; AdminCore bevat geen hardgecodeerde businessmodulelijst.
- De paneelstijl gebruikt uitsluitend gedeelde Admin UI-variabelen en werkt responsief in één of twee kolommen.

## 0.3.1 - 2026-09-18

- CLI-integratietest toegevoegd voor de echte nummerreekstabel, kolomtypen, `NOT NULL`-garanties en unique index.
- De schema-integratietest synchroniseert dezelfde definitie tweemaal om idempotentie van de databasebouwsteen te bewaken.

## 0.3.0 - 2026-09-17

- Flexgrid Autowire ondersteunt nu `BIGINT`, `required=true` en declaratieve named unique/composite indexes.
- Autowire-entity en repository voor tenantgebonden nummerreeksen toegevoegd.
- Atomische MySQL/MariaDB-implementatie van `NumberSequenceInterface` toegevoegd.
- Nummerreservering buiten een actieve transactie wordt expliciet geweigerd.

## 0.2.0 - 2026-09-17

- PDO-transactieadapter en nummerreekscontract toegevoegd.
- Auditcontext en gevalideerde module-permission keys toegevoegd.
- Versioned domeinevent-envelope, pending-eventcollectie en synchrone post-commit eventdispatch toegevoegd.
- Frameworkvrije infrastructuurtests en optionele SQLite-transactietest toegevoegd.

## 0.1.0 - 2026-09-17

- Eerste native Flexgrid-modulebasis toegevoegd.
- Tenantcontext, geld, valuta, btw en UUIDv4-generatie geïmplementeerd.
- Frameworkvrije tests voor de Core-value objects toegevoegd.
- Bestaande `_Time` geschikt gemaakt voor timestamp `0`.
