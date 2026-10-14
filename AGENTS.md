# Laravel Project Instructions

## Scope

These instructions apply to the entire repository. Follow more specific `AGENTS.md` files when a subdirectory provides one.

This project targets:

- PHP 8.5
- Laravel 13
- Blade and Bootstrap-based admin pages
- jQuery DataTables with server-side processing
- Select2 with paginated AJAX endpoints
- Static, stateless service and repository APIs

Preserve established domain names, route names, translations, authorization rules, and database behavior. Do not refactor unrelated code while implementing a focused change.

## Required Packages

The application uses these packages:

- Runtime: `laravel/telescope`
- Runtime: `yajra/laravel-datatables-fractal`
- Development only: `fruitcake/laravel-debugbar`
- Development only: `barryvdh/laravel-ide-helper`

For a new installation, use Composer to select the latest stable releases compatible with PHP 8.5 and Laravel 13; do not copy constraints from an older Laravel project blindly.

```bash
composer require laravel/telescope yajra/laravel-datatables-fractal
composer require --dev fruitcake/laravel-debugbar barryvdh/laravel-ide-helper
```

Keep `composer.json` aligned with this baseline:

```json
{
    "require": {
        "php": "^8.5",
        "laravel/framework": "^13.0"
    }
}
```

After dependency changes, run `composer validate` and relevant tests. Use `composer why-not` before forcing a conflicting version.

Package rules:

- Install Telescope with `php artisan telescope:install`, migrate its tables, and restrict dashboard access outside local environments.
- Enable Telescope watchers selectively in production and prune stored entries on a schedule.
- Never enable Debugbar in production. Production must use `APP_DEBUG=false`.
- Treat IDE Helper output as generated code. Regenerate it through Artisan commands; do not hand-edit generated helper files.
- Use Yajra DataTables for server-side list endpoints. Use Laravel JSON resources or transformers to define the row payload.

## General PHP and Laravel Style

- Follow PSR-12 and format PHP with Laravel Pint.
- Add `declare(strict_types=1);` to new PHP files. Match surrounding files when editing an existing area that has not adopted strict types.
- Use one class per file, explicit imports, typed parameters, typed properties, and return types.
- Prefer enums for closed sets of values and route model binding for model identifiers.
- Prefer named arguments when a method has several optional parameters or multiple arguments of the same scalar type.
- Use `===` and `!==` unless loose comparison is deliberately required.
- Use braces for all conditionals and loops.
- Use descriptive camelCase variable and method names. Use StudlyCase class names and SCREAMING_SNAKE_CASE enum cases. Preserve an existing legacy class name such as `OrderKYC_File` when compatibility requires it; new names should follow PSR conventions, such as `OrderKycFileRepository`.
- Use translation keys for user-facing text. Do not hard-code UI messages in controllers, resources, or Blade views.
- Do not read from `env()` outside configuration files.
- Never use mass assignment with unvalidated input. Prefer validated Form Request data or explicit assignments.
- Keep comments focused on why a choice exists. Do not narrate obvious code.

## Application Flow

Use this separation for admin list pages:

```text
HTTP request
  -> controller authorization and request mapping
  -> immutable filter DTO
  -> static repository query
  -> JSON resource/transformer
  -> Yajra DataTables JSON
  -> Blade DataTable
```

Use this separation for writes:

```text
HTTP request
  -> validation and authorization
  -> controller orchestration
  -> static service business operation
  -> repository/model persistence
  -> redirect or JSON response
```

Controllers coordinate HTTP concerns, services own business operations, repositories own reusable data access, resources own response presentation, and Blade owns markup.

## Controllers

Place admin controllers under `App\Http\Controllers\Admin`. Extend the project's admin `BaseController`. When controller middleware is declared on the class, implement `HasMiddleware` and map permission enums to exact actions.

