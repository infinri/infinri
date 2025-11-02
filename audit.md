Infinri /app Code Audit Report
Code Quality & Best Practices

DRY / Repetition: Several utility routines and patterns appear duplicated. For example, multiple controllers build HTML strings and manage session state in the same way. This violates the DRY principle – logic should be centralized (e.g. helpers or base classes) rather than copy-pasted
scalastic.io
.

SOLID / Single Responsibility: Some classes are very large and handle multiple concerns. For instance, Core/App/FrontController.php (418 LOC) both parses routes and dispatches actions. Similarly, the Media/Picker controller in CMS mixes UI building with controller logic. These classes do not strictly adhere to SRP; they would benefit from separating routing, rendering, and business logic into distinct components
elvinbaghele.medium.com
.

KISS / Simplicity: Complex constructs are found where simpler alternatives would suffice. For example, inline HTML is generated via large sprintf(<<<HTML...) blocks in controllers (see Core/Product/ViewController), rather than using view templates. This violates the KISS guideline, making code harder to read and maintain
scalastic.io
.

Modularity & Testability: Many methods are very long (see “Long Methods” table below) and contain nested loops or large switch/case blocks. For example, FrontController::dispatch() (148 lines) and UiComponentRenderer::renderGrid() (109 lines) juggle multiple responsibilities. These would be easier to unit-test and maintain if broken into smaller helper methods or service classes.

Design Patterns: A rudimentary MVC pattern is used, but many controllers directly manipulate output or sessions. For example, the Media/Picker controller assembles HTML and JavaScript in PHP strings instead of using view templates or separate renderer classes. Best practice is to keep controllers lightweight and view-focused (avoid business logic or presentation markup in controllers)
elvinbaghele.medium.com
elvinbaghele.medium.com
.

Top 10 complex files (by lines of code):

File (relative to app/Infinri/)	LOC	Notes
Core/Model/Layout/Processor.php	421	Heavy XML processing (refs, moves, loops)
Core/App/FrontController.php	418	Main request dispatcher
Core/App/Request.php	399	HTTP wrapper (initializes from superglobals)
Menu/Model/MenuItem.php	373	Data model with complex init
Core/Model/ResourceModel/Connection.php	362	DB connection and raw queries
Core/Model/Layout/Loader.php	348	Layout XML loading
Core/View/Element/UiComponentRenderer.php	343	Builds admin grids
Core/App/Response.php	337	HTTP response handling
Core/Block/Template.php	337	Block renderer with utilities
Core/View/Element/UiFormRenderer.php	335	Renders forms (lots of loops)

Top 10 long methods: (Length includes comments but shows method complexity)

File (relative)	Method	Length (lines)
Core/App/FrontController.php	dispatch()	148
Cms/Controller/Adminhtml/Media/Picker.php	renderPicker()	139
Core/Route/Loader.php	registerRoute()	110
Core/View/Element/UiComponentRenderer.php	renderGrid()	109
Cms/Controller/Adminhtml/Media/Uploadmultiple.php	execute()	106
Core/Model/Layout/Processor.php	processReferenceDirectives()	99
Auth/Controller/Adminhtml/Login/Post.php	execute()	97
Core/View/LayoutFactory.php	render()	89
Core/Console/Command/SetupUpgradeCommand.php	execute()	88
Core/Setup/Patch/Data/InstallDefaultConfig.php	getDefaultConfigs()	84

Recommendation: Break up large methods into smaller private helpers or services. For example, split the CMS media renderPicker() into parts: one to fetch data, one to build HTML (using templates), etc. Adopt a templating system or view classes to avoid raw HTML in controllers, and consider using dependency injection to follow SOLID and improve testability.

Algorithmic Efficiency

Nested loops: Several components use deeply nested loops. The layout processor (Core/Model/Layout/Processor.php) performs multiple foreach and while loops over XML nodes (e.g. processing <remove>, <move>, and <reference> directives)
elvinbaghele.medium.com
. If the layout XML or UI components grow large, this could become a performance bottleneck (potentially O(N^2) in the number of nodes).

