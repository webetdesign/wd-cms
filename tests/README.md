# Doctrine attribute regression

```sh
php tests/doctrine-attributes.php /path/to/integration/vendor/autoload.php
```

Standalone test: no Symfony kernel, EntityManager, SQL, schema generation or database connection. The prepended CMS namespace loader must load this checkout, not the CMS package from the supplied vendor.

## Baseline

`fixtures/doctrine-attributes-baseline.json` was captured **before cleanup**, at commit `9cf53fc30992f2552378ac6be238a161f316cc8e`, with:

- PHP 8.5.9
- doctrine/orm 2.20.13
- doctrine/annotations 2.0.2
- gedmo/doctrine-extensions 3.22.2
- webetdesign/wd-seo-bundle 7.4.2 (external traits)

It compares all public `ClassMetadata` values produced by `AttributeDriver`, plus instantiated Gedmo attributes, using strict array equality. Associative keys are sorted; list ordering and values are preserved. Reflection is represented by class/property names rather than filesystem paths.

Coverage: CmsPage, CmsPageDeclination, CmsSharedBlock and a fixture using the deprecated CMS SeoAwareTrait: **4 classes, 56 fields, 17 associations, 50 Gedmo declarations**, including external trait mappings. The trait fixture is only a driver probe, not a persistable entity (no identifier).

The real `AnnotationReader` separately checks the three entities' own declarations and CMS SeoAwareTrait. External traits' legacy PHPDoc is not part of this cleanup. It failed on the original source because it still parsed redundant ORM/Gedmo runtime metadata; the metadata baseline itself already matched.

`--snapshot` prints current evidence only; it does not rewrite the baseline. Do not regenerate the fixture to hide a mapping change. This exact public-metadata baseline is for the recorded ORM 2.20 runtime; ORM 3 changes its internal mapping representations and requires separate integration verification, not replacement of this baseline.

## ORM lifecycle/API regression

```sh
php tests/doctrine-lifecycle.php /path/to/integration/vendor/autoload.php
# Optional individual case: extension, shared-block, menu-ignore,
# menu-root, site-initialization
```

Five cases run against real dependencies with the candidate namespace loader prepended. Verified with ORM **2.20.13** and **3.7.4** (DBAL 3.10.6, Persistence 4.2.0 for the latter):

- Real extension `load()` on an uncompiled ContainerBuilder: user class configuration, single-table route inheritance, base/override discriminators and discriminator column.
- SharedBlockListener with real PostLoadEventArgs: text, textarea, WYSIWYG, indexable custom content, non-indexable/unknown content, empty block and unrelated entity.
- MenuAdminListener: unrelated entity, disabled initialization and root item creation, including name/menu linkage and exactly one flush **request**.
- SiteAdminListener prePersist: all page/menu initialization flag combinations, filtered/default homepage templates and entity/menu tree linkage.

RecordingEntityManager subclasses the real EntityManager, skips its constructor and records persist/flush; no Connection, SQL, UnitOfWork or real flush exists. Site listener construction is bypassed only to supply its site class and real ParameterBag: router/kernel/filesystem and route-cache events are not exercised.

Each API replacement had a recorded red then green run under ORM 2. ORM 2 retains aliases, so two narrowly scoped guards expose what it would otherwise hide: the extension token check rejects ClassMetadataInfo after real configuration, and reflection requires the dedicated PostLoadEventArgs parameter after real indexing. Menu/site additionally use **real Doctrine Persistence LifecycleEventArgs**, the ORM 3 base class without legacy aliases; the same paths also run with dedicated real ORM PostPersistEventArgs/PrePersistEventArgs. No vendor classes are replaced or faked.

The extension now uses ClassMetadata, SharedBlockListener uses PostLoadEventArgs, and menu/site listeners use getObject()/getObjectManager(). Their original initialization/indexing behavior remains intact.

## ORM 3 semantic metadata invariants

```sh
php tests/doctrine-attributes-orm3.php /path/to/orm3/vendor/autoload.php
```