```php
final class ClientController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            self::setPermissionToController(PermissionEnum::CLIENT_LIST, ['index']),
            self::setPermissionToController(PermissionEnum::CLIENT_CREATE, ['create']),
            self::setPermissionToController(PermissionEnum::CLIENT_EDIT, ['edit']),
            self::setPermissionToController(PermissionEnum::CLIENT_DELETE, ['destroy']),
        ];
    }
}
```

Controller rules:

- Type every action parameter and return value, including unions such as `JsonResponse|View` when an action has HTML and AJAX responses.
- Keep actions short. Map request input to a DTO, call a repository or service, and return a response.
- Use route model binding, for example `edit(Client $client)`.
- Use Form Request classes for non-trivial create/update validation. A private controller validation method is acceptable only when matching a small established CRUD module.
- Put business rules, multi-model updates, file replacement, notifications, and job dispatch decisions in services.
- Use `DB::transaction()` in the service when a business operation must commit atomically.
- Dispatch slow or externally visible work to a job after the database state is valid. When needed, dispatch after commit.
- Return redirects with named routes and translated flash messages after successful HTML form submissions.
- Return a consistent JSON envelope for JSON mutations.
- Do not catch exceptions merely to hide them. Catch only when adding useful context, compensating for a failure, or converting to an intentional domain/HTTP error.

### Combined Admin Form Actions

Admin HTML CRUD forms use one `create` action for both displaying and submitting the create form, and one `edit` action for both displaying and submitting the edit form, following `WebsiteSettingController`.

- Register `create` and `edit` with `Route::match(['post', 'get'], ...)`.
- Branch on `$request->isMethod('post')`: validate and persist on POST, otherwise return the form view.
- Do not add separate `store` or `update` actions or routes for admin HTML CRUD forms unless the user explicitly requests a REST-style contract.
- Map create permission middleware only to `create` and edit permission middleware only to `edit`.
- Submit both create and edit forms with POST; do not add PUT/PATCH method spoofing for the combined actions.

### DataTable index actions

An index action serves the Blade view normally and Yajra JSON for AJAX requests:

```php
public function index(Request $request): JsonResponse|View
{
    if ($request->ajax()) {
        $filter = $this->collectFilterParams($request);
        $query = ClientRepository::filter(search: $filter, returnQuery: true);

        return DataTables::eloquent($query)
            ->setTransformer(
                fn (Client $client): array => ClientResource::make($client)->resolve($request)
            )
            ->toJson();
    }

    return view('client.index');
}
```

Keep request-to-DTO mapping in a small private method or a dedicated filter service. Normalize empty strings to `null` and parse dates before creating the DTO.

Do not add arbitrary query logic to the controller. A tiny lookup endpoint may build a simple query, but reusable or scoped lookup queries belong in a repository.

## Filter DTOs

Create filter DTOs under `App\Repositories\DTO`. They carry already-normalized filter data from controllers to repositories.

- Prefer `final readonly class` for new immutable DTOs.
- Give every optional value a safe default.
- Type array contents with PHPDoc when PHP cannot express them.
- Use a static named constructor such as `create()` when that is the repository convention.
- Do not place queries, authorization, request access, or presentation logic in a DTO.
- Use named arguments at call sites.

```php
final readonly class ClientFilterDTO
{
    /** @param list<int> $ids */
    private function __construct(
        public ?string $text = null,
        public array $ids = [],
        public ?CarbonImmutable $createdAtFrom = null,
        public ?CarbonImmutable $createdAtTo = null,
        public ?string $orderByColumnName = null,
        public ?string $orderByColumnDirection = null,
    ) {
    }

    /** @param list<int> $ids */
    public static function create(
        ?string $text = null,
        array $ids = [],
        ?CarbonImmutable $createdAtFrom = null,
        ?CarbonImmutable $createdAtTo = null,
        ?string $orderByColumnName = null,
        ?string $orderByColumnDirection = null,
    ): self {
        return new self(
            text: $text,
            ids: $ids,
            createdAtFrom: $createdAtFrom,
            createdAtTo: $createdAtTo,
            orderByColumnName: $orderByColumnName,
            orderByColumnDirection: $orderByColumnDirection,
        );
    }
}
```