Grid Rendering: UiComponentRenderer::renderGrid() loops over buttons, columns, and items (multiple nested loops) to produce an HTML table. If the grid has many rows/columns, the time will grow quadratically. Caching rendered templates or simplifying grid logic could help.

Menu/Config Loops: Classes like Core/Model/Config/SystemReader.php and Menu/Builder.php use multiple sorts and loops over configuration arrays. These may be expensive if configs are large (though config size is usually modest).

Inefficient File Access: Some controllers (e.g. Media picker) scan directories with glob and then loop over files to generate HTML cards. This is inherently linear in the number of files; if media folders contain thousands of images, consider pagination or AJAX loading to avoid blocking.

Recommendation: Identify any truly large collections (e.g. database-backed lists, many menu items) and add pagination or batching. Optimize XML/layout loops by limiting XPath queries or breaking into smaller steps. Profile complex methods (e.g. via Xdebug or Blackfire) to pinpoint hot loops.

Security Audit

Superglobals: The code generally encapsulates request data in Core/App/Request, but raw superglobals are still used in places. Notable uses:

Core/App/Request constructor reads $_GET and $_POST (acceptable as an entry point)
scalastic.io
.

Unsafe debug: Cms/Controller/Adminhtml/Media/Uploadmultiple.php logs $_POST directly to error_log. Debug code should not expose raw request data in production. Controllers elsewhere should use $request->getParam() instead of accessing $_POST directly.

Sessions: Many classes manipulate $_SESSION directly (middleware, CSRF guard, login controllers). Modern best practices use a session-storage abstraction (e.g. Symfony’s session service) rather than superglobals. Direct use of $_SESSION is hard to test and may circumvent security checks. Use a session manager class instead.

CSRF Protection: Forms and POST endpoints should include CSRF tokens. The code does use a CsrfGuard to validate tokens in several actions (e.g. media upload checks validateToken(...)). However, ensure that all state-changing forms emit a hidden token and all POST handlers validate it. The OWASP guidelines state: “add CSRF tokens to all state-changing requests and validate them on the backend”
cheatsheetseries.owasp.org
. Verify that any new forms (e.g. admin settings, menu management) have tokens.

SQL Queries: Raw SQL queries appear in setup scripts and resource models. For example, Core/Model/ResourceModel/User.php builds queries with ? placeholders and prepares them. Use parameterized queries for any user-supplied input
cheatsheetseries.owasp.org
. Avoid concatenating variables into SQL (except known safe identifiers). If any dynamic table/column names are built, whitelist them or use proper identifier quoting.

Output escaping (XSS): All HTML output should be escaped unless safely sanitized. Many templates use $block->escapeHtml() or escapeHtmlAttr() consistently, which is good. However: the frontend CMS page template renders page content as raw HTML (<?= $page->getContent() ?>) without escaping. The code comments note “Content is HTML from WYSIWYG, admin-controlled.” Even if admins supply it, it should be sanitized. OWASP advises sanitizing HTML from WYSIWYG editors to remove dangerous tags
cheatsheetseries.owasp.org
. I recommend running page content through an HTML sanitizer (e.g. DOMPurify) or explicitly filtering tags.

Controllers Generating HTML: The Core/Product/ViewController and Cms/Media/Picker controllers output raw HTML and JavaScript in PHP strings. Embedding HTML in controllers is insecure and hard to maintain. Output should be moved to view templates or partials. This also avoids missing HTML-encoding opportunities and mixing logic/presentation.

Recommendation:

Refactor code to use a dedicated request/session service rather than direct superglobals. This centralizes input filtering.

Ensure every form includes a CSRF hidden input and validate on submit (double-check any AJAX endpoints as well).

Always use prepared statements or ORM methods for SQL; never trust string concatenation.

Escape all output to HTML; sanitize rich-text content. See OWASP: “Output Encoding and HTML Sanitization will provide the best protection”
cheatsheetseries.owasp.org
.

Raw superglobal usage (outside dedicated request classes):