This separate test runs real AttributeDriver/Gedmo AttributeReader against the same **4 classes, 56 fields, 17 associations and 50 Gedmo declarations**. It checks property sets, recorded field/association values, tables, identifiers, generation/inheritance, repositories, lifecycle configuration and exact Gedmo declarations. It reads but never rewrites the ORM 2 baseline.

ORM 3 mapping objects are inspected through public properties and association methods, not deprecated array access. New internal defaults are not mistaken for mapping changes; the recorded mapping values are all checked. SortDirection::Ascending/Descending is converted to ASC/DESC only for semantic comparison. One additional representation difference is explicit: ORM 3 marks many-to-many join-table PK columns nullable=false during mapping. ORM 2 SchemaTool::gatherRelations already sets their primary key, and DBAL Table::setPrimaryKey forces notnull=true; the test asserts this non-nullable PK invariant rather than ORM 2's intermediate nullable=true. No schema/SQL is generated or executed.

The ORM 3 runtime lacks doctrine/annotations: no legacy AnnotationReader is available, not a successful legacy-reader test. AttributeDriver/Gedmo invariants nevertheless execute; the original real legacy-reader test remains covered on ORM 2.

## Remaining scope and API audit

Composer accepts `^2.20 || ^3.3`; the minimum 3.3 preserves active PARTIAL DQL queries. The runtime verification above used 3.7.4, not every accepted ORM version. These standalone tests do not claim full bundle/app boot, database persistence, cache warmup or all admin/controller paths.

A read-only lexical scan of 151 `src/` PHP files found no additional active removed APIs among the audited ORM 3 upgrade symbols/methods. Remaining hits were classified rather than refactored:

- Unused ClassMetadataInfo import in CmsSonataFormBuilderHelper and PHPDoc/import-only old ORMException references in CmsDuplicateSiteCommand/SiteAdminListener remain; these alone are not runtime blockers.
- RouteAdminListener imports the supported **Persistence**, not removed ORM, LifecycleEventArgs.
- Admin/breadcrumb getEntityManager calls target CMS-defined admin methods, not Doctrine event args.
- The two active PARTIAL selections in CmsPageAdminController still require the existing ORM 3.3 minimum. Flush calls found have no entity argument.

Lexical inspection cannot prove absence of dynamic APIs or compatibility of every integration dependency; broader application QA remains separate.

No other runtime annotations were found in this bundle's `src/` PHPDoc after cleanup. External Gedmo TimestampableEntity still has legacy annotations and is intentionally untouched.

Official migration reference: https://github.com/doctrine/orm/blob/3.3.0/UPGRADE.md (removed annotations driver, ClassMetadataInfo, LifecycleEventArgs, ORMException; PARTIAL compatibility from 3.3). ORM 3 lifecycle events inherit Doctrine Persistence LifecycleEventArgs with getObject()/getObjectManager().

## Symfony 7 controller and bundle build regressions

```sh
php tests/controller-declination.php /path/to/integration/vendor/autoload.php
php tests/bundle-build.php /path/to/integration/vendor/autoload.php
```

Both standalone tests prepend the candidate namespace loader and assert the source filename. No application kernel, database, secrets or network are used.

- Declination: eight cases using a real ContainerBuilder, RequestStack (main + current subrequest), CmsPage/CmsRoute/CmsPageDeclination. Exact path, ignored query, configured lowercase extension with/without query, disabled extension exact match/non-match, uppercase extension and unknown path assert entity identity or null. The inherited request_stack service subscription is checked. Only the removed AbstractController::get shortcut is replaced with access through its existing injected container; matching and CMS configuration remain unchanged.
- Build: real Symfony DebugClassLoader captures the specific future native void return-type deprecation, then real build() must register BlockPass, TemplatePass and ConfigurationPass in order. This is not a claim that those passes or the whole application compile successfully.

The exact-path case failed before the controller fix with undefined BaseCmsController::get(). The build test separately failed before adding : void with the actual Symfony deprecation. Additional declination cases characterize unchanged behavior; they require no additional production changes. Verified on PHP 8.2.27, Symfony 7.4.20 and ORM 3.7.4. No universal compatibility or complete application QA is implied.