Use explicit named forwarding so adding or reordering constructor parameters cannot silently change the mapping.

## Static Services

Place services under `App\Services`. In this architecture, services expose static functions.

- Services must be stateless. Do not use instance properties or hide mutable global state behind static methods.
- Declare public operations as `public static function` with complete parameter and return types.
- Accept domain objects and typed values, not the HTTP `Request` object.
- Return the changed domain object or `void`; do not return views or redirects.
- Use repositories for complex/reusable reads. Direct model persistence is acceptable when the service owns that model's business operation.
- Name methods as business actions: `replaceKycFile`, `updateClientDataInOrders`, `changeOrderStatus`.
- Keep file cleanup, event dispatch, cache invalidation, and queueing ordered so a failed persistence operation does not destroy the valid old state.

```php
final class ClientService
{
    public static function updateClientDataInOrders(Client $client): void
    {
        $filter = OrderFilterDTO::create(
            clientId: $client->id,
            orderByColumnName: 'id',
            orderByColumnDirection: 'desc',
        );

        ClientAddressService::updateOrderClientDataByOrderFilterDTO($filter);
    }
}
```

Static service methods should remain easy to test. If a service needs swappable infrastructure or request-scoped state, introduce an interface-backed injected collaborator rather than forcing it into a static API.

## Repositories

Place repositories under `App\Repositories`. Repositories expose static query methods and accept typed DTOs.

Structure filtered repositories consistently:

1. Small named lookup methods, such as `getOneById()` or `getAllByOrder()`.
2. A private static `getQuery()` method that creates the base Eloquent query, select list, required joins, and eager loads.
3. A private static `addWhereClause()` method that applies the filter DTO.
4. A public static `filter()` method that returns a builder, count, first record, paginator, or collection according to explicit options.

Repository rules:

- Repositories never read from `Request`, session, or view state.
- Start with `Model::query()` and qualify columns when joins can make them ambiguous.
- Use Eloquent scopes and relationships where they improve clarity.
- Eager-load relationships used by resources or loops. Do not query a related model once per row.
- Apply optional filters only when values are meaningful; distinguish `null` from `0` and `false`.
- Group OR-search conditions in a closure so they do not escape other constraints.
- Prefer `whereIn()` for non-empty typed lists.
- Use a deterministic default order for paginated queries.
- Never pass an untrusted column or direction to `orderByRaw()`.
- Whitelist sortable columns and allow only `asc` or `desc` before calling `orderBy()`.
- Use query bindings; never concatenate request values into raw SQL.
- Keep declared union return types accurate.

```php
final class ClientRepository
{
    private const SORTABLE_COLUMNS = [
        'id' => 'clients.id',
        'name' => 'clients.name',
        'created_at' => 'clients.created_at',
    ];

    private static function getQuery(): Builder
    {
        return Client::query()->select('clients.*');
    }

    private static function addWhereClause(Builder $builder, ClientFilterDTO $search): void
    {
        $builder->when($search->text, function (Builder $query, string $text): void {
            $query->where(function (Builder $query) use ($text): void {
                $query->where('clients.name', 'like', "%{$text}%")
                    ->orWhere('clients.national_id', 'like', "%{$text}%")
                    ->orWhere('clients.phone', 'like', "%{$text}%");
            });
        });

        if ($search->ids !== []) {
            $builder->whereIn('clients.id', $search->ids);
        }
    }

    public static function filter(
        ClientFilterDTO $search,
        bool $count = false,
        bool $returnQuery = false,
        ?int $pageNumber = null,
        ?int $numberOfItemsPerPage = null,
        bool $firstResult = false,
    ): Builder|Collection|LengthAwarePaginator|Client|int|null {
        $query = self::getQuery();
        self::addWhereClause($query, $search);

        if ($returnQuery) {
            return $query;
        }

        if ($count) {
            return $query->count();
        }

        $sortColumn = self::SORTABLE_COLUMNS[$search->orderByColumnName ?? 'id']
            ?? self::SORTABLE_COLUMNS['id'];
        $sortDirection = strtolower($search->orderByColumnDirection ?? 'desc');
        $query->orderBy($sortColumn, in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc');

        if ($firstResult) {
            return $query->first();
        }

        if ($pageNumber !== null && $numberOfItemsPerPage !== null) {
            return $query->paginate(perPage: $numberOfItemsPerPage, page: $pageNumber);
        }

        return $query->get();
    }
}
```

