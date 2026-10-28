<?php
// Lists translation keys used in code/views that are missing from lang/en or lang/ar.
$root = dirname(__DIR__);
$files = array_merge(glob("$root/app/{,*/,*/*/,*/*/*/}*.php", GLOB_BRACE), glob("$root/resources/views/{,*/,*/*/,*/*/*/}*.blade.php", GLOB_BRACE), glob("$root/routes/*.php"));
$used = [];
foreach ($files as $f) {
    preg_match_all("/(?:__|trans|@lang)\(\s*'([a-z_]+\.[a-zA-Z0-9_.]+)'/", file_get_contents($f), $m);
    foreach ($m[1] as $k) $used[$k][] = basename($f);
}
$missing = 0;
foreach (['en', 'ar'] as $l) {
    foreach ($used as $k => $where) {
        [$g, $rest] = explode('.', $k, 2);
        $file = "$root/lang/$l/$g.php";
        $arr = is_file($file) ? include $file : [];
        $v = $arr;
        foreach (explode('.', $rest) as $p) { $v = is_array($v) && array_key_exists($p, $v) ? $v[$p] : null; }
        if ($v === null) { echo "$l: $k  (".implode(',', array_unique($where)).")\n"; $missing++; }
    }
}
echo count($used)." keys used, $missing missing\n";
