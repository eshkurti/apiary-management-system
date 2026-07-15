# Honigmanufaktur Lindenhof — Apiary Compliance & Ecommerce Platform

A Yii2/PHP system for a German honey producer that ties field-recorded hive treatments directly to batch release, product publication, and lot-level recall traceability — so a single connected dataset replaces the paper trail that regulated food producers normally run on.

Built for **120 colonies across 6 apiary stands** in Landkreis Hof, Bavaria, covering seasonal migratory beekeeping, direct-to-consumer retail, and wholesale accounts.

## The problem this solves

German beekeepers operating at any commercial scale have to satisfy real regulatory requirements — EU Regulation 2019/6 Art. 108(2) (mandatory treatment records: supplier, stand, colony, Wartezeit, duration), HonigV water-content limits per honey variety, BienSeuchV annual disease-treatment flags, and the EU Honey Directive 2024/1438. In practice, most small producers track all of this on paper (the *Bestandsbuch* / *Stockkarte* forms), completely disconnected from whatever they use to sell the honey. Existing beekeeping software (BeeInTouch, Deutscher Imkerbund tools) manages the paperwork or the shop — not both, and never as one gated pipeline.

This system's core idea: **a treatment record programmatically gates batch release, which gates product publication, which guarantees recall traceability on every order line.** You cannot publish honey for sale unless the batch it came from has cleared its withdrawal period, and every unit sold can be traced back to the exact colonies and treatments behind it.

## Architecture

A modular monolith — three bounded modules sharing one relational schema (15 tables, MariaDB):

- **Production Management** — `ApiaryStand`, `Colony`, `ColonyMovement`, `Inspection`, `Treatment`, `Batch`. Records what happened in the field. No compliance decisions are made here.
- **Compliance** — Release Gate, Bestandsbuch export (reproduces the official LWG Bayern paper template column-for-column), Stockkarte export, recall trace. The *only* place a batch release decision can be made.
- **Ecommerce** — Products, Kanban-style order pipeline, Customers, Dashboard.

`ApiaryStand` and `Colony` are deliberately separate entities linked by a movement log — an earlier design conflated the two, and un-conflating them became a defining architectural correction.

## Engineering decisions worth calling out

A few things that aren't obvious just from reading the code, because they came from getting something wrong first:

**Single release authority.** The release action lives in exactly one controller (`ComplianceController`). An earlier version allowed release from two different places; that duplication was found and removed. Production Management can show a read-only gate summary, but it cannot release a batch.

**Withdrawal check uses harvest date, not "today."** `Batch::getReleaseGateChecks()` checks whether a treatment's `wartezeit_expiry` falls after the batch's `harvest_date` — not the current date. A treatment applied after harvest can't retroactively contaminate honey that's already been extracted; checking against "today" was the original (wrong) implementation.

**Disease flags don't auto-clear.** Flagging a source colony transitions any released batch sourcing it to `review_required` and unpublishes its products. Clearing the flag does not auto-revert the batch — release requires a manual re-review, and the review note persists as an audit trail even after re-release.

**Sold-out ≠ unpublished.** When stock hits zero, a product disappears from the shop catalogue, but its detail page stays reachable for lot traceability. Only a deliberate recall or mislabelling unpublishes a product — never routine stock depletion.

**Batch → Product is manual and repeatable.** One batch can become several products at different prices (retail 500g jars, wholesale bulk), created through one shared form regardless of entry point. Release doesn't auto-generate a product.

**Honey variety and container size are constrained dropdowns, not free text** — an earlier free-text version broke HonigV water-content validation (20% standard / 23% Heidehonig).

**`treatment_product` is a seeded reference table** of real approved German Varroa/Tracheenmilbe products (Oxuvar, Apivar, MAQS, Thymovar, etc.) that autofills withdrawal days, duration, quantity, and supplier — built to minimize manual entry for a beekeeper standing at a hive.

## Tech stack

PHP 8.3 · Yii2 (Advanced Template — separate frontend/backend/console apps sharing a common layer) · MariaDB · mPDF for compliance PDF exports · Codeception for testing · RBAC with permission-based (not role-based) controller checks throughout · `strict_types` and full return-type declarations across the codebase.

## Testing

39 passing Codeception tests: 14 journey-style tests across two classes (`HarvestToReleaseCest` — 10 backend functional tests covering the full harvest → treatment → release → recall path, `ShopCheckoutCest` — 4 frontend functional tests) plus 25 adapted Yii2 template tests. Tests are written as user journeys with explicit failure cases, not just happy-path coverage.

## Database access model

Three least-privilege MariaDB accounts rather than one shared credential: an app account for normal read/write, an admin account, and a dedicated read-only reporting account (`lindenhof_report`) wired specifically into the Bestandsbuch, Stockkarte, and recall-trace read paths in `ComplianceController` and `PdfExportService` — so compliance reporting can never write back into production data.

## Getting started

```bash
composer install
php init --env=Development --overwrite=All
```

Create a MariaDB database, point `common/config/main-local.php` at it, then:

```bash
php yii migrate
```

That's 20 migrations total: schema, RBAC roles/permissions, the `treatment_product` reference data, and a full demo dataset (6 apiary stands, 12 colonies, 8 treatments, 2 batches — one released, one pending — a published product, customers, and orders). They also seed four ready-to-use accounts, all password `COSD@groupb`: `admin` (administrator), `headBeekeeper`, `fieldBeekeeper`, and `customer` — no manual account creation needed.

Then run the two applications, each on its own port:

```bash
php -S 127.0.0.1:8081 -t backend/web   # staff admin — log in as admin / COSD@groupb
php -S 127.0.0.1:8080 -t frontend/web  # public shop
```

[`SETUP_GUIDE.md`](SETUP_GUIDE.md) has the full walkthrough — MariaDB user setup, a local cookie-path gotcha specific to running both apps off PHP's built-in server, and a six-step smoke test to confirm the whole harvest → compliance gate → shop product → order pipeline is wired up correctly end to end.

Run the test suite with:

```bash
./vendor/bin/codecept run --env php-builtin
```

## Deployment

Beyond running locally, this was also deployed to a university-provided VM as coursework infrastructure, using the Yii2 Advanced Template's environment-split config (`environments/dev` vs `environments/prod`) rather than hand-editing files per-target. Production runs the three-account database model described above rather than a single shared credential, and the deployed config wires the read-only reporting account into the compliance export paths specifically — not a blanket permission grant.

That deployment sits on a network-restricted university host, so it isn't something an outside visitor can browse to — the local setup above is the way to actually run and click through the system.

## Documentation

[`docs/`](docs/) contains the full IEEE-format paper written for this project (PDF and the original Word source), covering the specification (12 user stories, 23 functional requirements with a full traceability matrix), architecture rationale, validation approach, and project management process, plus the internal requirements spec the codebase was built from.

## Author

Emil Shkurti