For new APIs, prefer separate methods such as `query()`, `count()`, and `paginate()` when that produces clearer return types. Keep the multi-mode `filter()` signature when consistency with the existing module is more important.

## JSON Resources and DataTable Rows

Place admin row resources under a feature namespace such as `App\Http\Resources\Admin\Client`.

- Cast scalar output deliberately.
- Format dates in one documented display format and respect the application's timezone.
- Ensure resource keys exactly match the DataTable column `data` keys.
- Generate URLs with named routes.
- Include action buttons only when the current user has the corresponding permission.
- Escape model/user content. If a field intentionally contains action HTML, construct it only from escaped labels and trusted application URLs.
- Avoid queries in resources. The controller/repository must eager-load required data.

## Blade DataTables

Use a feature `index.blade.php` plus shared partials for filtering, DataTable initialization, and deletion behavior.

The index view should:

- Extend `layouts.master`.
- Set the translated title.
- Load DataTables and SweetAlert assets in `vendor-style` and `vendor-script` sections.
- Include a feature filter partial before the table.
- Render one `<th>` for every configured DataTable column, in exactly the same order.
- Use a blank first column only when the responsive-control column is enabled.
- Configure the action column as non-orderable and non-searchable.
- Include the shared `_datatable` and `_delete` partials in `page-script`.
- Gate create/export/delete/edit UI with the same permissions enforced on the server.

```blade
@section('page-script')
    @include('client._filter_js')
    @include('admin.layouts._datatable', [
        'url' => route('admin.client.index'),
        'urlCreate' => route('admin.client.create'),
        'permissionCreateEnum' => PermissionEnum::CLIENT_CREATE,
        'disableExport' => ! UserPermissionService::can(PermissionEnum::CLIENT_EXPORT_EXCEL),
        'header' => trans('admin.client_list'),
        'columns' => [
            ['data' => ''],
            ['data' => 'id', 'name' => 'clients.id'],
            ['data' => 'name', 'name' => 'clients.name'],
            ['data' => 'created_date', 'name' => 'clients.created_at', 'searchable' => false],
            ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
        ],
    ])
    @include('admin.layouts._delete')
@endsection
```

DataTable behavior:

- Set `serverSide: true` and `processing: true`.
- Serialize `#filter_form` into the AJAX request and call `datatable.draw()` on submit/reset.
- Keep locale files, export behavior, responsive rendering, and tooltip initialization in the shared partial.
- Reinitialize transient widgets such as tooltips in `drawCallback`.
- Do not duplicate the shared initialization in individual index views.
- Treat client-provided DataTables order/search parameters as untrusted input and map them to known columns.

## Select2 With AJAX

Use a shared Blade partial for Select2 AJAX setup. Pass the element ID, endpoint URL, translated placeholder, and optional dropdown parent. Always declare JavaScript variables with `const` or `let`; do not create implicit globals.

The endpoint must:

- Be a GET route with a stable name.
- Apply authentication, authorization, tenant, hub, and other visibility scopes before search.
- Validate `term` as nullable text with a reasonable maximum length.
- Search user-facing fields with a grouped OR condition.
- Select only fields needed for the option.
- Order results deterministically and paginate them, normally 10 per page.
- Avoid N+1 queries by joining or eager-loading labels needed for option text.
- Return Select2's normalized `results` and `pagination.more` shape.

