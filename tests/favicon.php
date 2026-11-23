<?php

declare(strict_types=1);

// Favicon im Woo-Theme: ohne Website-Icon das MGD-Logo, mit eigenem Icon nichts von uns
define('ABSPATH', __DIR__ . '/');
$GLOBALS['mt_hooks'] = [];
$GLOBALS['mt_site_icon'] = false;
$GLOBALS['mt_redirect'] = null;
function add_action(string $h, callable $cb, int $p = 10): void { $GLOBALS['mt_hooks'][$h][] = $cb; }
function apply_filters(string $h, $v) { return $v; }
function get_template_directory_uri(): string { return 'https://shop.example.test/wp-content/themes/MichaThemeV5'; }
function has_site_icon(): bool { return $GLOBALS['mt_site_icon']; }
function esc_url(string $u): string { return htmlspecialchars($u, ENT_QUOTES); }
function wp_safe_redirect(string $u, int $s = 302, string $by = ''): bool { $GLOBALS['mt_redirect'] = $u; throw new RuntimeException('redirect'); }
require dirname(__DIR__) . '/inc/favicon.php';

$fails = 0;
$ok = static function (string $n, bool $c) use (&$fails): void { echo ($c ? 'OK   ' : 'FEHL ') . $n . "\n"; $fails += $c ? 0 : 1; };
$run = static function (string $hook): string { ob_start(); try { foreach ($GLOBALS['mt_hooks'][$hook] ?? [] as $cb) { $cb(); } } catch (RuntimeException) {} return (string) ob_get_clean(); };

$png = dirname(__DIR__) . '/assets/mt/mt-favicon.png';
$info = is_file($png) ? getimagesize($png) : false;
$ok('mt-favicon.png vorhanden, PNG, quadratisch, mind. 192 px', $info !== false && $info[2] === IMAGETYPE_PNG && $info[0] === $info[1] && $info[0] >= 192);
$head = $run('wp_head');
$ok('ohne Website-Icon: link rel=icon auf das Logo', str_contains($head, 'rel="icon"') && str_contains($head, '/assets/mt/mt-favicon.png'));
$run('do_faviconico');
$ok('ohne Website-Icon: /favicon.ico leitet aufs Logo', is_string($GLOBALS['mt_redirect']) && str_ends_with($GLOBALS['mt_redirect'], '/assets/mt/mt-favicon.png'));
$GLOBALS['mt_site_icon'] = true; $GLOBALS['mt_redirect'] = null;
$ok('mit eigenem Website-Icon: kein link von uns', $run('wp_head') === '');
$run('do_faviconico');
$ok('mit eigenem Website-Icon: keine Umleitung', $GLOBALS['mt_redirect'] === null);
exit($fails === 0 ? 0 : 1);
