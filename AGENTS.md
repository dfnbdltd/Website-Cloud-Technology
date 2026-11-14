# Cloud Technology BD — Coding Agent Guide

**Bismillah.** Read this guide before inspecting or changing this repository.
It is written for AI coding agents working on the existing application.

## 1. Project Context and Source of Truth

This is an existing Laravel web application. Treat the current codebase as the
source of truth; do not redesign it as a new application.

Use these files for dependency and build information:

- `composer.json` — declared PHP and Composer dependencies.
- `composer.lock` — resolved PHP dependency versions.
- `package.json` — declared frontend dependencies and scripts.
- `webpack.mix.js` — frontend asset entry points and build behavior.

Confirmed baseline:

- Laravel `^6.20.26`; the lockfile currently resolves `laravel/framework` to
  `6.x-dev`.
- PHP `^7.2.5|^8.0`.
- Laravel Mix `^5.0.1`, Webpack, Sass, Axios, and Lodash.
- PHPUnit is constrained to `^8.5.8|^9.3.3`; the lockfile currently contains
  PHPUnit `9.6.37`.
- Composer is the backend dependency manager. No npm, Yarn, or pnpm lockfile
  is currently present.

Do not upgrade Laravel, PHP, Composer packages, Node packages, or build tools
unless the user explicitly requests it.

## 2. Repository Map

Use only the relevant area for each task:

- `app/` — application PHP code: controllers, middleware, models, jobs, mail,
  services, rules, providers, exceptions, and support code.
- `routes/` — `web.php`, `api.php`, `console.php`, and `channels.php`.
- `resources/` — Blade views, JavaScript, Sass, and translations.
- `public/` — public entry point and compiled/static assets.
- `config/` — Laravel and application configuration.
- `database/` — migrations, factories, seeds, and SQL assets.
- `bootstrap/` — framework bootstrap and generated cache.
- `storage/` — runtime files, uploads, cache, and logs.
- `tests/` — `Feature/` and `Unit/` tests.
- `artisan` — Laravel CLI entry point.
- `webpack.mix.js` — Laravel Mix configuration.

The current frontend pipeline compiles `resources/js/app.js` to `public/js` and
`resources/sass/app.scss` to `public/css`. Follow this pipeline rather than
introducing another frontend architecture.

## 3. Non-Negotiable Safety Rules

### Secrets and privacy

- Never open, print, copy, or transmit `.env` contents.
- Use `.env.example` only when environment configuration must be understood.
- Never expose passwords, API keys, mail credentials, database credentials, or
  tokens in source code, logs, responses, patches, or explanations.
- Never add hardcoded secrets.

### Database and data

- Inspect relevant migrations, models, SQL files, and configuration before
  changing schema or queries.
- Never run `migrate:fresh`, `db:wipe`, broad delete statements, or equivalent
  destructive operations without explicit user approval.
- Ask before running migrations or seeders against an existing database when
  they could change or remove user data.
- Do not delete records, tables, migrations, or SQL assets without approval.
- Preserve existing API and database compatibility.

### Git and deployment

- Check `git status` before significant work and preserve unrelated user edits.
- Never reset, checkout, clean, or discard changes not made by you.
- Do not commit, push, switch/delete branches, or deploy without explicit
  authorization.
- Do not overwrite unfamiliar files until their purpose is clear.

## 4. Engineering Principles

- Make the smallest change that fully solves the request.
- Follow the existing Laravel MVC/service architecture and local style.
- Reuse existing controllers, models, middleware, services, rules, views,
  components, and helpers before creating new ones.
- Search for an existing route, model, migration, helper, or component before
  adding a duplicate.
- Do not refactor unrelated code or rewrite working code for style alone.
- Do not introduce a library when existing project code can solve the problem.
- Keep behavior backward-compatible unless a breaking change was requested.
- Validate user-controlled input and follow existing authorization patterns.
- Avoid N+1 queries and unnecessary database calls.
- Keep UI changes responsive on mobile, tablet, and desktop.

## 5. Focused Investigation Workflow

Before changing anything:

1. Restate the requested behavior and identify its likely layer: route,
   controller, validation, model, service, view, asset, or test.
2. Check `git status` and note pre-existing changes.
3. Use targeted search before opening files. Read only the relevant route,
   controller, model/service, view, asset, and test files.
4. Check migrations and database conventions before schema work.
5. Check dependency files before using framework or build commands.
6. For larger tasks, state a short implementation plan before editing.
7. Implement the minimum coherent change.
8. Review the diff for accidental edits, secrets, debug code, and unrelated
   formatting changes.
9. Run the narrowest relevant verification and report exactly what ran.

### Efficient reading rules

- Do not scan the whole repository by default.
- Do not repeatedly read unchanged files.
- Prefer targeted searches and directory listings over broad recursive reads.
- Read only needed sections of large files.
- Never assume a file exists; check first.
- If more files are needed, explain why they are relevant.

## 6. Laravel Conventions

- Check `routes/web.php` and `routes/api.php` before creating routes.
- Follow existing controller and form-request validation patterns.
- Reuse existing authentication, authorization, middleware, and policies.
- Use Eloquent models and existing relationships consistently.
- Check for an existing migration before creating a new one; do not duplicate
  schema changes.
- Keep business logic in established services/domain locations rather than
  putting large workflows in controllers or Blade templates.
- Use Blade and the existing `resources/` structure for server-rendered UI.
- Keep translations in `resources/lang` when the feature supports localization.
- Follow existing mail, queue, filesystem, and configuration conventions.

## 7. Frontend Conventions

- Inspect `package.json` and `webpack.mix.js` before frontend changes.
- Reuse existing JavaScript modules, Sass styles, Blade components, and
  variables before adding duplicates.
- Put source changes under `resources/`; do not hand-edit generated output in
  `public/` unless the task specifically concerns a static public file.
- Use the existing Laravel Mix/Webpack/Sass pipeline.
- Avoid adding a frontend framework or package without explicit justification.
- Test responsive behavior and preserve existing UI behavior.

## 8. Verification

Use the narrowest appropriate checks; do not run the entire suite by default.
Examples include:

- Backend: `php artisan test --filter=RelevantTest` or the configured PHPUnit
  command.
- A focused PHPUnit file when the change affects one test area.
- Frontend: the existing `npm run development` or `npm run production` script,
  after checking dependencies and whether generated assets are tracked.
- Targeted PHP or static checks when available.

Never claim a test or build passed unless it was actually executed. If a check
cannot run because of missing dependencies, environment, database, or runtime
requirements, report that limitation clearly.

## 9. Directories and Files to Avoid by Default

Do not inspect or modify these unless the task specifically requires them:

- `vendor/` — installed Composer dependencies.
- `node_modules/` — installed frontend dependencies.
- `.git/` — repository internals.
- `storage/logs/` — potentially sensitive runtime logs.
- `bootstrap/cache/` — generated framework cache.
- `public/build/` — generated frontend output, if present.
- `.env` — secrets and environment-specific credentials.
- Root temporary/debug artifacts such as `tmp-*`, `tmp_*.php`, and preview or
  output files, unless explicitly part of the task.

## 10. Completion Report

Every completed task should end with:

- What was implemented.
- Files changed, with paths.
- Tests/checks actually run and their results.
- Assumptions, limitations, or follow-up work.

If the user requested analysis only, do not edit files. If implementation was
requested, do not stop after analysis: make the approved change, verify it, and
report the result.