```php
public function clients(Request $request): JsonResponse
{
    $validated = $request->validate([
        'term' => ['nullable', 'string', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);

    $term = trim($validated['term'] ?? '');
    $clients = Client::query()
        ->select(['id', 'name', 'phone', 'national_id'])
        ->when($term !== '', function (Builder $query) use ($term): void {
            $query->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('national_id', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        })
        ->orderBy('name')
        ->paginate(10);

    return response()->json([
        'results' => $clients->getCollection()->map(static fn (Client $client): array => [
            'id' => $client->id,
            'text' => "{$client->name} - {$client->phone} - {$client->national_id}",
        ])->values(),
        'pagination' => [
            'more' => $clients->hasMorePages(),
        ],
    ]);
}
```

```javascript
const clientSelect = $('#client_id');

if (clientSelect.length > 0) {
    clientSelect.wrap('<div class="position-relative"></div>').select2({
        dropdownParent: clientSelect.parent(),
        placeholder: @json(trans('admin.search...')),
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: @json(route('admin.lookups.clients')),
            dataType: 'json',
            delay: 250,
            cache: true,
            data: params => ({
                term: params.term ?? '',
                page: params.page ?? 1,
            }),
            processResults: data => data,
        },
    });
}
```

For edit forms, render the currently selected option server-side so Select2 displays it before the first AJAX search. Use `dropdownParent` for controls inside modals.

Do not mix response contracts. If a legacy endpoint returns Laravel paginator JSON (`data`, `total`, `current_page`, and `per_page`), adapt it once in the shared partial. Do not refer to a nonexistent `total_count` field.

## Routes

- Group admin routes by prefix and name prefix.
- Use named routes everywhere in PHP and Blade.
- Admin HTML CRUD modules use the combined GET/POST `create` and `edit` actions defined in the Controllers section.
- Prefer conventional REST actions (`index`, `store`, `show`, `update`, `destroy`) for APIs and non-admin resources when they fit the endpoint contract.
- Put lookup routes in a clearly named group such as `admin.lookups.*`.
- Match the HTTP verb to the operation. Combined admin form actions intentionally use GET to display and POST to create or update; APIs use GET for reads, POST for creates/actions, PUT/PATCH for updates, and DELETE for deletion.

## Public Website SEO, Performance, and Images

Public-facing pages must be search-engine friendly and optimized for fast loading. Admin-only pages do not need to be indexed.

SEO requirements:

- Use semantic HTML with one clear `<h1>` per page and a logical heading hierarchy.
- Give every indexable page a unique, translated `<title>` and meta description based on its actual content.
- Provide canonical URLs and add Open Graph/Twitter metadata for pages intended to be shared.
- Add structured data in JSON-LD when the page represents a supported schema type; keep it consistent with visible content.
- Keep important page content and navigation available in server-rendered HTML. Do not require JavaScript for crawlers to discover primary content or links.
- Use descriptive named URLs and meaningful internal link text. Preserve redirects when public URLs change.
- Maintain valid `robots.txt` and XML sitemaps. Exclude private, duplicate, filtered, preview, and admin URLs from indexing.
- Add descriptive, translated `alt` text to meaningful images and an empty `alt` attribute to decorative images.

Loading-performance requirements:

- Treat Core Web Vitals as acceptance criteria: target LCP at or below 2.5 seconds, INP at or below 200 milliseconds, and CLS at or below 0.1 at the 75th percentile.
- Minimize render-blocking CSS and JavaScript. Bundle and minify production assets, load scripts with `defer` when safe, and avoid loading page-specific libraries globally.
- Cache public responses and expensive queries where content freshness permits. Use appropriate browser cache headers for versioned static assets.
- Prevent N+1 queries, select only required columns, paginate large result sets, and move slow non-response work to queues.
- Preload only critical assets such as the above-the-fold hero image or primary font. Do not overuse preconnect, preload, or third-party scripts.
- Keep fonts small: use only required families and weights, prefer WOFF2, subset when practical, and configure `font-display` deliberately.
- Avoid layout shifts by reserving space for images, embeds, banners, and asynchronously loaded components.