File (relative to app/Infinri/)	Superglobal	Context / Issue
Core/App/Request.php	$_GET, $_POST	Constructor bootstrap – acceptable entry usage.
Cms/Controller/Adminhtml/Media/Uploadmultiple.php	$_POST	Debug log directly prints $_POST contents. Use $request->getParam() instead.
Core/App/Middleware/AuthenticationMiddleware.php	$_SESSION	Stores/reads auth flags and user info. Use a session service instead.
Core/Model/Message/MessageManager.php	$_SESSION	Pushes/reads flash messages. Should use session abstraction.
Core/Security/CsrfGuard.php	$_SESSION	Stores CSRF tokens in session. (Could use Symfony CSRF component.)
Auth/Block/Adminhtml/Login/Form.php	$_SESSION	Checks $_SESSION['login_error']. Should use injected message manager.
Auth/Controller/Adminhtml/Login/Index.php	$_SESSION	Checks $_SESSION['admin_authenticated']. (Guard should be injected.)
Auth/Controller/Adminhtml/Login/Logout.php	$_SESSION	Reads and then clears session data on logout. Use session manager.
Auth/Controller/Adminhtml/Login/Post.php	$_SESSION	Sets login error and fingerprint. Again, prefer service.
Front-End Audit

Mixing logic in templates: Most .phtml templates correctly use $block->escapeHtml() or escapeHtmlAttr() when outputting variables. However, there are some issues:

The CMS page view template (Cms/view/frontend/templates/page/view.phtml) outputs <?= $page->getContent() ?> unescaped. If user-generated content is allowed, this is an XSS risk. Ideally sanitize or explicitly allow only safe HTML.

The admin forms in Theme/view/adminhtml/templates/form.phtml do use escaping for all field values (e.g. htmlspecialchars($data[$field['dataScope']]) which is good.

Inline logic in templates: A few templates contain embedded PHP logic (e.g. loops over fields in form templates or if-statements in header/footer). This is normal, but ensure it’s only presentation logic. The code follows MVC practice by pushing complex logic into blocks/renderer classes. The templates themselves are reasonably simple.

Hardcoded scripts or HTML:

The login form template manually injects a <link> to a compiled CSS file via PHP because “layout blocks aren’t rendering properly” – this is a workaround that should be fixed via proper asset management.

In Menu/view/adminhtml/templates/form/field/checkboxset-with-sortorder.phtml, there is an inline <script> block at the bottom to highlight rows on checkbox toggle. Inline scripts should generally be moved to a separate .js file and included as a static asset to improve security (avoid CSP issues) and maintainability.

Unescaped output or inline JavaScript (table):

File	Issue
Cms/view/frontend/templates/page/view.phtml	Outputs raw $page->getContent() HTML (no sanitization).
Menu/view/adminhtml/templates/form/field/checkboxset-with-sortorder.phtml	Inline <script> block in template (highlighting logic).

Recommendation: Move inline scripts/CSS into dedicated asset files. Ensure all variables in templates are escaped with escapeHtml or similar. For WYSIWYG content, apply HTML sanitization. Adhere to MVC separation: “Views should only contain logic related to presenting data”
elvinbaghele.medium.com
, keeping controllers and blocks free of markup.

Summary of Issues & Fixes

Refactor large classes/methods: Break down the longest classes and methods (see tables above) into smaller, single-purpose units or reuseable services. Apply SOLID principles to give classes one responsibility
elvinbaghele.medium.com
.

Eliminate code duplication: Identify similar code blocks (e.g. HTML builders, error handling) and extract them to helper functions or base classes to enforce DRY
scalastic.io
.

Improve security: Replace raw superglobal access with a centralized request/session handler. Ensure every form has CSRF tokens and validate them (state-changing requests must include them
cheatsheetseries.owasp.org
). Use prepared statements/parameterized queries for all database access
cheatsheetseries.owasp.org
. Escape or sanitize all output, especially rich-text content (as OWASP recommends HTML sanitization for user-authored content
cheatsheetseries.owasp.org
).

Enhance front-end structure: Move hard-coded HTML/JS in controllers into proper templates. Keep presentation in view templates and logic in blocks/controllers. Remove inline scripts and CSS in templates, favoring static files or view models.

Overall, the codebase shows a good start with modular structure, but introducing these best-practice changes will improve maintainability, security, and scalability.