Image requirements:

- Never serve a full-resolution upload when a smaller rendered variant is sufficient. Generate and store purpose-specific variants during upload or asynchronous processing.
- Prefer AVIF or WebP for photographic content, with a suitable fallback where browser support requires it. Use SVG for simple icons and logos when safe and appropriate.
- Use responsive images with `srcset` and `sizes`, and set explicit intrinsic `width` and `height` attributes to preserve aspect ratio and prevent layout shifts.
- Match image dimensions to the largest realistic rendered size for the relevant breakpoint and density. Do not rely on CSS alone to shrink oversized files.
- Compress images and strip unnecessary metadata while preserving acceptable visual quality. Validate upload MIME type, file size, and pixel dimensions.
- Lazy-load below-the-fold images with `loading="lazy"` and use asynchronous decoding where appropriate. Do not lazy-load the likely LCP image; give it high fetch priority only when evidence shows it is the primary above-the-fold asset.
- Keep image filenames and generated URLs stable and descriptive when practical. Always store the original separately if future regeneration is required.

Verification:

- For meaningful public-page changes, check a production build with Lighthouse or an equivalent tool in both mobile and desktop profiles.
- Inspect generated HTML to confirm metadata, canonical URLs, structured data, heading order, image attributes, and crawlable links.
- Check representative pages under network throttling and verify that optimized image variants are served instead of original oversized uploads.
- Treat regressions in SEO metadata, indexing controls, Core Web Vitals, bundle size, query count, or transferred image bytes as defects.

## Security and Performance

- Enforce authorization server-side; hidden buttons are not security controls.
- Validate all request data, including DataTables filters and Select2 query parameters.
- Never expose unrestricted lookup results across tenant/hub boundaries.
- Avoid raw SQL unless Eloquent/query builder cannot express the query. Bind every raw value.
- Escape user-controlled HTML and be especially careful with DataTable resource fields rendered as HTML.
- Prevent N+1 queries with eager loading or joins. Check list pages with Telescope or Debugbar locally.
- Select only needed columns for high-volume DataTable and Select2 endpoints.
- Add indexes for columns used frequently in joins, filters, and ordering; make index changes through migrations.
- Never log secrets, access tokens, national IDs, or other sensitive personal data to Telescope or application logs. Configure Telescope filters/redaction accordingly.

## Tests and Verification

Add or update focused tests for every behavior change.

At minimum, list-page work should cover:

- authenticated HTML index response;
- AJAX DataTables response shape;
- filter behavior and ordering;
- permission denial and action-button visibility;
- Select2 search, scope restrictions, pagination, and JSON shape;
- service success and failure behavior;
- repository edge cases for `null`, empty arrays, `0`, and `false`.

Before finishing, run the narrowest relevant commands, then broaden when practical:

```bash
vendor/bin/pint --dirty
php artisan test --filter=Client
composer validate
```

Also run static analysis when configured. Report what was run and any checks that could not be run.

## Definition of Done

A change is complete when:

- it follows the controller -> DTO -> repository/service -> resource -> Blade separation;
- authorization and validation are enforced on the server;
- DataTable and Select2 response contracts match their JavaScript consumers;
- query ordering is whitelisted and SQL values are bound;
- related data is loaded without N+1 queries;
- user-facing strings are translated;
- public pages include correct SEO metadata and crawl/index controls;
- public-page assets and images are responsive, compressed, correctly dimensioned, and do not introduce avoidable Core Web Vitals regressions;
- relevant tests pass and changed PHP files are formatted;
- production does not expose Telescope, Debugbar, stack traces, or sensitive data.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
