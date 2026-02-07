<?php
/**
 * 排行榜（Vue3Admin）
 *
 * @package custom
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

function v3a_ranks_h($value, string $charset = 'UTF-8'): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, $charset);
}

function v3a_ranks_post_url(string $siteUrl, int $cid): string
{
    $base = rtrim(trim($siteUrl), '/');
    return $base === '' ? '/?p=' . $cid : ($base . '/?p=' . $cid);
}

function v3a_ranks_post_route_params(array $post, \Typecho\Db $db): array
{
    $cid = (int) ($post['cid'] ?? 0);
    $slug = trim((string) ($post['slug'] ?? ''));
    $created = (int) ($post['created'] ?? 0);
    if ($created <= 0) {
        $created = time();
    }

    $year = gmdate('Y', $created);
    $month = gmdate('m', $created);
    $day = gmdate('d', $created);
    try {
        if (class_exists('\\Typecho\\Date')) {
            $date = new \Typecho\Date($created);
            $year = (string) $date->year;
            $month = (string) $date->month;
            $day = (string) $date->day;
        }
    } catch (\Throwable $e) {
    }

    static $catParamsByCid = [];
    static $metaByMid = [];

    $category = '';
    $directory = '';

    if ($cid > 0) {
        if (!isset($catParamsByCid[$cid])) {
            $catParamsByCid[$cid] = ['category' => '', 'directory' => ''];

            try {
                $cat = $db->fetchRow(
                    $db->select('table.metas.mid', 'table.metas.slug', 'table.metas.parent')
                        ->from('table.relationships')
                        ->join('table.metas', 'table.relationships.mid = table.metas.mid')
                        ->where('table.relationships.cid = ?', $cid)
                        ->where('table.metas.type = ?', 'category')
                        ->order('table.metas.order', \Typecho\Db::SORT_ASC)
                        ->order('table.metas.mid', \Typecho\Db::SORT_ASC)
                        ->limit(1)
                );

                if (is_array($cat) && !empty($cat['slug'])) {
                    $categorySlug = (string) ($cat['slug'] ?? '');
                    $directoryParts = [];

                    $currentMid = (int) ($cat['mid'] ?? 0);
                    $guard = 0;
                    while ($currentMid > 0 && $guard < 20) {
                        if (!isset($metaByMid[$currentMid])) {
                            $metaByMid[$currentMid] = $db->fetchRow(
                                $db->select('mid', 'slug', 'parent', 'type')
                                    ->from('table.metas')
                                    ->where('mid = ?', $currentMid)
                                    ->limit(1)
                            );
                        }

                        $node = $metaByMid[$currentMid] ?? null;
                        if (!is_array($node) || (string) ($node['type'] ?? '') !== 'category') {
                            break;
                        }

                        $slugPart = trim((string) ($node['slug'] ?? ''));
                        if ($slugPart !== '') {
                            array_unshift($directoryParts, $slugPart);
                        }

                        $parentMid = (int) ($node['parent'] ?? 0);
                        if ($parentMid <= 0 || $parentMid === $currentMid) {
                            break;
                        }

                        $currentMid = $parentMid;
                        $guard++;
                    }

                    if (empty($directoryParts) && $categorySlug !== '') {
                        $directoryParts[] = $categorySlug;
                    }

                    $catParamsByCid[$cid] = [
                        'category' => $categorySlug !== '' ? urlencode($categorySlug) : '',
                        'directory' => implode('/', array_map('urlencode', $directoryParts)),
                    ];
                }
            } catch (\Throwable $e) {
            }
        }

        $category = (string) ($catParamsByCid[$cid]['category'] ?? '');
        $directory = (string) ($catParamsByCid[$cid]['directory'] ?? '');
    }

    return [
        'cid' => (string) $cid,
        'slug' => urlencode($slug),
        'category' => $category,
        'directory' => $directory,
        'year' => $year,
        'month' => $month,
        'day' => $day,
    ];
}

function v3a_ranks_post_permalink(array $post, \Typecho\Db $db, string $siteUrl, string $indexUrl): string
{
    $cid = (int) ($post['cid'] ?? 0);
    if ($cid <= 0) {
        return '#';
    }

    $fallback = v3a_ranks_post_url($siteUrl, $cid);

    $params = v3a_ranks_post_route_params($post, $db);

    try {
        $options = \Helper::options();
        $pattern = '';
        if ($options && isset($options->routingTable['post']['url'])) {
            $pattern = (string) $options->routingTable['post']['url'];
        } elseif ($options && isset($options->routingTable[1]['post']['url'])) {
            $pattern = (string) $options->routingTable[1]['post']['url'];
        }

        if ($pattern !== '') {
            $path = preg_replace('/\[([_a-z0-9-]+)[^\]]*\]/i', '{$1}', $pattern);
            $path = preg_replace_callback('/\{([_a-z0-9-]+)\}/i', static function (array $m) use ($params): string {
                $key = strtolower((string) ($m[1] ?? ''));
                return (string) ($params[$key] ?? '');
            }, (string) $path);

            if ($path !== '' && strpos($path, '{') === false) {
                $url = (string) \Typecho\Common::url((string) $path, $indexUrl);
                if ($url !== '' && $url !== '#') {
                    return $url;
                }
            }
        }
    } catch (\Throwable $e) {
    }

    try {
        if (class_exists('\\Typecho\\Router')) {
            $route = \Typecho\Router::get('post');
            if (empty($route)) {
                $opts = \Helper::options();
                if ($opts && isset($opts->routingTable)) {
                    \Typecho\Router::setRoutes($opts->routingTable);
                }
            }

            $url = (string) \Typecho\Router::url('post', $post, $indexUrl);
            if ($url !== '' && $url !== '#' && strpos($url, '{') === false) {
                return $url;
            }
        }
    } catch (\Throwable $e) {
    }

    $slug = trim((string) ($post['slug'] ?? ''));
    if ($slug !== '') {
        $base = rtrim(trim($siteUrl), '/');
        $slugPath = str_replace('%2F', '/', rawurlencode($slug));
        if ($base !== '') {
            return $base . '/archives/' . $slugPath . '.html';
        }
        return '/archives/' . $slugPath . '.html';
    }

    return $fallback;
}

function v3a_ranks_extract_repos(string $text): array
{
    $repos = [];
    if (preg_match_all('~https?://(?:www\.)?github\.com/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+)(?:[/?#][^\s"\'<)]*)?~i', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $owner = trim((string) ($m[1] ?? ''), " .\t\n\r\0\x0B");
            $repo = trim((string) ($m[2] ?? ''), " .\t\n\r\0\x0B");
            $repo = (string) preg_replace('/\.git$/i', '', $repo);
            if ($owner === '' || $repo === '') {
                continue;
            }
            $full = $owner . '/' . $repo;
            $repos[strtolower($full)] = $full;
        }
    }

    if (preg_match_all('~git@github\.com:([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+?)(?:\.git)?(?:\s|$)~i', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $owner = trim((string) ($m[1] ?? ''), " .\t\n\r\0\x0B");
            $repo = trim((string) ($m[2] ?? ''), " .\t\n\r\0\x0B");
            if ($owner === '' || $repo === '') {
                continue;
            }
            $full = $owner . '/' . $repo;
            $repos[strtolower($full)] = $full;
        }
    }

    return array_values($repos);
}

function v3a_ranks_fetch_category_posts(\Typecho\Db $db, string $slug): array
{
    $rows = [];
    try {
        $rows = (array) $db->fetchAll(
            $db->select(
                'table.contents.cid',
                'table.contents.title',
                'table.contents.slug',
                'table.contents.type',
                'table.contents.text',
                'table.contents.created'
            )
                ->from('table.contents')
                ->join('table.relationships', 'table.contents.cid = table.relationships.cid')
                ->join('table.metas', 'table.relationships.mid = table.metas.mid')
                ->where('table.contents.type = ?', 'post')
                ->where('table.contents.status = ?', 'publish')
                ->where('table.metas.type = ?', 'category')
                ->where('table.metas.slug = ?', $slug)
                ->order('table.contents.created', \Typecho\Db::SORT_DESC)
        );
    } catch (\Throwable $e) {
        $rows = [];
    }

    if (!empty($rows)) {
        return $rows;
    }

    try {
        $rows = (array) $db->fetchAll(
            $db->select(
                'table.contents.cid',
                'table.contents.title',
                'table.contents.slug',
                'table.contents.type',
                'table.contents.text',
                'table.contents.created'
            )
                ->from('table.contents')
                ->join('table.relationships', 'table.contents.cid = table.relationships.cid')
                ->join('table.metas', 'table.relationships.mid = table.metas.mid')
                ->where('table.contents.type = ?', 'post')
                ->where('table.contents.status = ?', 'publish')
                ->where('table.metas.type = ?', 'category')
                ->where('table.metas.name = ?', $slug)
                ->order('table.contents.created', \Typecho\Db::SORT_DESC)
        );
    } catch (\Throwable $e) {
        $rows = [];
    }

    return $rows;
}

function v3a_ranks_build_repo_entries(array $posts, \Typecho\Db $db, string $siteUrl, string $indexUrl, string $label): array
{
    $entries = [];
    $repoSeen = [];

    foreach ($posts as $row) {
        $cid = (int) ($row['cid'] ?? 0);
        if ($cid <= 0) {
            continue;
        }

        $repos = v3a_ranks_extract_repos((string) ($row['text'] ?? ''));
        if (empty($repos)) {
            continue;
        }

        $repo = (string) $repos[0];
        $repoKey = strtolower($repo);
        if (isset($repoSeen[$repoKey])) {
            continue;
        }
        $repoSeen[$repoKey] = 1;

        $entries[] = [
            'cid' => $cid,
            'title' => (string) ($row['title'] ?? ''),
            'url' => v3a_ranks_post_permalink($row, $db, $siteUrl, $indexUrl),
            'repo' => $repo,
            'repo_key' => $repoKey,
            'category' => $label,
            'created' => (int) ($row['created'] ?? 0),
        ];
    }

    return $entries;
}

function v3a_ranks_parse_headers(array $headers): array
{
    $map = [];
    foreach ($headers as $line) {
        if (!is_string($line) || strpos($line, ':') === false) {
            continue;
        }
        [$key, $val] = explode(':', $line, 2);
        $key = strtolower(trim($key));
        if ($key !== '') {
            $map[$key] = trim($val);
        }
    }
    return $map;
}

function v3a_ranks_http_json(string $url, int $timeout = 10): array
{
    $status = 0;
    $body = '';
    $headers = [];

    $reqHeaders = [
        'User-Agent: Typecho-V3A-Ranks/1.0',
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);
            curl_setopt($ch, CURLOPT_HEADER, true);

            $resp = curl_exec($ch);
            if ($resp !== false) {
                $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $rawHeader = substr((string) $resp, 0, $headerSize);
                $body = (string) substr((string) $resp, $headerSize);
                $lines = preg_split('/\r\n|\n|\r/', $rawHeader) ?: [];
                $headers = v3a_ranks_parse_headers($lines);
            }
            curl_close($ch);
        }
    }

    if ($body === '') {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeout,
                'ignore_errors' => true,
                'header' => implode("\r\n", $reqHeaders),
            ],
        ]);
        $resp = @file_get_contents($url, false, $context);
        if ($resp !== false) {
            $body = (string) $resp;
        }

        $raw = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : [];
        foreach ($raw as $line) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/i', (string) $line, $m)) {
                $status = (int) ($m[1] ?? 0);
                break;
            }
        }
        $headers = $headers ?: v3a_ranks_parse_headers($raw);
    }

    $data = null;
    if (trim($body) !== '') {
        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $decoded;
        }
    }

    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'data' => $data,
        'headers' => $headers,
    ];
}

function v3a_ranks_cache_path(): string
{
    return __TYPECHO_ROOT_DIR__ . '/usr/cache/v3a_ranks_cache.json';
}

function v3a_ranks_cache_load(): array
{
    $path = v3a_ranks_cache_path();
    if (!is_file($path)) {
        return ['repos' => []];
    }

    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return ['repos' => []];
    }

    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        return ['repos' => []];
    }
    $repos = isset($data['repos']) && is_array($data['repos']) ? $data['repos'] : [];

    return ['repos' => $repos];
}

function v3a_ranks_cache_save(array $cache): void
{
    $path = v3a_ranks_cache_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $json = json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json !== false) {
        @file_put_contents($path, $json, LOCK_EX);
    }
}

function v3a_ranks_fetch_repo_stats(array $repos, bool &$rateLimited, int &$latestFetchedAt): array
{
    $stats = [];
    $cache = v3a_ranks_cache_load();
    $dirty = false;
    $ttl = 6 * 3600;
    $now = time();
    $rateLimited = false;
    $latestFetchedAt = 0;

    foreach ($repos as $repo) {
        $key = strtolower(trim((string) $repo));
        if ($key === '' || strpos($key, '/') === false || isset($stats[$key])) {
            continue;
        }

        $cached = isset($cache['repos'][$key]) && is_array($cache['repos'][$key]) ? $cache['repos'][$key] : null;
        $fresh = is_array($cached) && (time() - (int) ($cached['fetched_at'] ?? 0)) < $ttl;
        if ($fresh) {
            $stats[$key] = $cached;
            $latestFetchedAt = max($latestFetchedAt, (int) ($cached['fetched_at'] ?? 0));
            continue;
        }

        if ($rateLimited && is_array($cached)) {
            $stats[$key] = $cached;
            $latestFetchedAt = max($latestFetchedAt, (int) ($cached['fetched_at'] ?? 0));
            continue;
        }

        [$owner, $name] = explode('/', $key, 2);
        $repoInfoUrl = 'https://api.github.com/repos/' . rawurlencode($owner) . '/' . rawurlencode($name);
        $infoRes = v3a_ranks_http_json($repoInfoUrl, 10);

        if (!$infoRes['ok'] || !is_array($infoRes['data'])) {
            if ((int) ($infoRes['status'] ?? 0) === 403 && (string) (($infoRes['headers']['x-ratelimit-remaining'] ?? '')) === '0') {
                $rateLimited = true;
            }
            if (is_array($cached)) {
                $stats[$key] = $cached;
                $latestFetchedAt = max($latestFetchedAt, (int) ($cached['fetched_at'] ?? 0));
            }
            continue;
        }

        $info = $infoRes['data'];
        $stars = (int) ($info['stargazers_count'] ?? 0);
        $pushedAt = (string) ($info['pushed_at'] ?? '');
        $fullName = (string) ($info['full_name'] ?? $key);
        $htmlUrl = (string) ($info['html_url'] ?? ('https://github.com/' . $key));

        $commit90 = is_array($cached) ? (int) ($cached['commit90'] ?? 0) : 0;
        $since = rawurlencode(gmdate('c', $now - 90 * 86400));
        $commitsUrl = 'https://api.github.com/repos/'
            . rawurlencode($owner)
            . '/'
            . rawurlencode($name)
            . '/commits?per_page=100&since='
            . $since;
        $commitRes = v3a_ranks_http_json($commitsUrl, 10);
        if ($commitRes['ok'] && is_array($commitRes['data'])) {
            $commit90 = count($commitRes['data']);
        } elseif ((int) ($commitRes['status'] ?? 0) === 403 && (string) (($commitRes['headers']['x-ratelimit-remaining'] ?? '')) === '0') {
            $rateLimited = true;
        } elseif ((int) ($commitRes['status'] ?? 0) === 409) {
            $commit90 = 0;
        }

        $stat = [
            'repo' => $fullName,
            'repo_key' => $key,
            'stars' => $stars,
            'pushed_at' => $pushedAt,
            'commit90' => $commit90,
            'html_url' => $htmlUrl,
            'fetched_at' => $now,
        ];

        $stats[$key] = $stat;
        $cache['repos'][$key] = $stat;
        $dirty = true;
        $latestFetchedAt = max($latestFetchedAt, $now);
    }

    if ($dirty) {
        v3a_ranks_cache_save($cache);
    }

    return $stats;
}

function v3a_ranks_maintenance_frequency_index(array $stat): float
{
    $commit90 = max(0, (int) ($stat['commit90'] ?? 0));
    $pushedAt = trim((string) ($stat['pushed_at'] ?? ''));
    $days = 999;
    if ($pushedAt !== '') {
        $ts = strtotime($pushedAt);
        if ($ts !== false && $ts > 0) {
            $days = max(0, (int) floor((time() - $ts) / 86400));
        }
    }

    $weeklyCommits = $commit90 / 13.0;
    $recencyWeight = 0.35;
    if ($days <= 7) {
        $recencyWeight = 1.20;
    } elseif ($days <= 30) {
        $recencyWeight = 1.00;
    } elseif ($days <= 60) {
        $recencyWeight = 0.80;
    } elseif ($days <= 90) {
        $recencyWeight = 0.60;
    }

    return round($weeklyCommits * $recencyWeight, 3);
}

function v3a_ranks_fetch_comments_rank(\Typecho\Db $db, string $siteUrl, string $indexUrl, int $limit = 5): array
{
    try {
        $rows = (array) $db->fetchAll(
            $db->select('cid', 'title', 'slug', 'type', 'created', 'commentsNum')
                ->from('table.contents')
                ->where('type = ?', 'post')
                ->where('status = ?', 'publish')
                ->order('commentsNum', \Typecho\Db::SORT_DESC)
                ->order('created', \Typecho\Db::SORT_DESC)
                ->limit($limit)
        );
    } catch (\Throwable $e) {
        return [];
    }

    $list = [];
    foreach ($rows as $row) {
        $cid = (int) ($row['cid'] ?? 0);
        if ($cid <= 0) {
            continue;
        }
        $list[] = [
            'cid' => $cid,
            'title' => (string) ($row['title'] ?? ''),
            'url' => v3a_ranks_post_permalink($row, $db, $siteUrl, $indexUrl),
            'count' => (int) ($row['commentsNum'] ?? 0),
        ];
    }

    return $list;
}

function v3a_ranks_fetch_view_counts(\Typecho\Db $db): array
{
    $counts = [];

    try {
        if (!class_exists('\\TypechoPlugin\\Vue3Admin\\LocalStorage')) {
            $file = __TYPECHO_ROOT_DIR__ . '/usr/plugins/Vue3Admin/LocalStorage.php';
            if (is_file($file)) {
                require_once $file;
            }
        }

        if (class_exists('\\TypechoPlugin\\Vue3Admin\\LocalStorage')) {
            $pdo = \TypechoPlugin\Vue3Admin\LocalStorage::pdo();
            if ($pdo instanceof \PDO) {
                $stmt = $pdo->query('SELECT cid, COUNT(id) AS views FROM v3a_visit_log WHERE cid > 0 GROUP BY cid ORDER BY views DESC LIMIT 300');
                foreach ((array) $stmt->fetchAll() as $row) {
                    $cid = (int) ($row['cid'] ?? 0);
                    $views = (int) ($row['views'] ?? 0);
                    if ($cid > 0 && $views > 0) {
                        $counts[$cid] = $views;
                    }
                }
                return $counts;
            }
        }
    } catch (\Throwable $e) {
    }

    try {
        $rows = (array) $db->fetchAll(
            $db->select('cid', ['COUNT(id)' => 'views'])
                ->from('table.v3a_visit_log')
                ->where('cid > ?', 0)
                ->group('cid')
                ->order('views', \Typecho\Db::SORT_DESC)
                ->limit(300)
        );
        foreach ($rows as $row) {
            $cid = (int) ($row['cid'] ?? 0);
            $views = (int) ($row['views'] ?? 0);
            if ($cid > 0 && $views > 0) {
                $counts[$cid] = $views;
            }
        }
    } catch (\Throwable $e) {
    }

    return $counts;
}

function v3a_ranks_build_views_rank(array $counts, \Typecho\Db $db, string $siteUrl, string $indexUrl, int $limit = 10): array
{
    if (empty($counts)) {
        return [];
    }

    arsort($counts, SORT_NUMERIC);
    $cids = array_slice(array_keys($counts), 0, 300);
    if (empty($cids)) {
        return [];
    }

    $map = [];
    try {
        $rows = (array) $db->fetchAll(
            $db->select('cid', 'title', 'slug', 'type', 'created')
                ->from('table.contents')
                ->where('cid IN ?', $cids)
                ->where('type = ?', 'post')
                ->where('status = ?', 'publish')
        );
        foreach ($rows as $row) {
            $cid = (int) ($row['cid'] ?? 0);
            if ($cid > 0) {
                $map[$cid] = $row;
            }
        }
    } catch (\Throwable $e) {
        return [];
    }

    $rank = [];
    foreach ($counts as $cid => $views) {
        $cid = (int) $cid;
        if (!isset($map[$cid])) {
            continue;
        }
        $rank[] = [
            'cid' => $cid,
            'title' => (string) ($map[$cid]['title'] ?? ''),
            'url' => v3a_ranks_post_permalink((array) $map[$cid], $db, $siteUrl, $indexUrl),
            'count' => (int) $views,
        ];
        if (count($rank) >= $limit) {
            break;
        }
    }

    return $rank;
}

function v3a_ranks_build_star_rank(array $entries, array $stats, int $limit = 5): array
{
    $rows = [];
    foreach ($entries as $entry) {
        $key = (string) ($entry['repo_key'] ?? '');
        if ($key === '' || !isset($stats[$key])) {
            continue;
        }
        $stat = $stats[$key];
        $rows[] = [
            'title' => (string) ($entry['title'] ?? ''),
            'url' => (string) ($stat['html_url'] ?? ('https://github.com/' . $key)),
            'article_url' => (string) ($entry['url'] ?? ''),
            'repo' => (string) ($stat['repo'] ?? ($entry['repo'] ?? '')),
            'stars' => (int) ($stat['stars'] ?? 0),
            'created' => (int) ($entry['created'] ?? 0),
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        if ((int) $a['stars'] !== (int) $b['stars']) {
            return ((int) $b['stars']) <=> ((int) $a['stars']);
        }
        return ((int) $b['created']) <=> ((int) $a['created']);
    });

    return array_slice($rows, 0, $limit);
}

function v3a_ranks_build_maintenance_rank(array $entries, array $stats, int $limit = 5): array
{
    $rows = [];
    foreach ($entries as $entry) {
        $key = (string) ($entry['repo_key'] ?? '');
        if ($key === '' || !isset($stats[$key])) {
            continue;
        }
        $stat = $stats[$key];
        $pushedTs = strtotime((string) ($stat['pushed_at'] ?? ''));
        if ($pushedTs === false) {
            $pushedTs = 0;
        }

        $rows[] = [
            'title' => (string) ($entry['title'] ?? ''),
            'url' => (string) ($stat['html_url'] ?? ('https://github.com/' . $key)),
            'article_url' => (string) ($entry['url'] ?? ''),
            'repo' => (string) ($stat['repo'] ?? ($entry['repo'] ?? '')),
            'commit90' => (int) ($stat['commit90'] ?? 0),
            'frequency_index' => v3a_ranks_maintenance_frequency_index($stat),
            'pushed_ts' => (int) $pushedTs,
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        if ((float) $a['frequency_index'] !== (float) $b['frequency_index']) {
            return ((float) $b['frequency_index']) <=> ((float) $a['frequency_index']);
        }
        if ((int) $a['commit90'] !== (int) $b['commit90']) {
            return ((int) $b['commit90']) <=> ((int) $a['commit90']);
        }
        return ((int) $b['pushed_ts']) <=> ((int) $a['pushed_ts']);
    });

    return array_slice($rows, 0, $limit);
}

$charset = trim((string) ($this->options->charset ?? '')) ?: 'UTF-8';
$siteUrl = (string) ($this->options->siteUrl ?? '');
$indexUrl = (string) ($this->options->index ?? $siteUrl);
$db = \Typecho\Db::get();

$export = \Typecho\Plugin::export();
$v3aEnabled = isset($export['activated']['Vue3Admin']);

$notice = [];

$viewRank = [];
if ($v3aEnabled) {
    $viewRank = v3a_ranks_build_views_rank(v3a_ranks_fetch_view_counts($db), $db, $siteUrl, $indexUrl, 10);
} else {
    $notice[] = '未启用 Vue3Admin 插件，浏览量榜单不可用。';
}

$commentRank = v3a_ranks_fetch_comments_rank($db, $siteUrl, $indexUrl, 5);

$pluginEntries = v3a_ranks_build_repo_entries(v3a_ranks_fetch_category_posts($db, 'plugins'), $db, $siteUrl, $indexUrl, 'plugins');
$themeEntries = v3a_ranks_build_repo_entries(v3a_ranks_fetch_category_posts($db, 'themes'), $db, $siteUrl, $indexUrl, 'themes');
$allEntries = array_merge($pluginEntries, $themeEntries);

$repos = [];
foreach ($allEntries as $entry) {
    $repo = (string) ($entry['repo'] ?? '');
    if ($repo !== '') {
        $repos[] = $repo;
    }
}
$repos = array_values(array_unique($repos));

$githubRateLimited = false;
$githubFetchedAt = 0;
$repoStats = !empty($repos) ? v3a_ranks_fetch_repo_stats($repos, $githubRateLimited, $githubFetchedAt) : [];

if (empty($repos)) {
    $notice[] = '未从 plugins/themes 分类文章中解析到 GitHub 仓库链接。';
}
if ($githubRateLimited) {
    $notice[] = 'GitHub API 频率受限，部分数据来自缓存。';
}

$pluginStarRank = v3a_ranks_build_star_rank($pluginEntries, $repoStats, 5);
$themeStarRank = v3a_ranks_build_star_rank($themeEntries, $repoStats, 5);
$maintenanceRank = v3a_ranks_build_maintenance_rank($allEntries, $repoStats, 5);

$this->need('header.php');
?>

<main class="container">
    <div class="container-thin">
        <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
            <?php postMeta($this, 'page'); ?>

            <div class="entry-content fmt" itemprop="articleBody">
                <style>
                    main.container{width:100vw!important;max-width:100vw!important;padding-left:0!important;padding-right:0!important}
                    main.container > .container-thin{width:90vw!important;max-width:90vw!important;margin-left:auto;margin-right:auto;padding-left:0!important;padding-right:0!important;box-sizing:border-box}
                    .entry-content.fmt{width:100%}
                    .v3a-ranks-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
                    .v3a-rank-col{border:1px solid var(--pico-muted-border-color);border-radius:var(--pico-border-radius);padding:.9rem;background:var(--pico-card-background-color,transparent);max-width:100%;width:100%;box-sizing:border-box;overflow:hidden;font-size:1rem;line-height:1.55}
                    .v3a-rank-col-split{border:none;background:transparent;padding:0;display:grid;gap:.8rem}
                    .v3a-rank-panel{border:1px solid var(--pico-muted-border-color);border-radius:var(--pico-border-radius);padding:.9rem;background:var(--pico-card-background-color,transparent);max-width:100%;width:100%;box-sizing:border-box;overflow:hidden;font-size:1rem;line-height:1.55}
                    .v3a-rank-panel > h3{margin:0 0 .8rem;font-size:1.06rem}
                    .v3a-rank-col h3{margin:0 0 .8rem;font-size:1.06rem}
                    .v3a-rank-sub{margin:.75rem 0 .45rem;font-size:.98rem;color:var(--pico-muted-color)}
                    .v3a-rank-list{list-style:none;margin:0;padding:0}
                    .v3a-rank-item{display:flex;gap:.6rem;align-items:flex-start;padding:.38rem 0;border-bottom:1px dashed var(--pico-muted-border-color);min-height:2.2rem}
                    .v3a-rank-item:last-child{border-bottom:0}
                    .v3a-rank-item.empty{color:var(--pico-muted-color)}
                    .v3a-rank-no{flex:0 0 1.35rem;line-height:1.45;font-size:.96rem;font-weight:700;color:var(--pico-muted-color)}
                    .v3a-rank-main{min-width:0;flex:1;max-width:100%;overflow:hidden}
                    .v3a-rank-main a:not(.v3a-rank-article-btn){display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:1rem;line-height:1.5}
                    .v3a-rank-main > span{display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
                    .v3a-rank-meta{margin-top:2px;font-size:.95rem;color:var(--pico-muted-color);max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
                    .v3a-rank-head{display:flex;flex-wrap:nowrap;align-items:center;gap:.4rem;min-width:0;max-width:100%}
                    .v3a-rank-repo-link{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
                    .v3a-rank-tools{display:inline-flex;align-items:center;gap:.36rem;flex:0 0 auto}
                    .v3a-rank-article-btn{display:inline-flex;align-items:center;justify-content:center;font-size:.92rem;line-height:1;color:var(--pico-muted-color);opacity:.9;text-decoration:none}
                    .v3a-rank-article-btn:hover{opacity:1}
                    .v3a-rank-stars{display:inline-block;font-size:.95rem;line-height:1;color:var(--pico-muted-color);white-space:nowrap}
                    .v3a-rank-eye{display:inline-flex;align-items:center;justify-content:center;width:1em;height:1em;font-size:.95rem;line-height:1;color:var(--pico-muted-color);opacity:.9}
                    .v3a-rank-eye svg{display:block;width:100%;height:100%}
                    .v3a-rank-msg{display:inline-flex;align-items:center;justify-content:center;width:1em;height:1em;font-size:.95rem;line-height:1;color:var(--pico-muted-color);opacity:.9}
                    .v3a-rank-msg svg{display:block;width:100%;height:100%}
                    .v3a-rank-activity{display:inline-flex;align-items:center;justify-content:center;width:1em;height:1em;font-size:.95rem;line-height:1;color:var(--pico-muted-color);opacity:.9}
                    .v3a-rank-activity svg{display:block;width:100%;height:100%}
                    .v3a-rank-stat{display:inline-block;font-size:.95rem;line-height:1;color:var(--pico-muted-color);white-space:nowrap}
                    .v3a-rank-note{margin-bottom:.85rem;padding:.65rem .8rem;border:1px solid var(--pico-muted-border-color);border-radius:var(--pico-border-radius);font-size:.96rem;color:var(--pico-muted-color)}
                    .v3a-rank-foot{margin-top:.8rem;font-size:.94rem;color:var(--pico-muted-color)}
                    @media (max-width:1100px){
                        main.container > .container-thin{width:90vw!important;max-width:90vw!important}
                        .v3a-ranks-grid{grid-template-columns:1fr;gap:.85rem}
                        .v3a-rank-col{height:auto}
                    }
                    @media (max-width:768px){
                        main.container > .container-thin{width:90vw!important;max-width:90vw!important}
                        .v3a-rank-col{padding:.72rem;height:auto}
                        .v3a-rank-panel{padding:.72rem}
                        .v3a-rank-col h3{font-size:1.02rem;margin:0 0 .55rem}
                        .v3a-rank-panel > h3{font-size:1.02rem;margin:0 0 .55rem}
                        .v3a-rank-sub{font-size:.95rem;margin:.5rem 0 .3rem}
                        .v3a-rank-item{gap:.45rem;padding:.24rem 0;min-height:1.82rem}
                        .v3a-rank-no{flex:0 0 1.1rem;font-size:.92rem;line-height:1.34}
                        .v3a-rank-main a:not(.v3a-rank-article-btn){font-size:.96rem;line-height:1.42}
                        .v3a-rank-meta{font-size:.9rem;margin-top:1px}
                        .v3a-rank-article-btn{font-size:.9rem}
                        .v3a-rank-stars{font-size:.9rem}
                        .v3a-rank-eye{font-size:.9rem}
                        .v3a-rank-msg{font-size:.9rem}
                        .v3a-rank-activity{font-size:.9rem}
                        .v3a-rank-stat{font-size:.9rem}
                        .v3a-rank-note{font-size:.92rem;padding:.5rem .6rem}
                        .v3a-rank-foot{font-size:.9rem}
                    }
                    @media (max-width:430px){
                        main.container > .container-thin{width:90vw!important;max-width:90vw!important}
                        .v3a-rank-col{padding:.62rem;height:auto}
                        .v3a-rank-panel{padding:.62rem}
                        .v3a-rank-main a:not(.v3a-rank-article-btn){font-size:.92rem}
                        .v3a-rank-meta{font-size:.86rem}
                        .v3a-rank-article-btn{font-size:.86rem}
                        .v3a-rank-stars{font-size:.86rem}
                        .v3a-rank-eye{font-size:.86rem}
                        .v3a-rank-msg{font-size:.86rem}
                        .v3a-rank-activity{font-size:.86rem}
                        .v3a-rank-stat{font-size:.86rem}
                        .v3a-rank-item{min-height:1.66rem;padding:.2rem 0}
                    }
                </style>

                <?php foreach ($notice as $msg): ?>
                    <div class="v3a-rank-note"><?php echo v3a_ranks_h($msg, $charset); ?></div>
                <?php endforeach; ?>

                <div class="v3a-ranks-grid">
                    <section class="v3a-rank-col v3a-rank-col-split">
                        <div class="v3a-rank-panel">
                            <h3>浏览量榜单（TOP 10）</h3>
                            <ol class="v3a-rank-list">
                                <?php for ($i = 0; $i < 10; $i++): $item = $viewRank[$i] ?? null; ?>
                                    <li class="v3a-rank-item<?php echo $item ? '' : ' empty'; ?>">
                                        <span class="v3a-rank-no"><?php echo (int) ($i + 1); ?></span>
                                        <div class="v3a-rank-main">
                                            <?php if ($item): ?>
                                                <div class="v3a-rank-head">
                                                    <a class="v3a-rank-repo-link" href="<?php echo v3a_ranks_h($item['url'] ?? '', $charset); ?>"><?php echo v3a_ranks_h($item['title'] ?? '（无标题）', $charset); ?></a>
                                                    <span class="v3a-rank-tools">
                                                        <span class="v3a-rank-eye" aria-hidden="true">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                                        </span>
                                                        <span class="v3a-rank-stat"><?php echo number_format((int) ($item['count'] ?? 0)); ?></span>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span>暂无数据</span>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endfor; ?>
                            </ol>
                        </div>
                    </section>

                    <section class="v3a-rank-col v3a-rank-col-split">
                        <div class="v3a-rank-panel">
                            <h3>插件星数榜单（TOP 5）</h3>
                            <ol class="v3a-rank-list">
                                <?php for ($i = 0; $i < 5; $i++): $item = $pluginStarRank[$i] ?? null; ?>
                                    <li class="v3a-rank-item<?php echo $item ? '' : ' empty'; ?>">
                                        <span class="v3a-rank-no"><?php echo (int) ($i + 1); ?></span>
                                        <div class="v3a-rank-main">
                                            <?php if ($item): ?>
                                                <div class="v3a-rank-head">
                                                    <a class="v3a-rank-repo-link" href="<?php echo v3a_ranks_h($item['url'] ?? '', $charset); ?>" target="_blank" rel="noreferrer noopener"><?php echo v3a_ranks_h($item['repo'] ?? ($item['title'] ?? '（无标题）'), $charset); ?></a>
                                                    <span class="v3a-rank-tools">
                                                        <span class="v3a-rank-stars">★ <?php echo number_format((int) ($item['stars'] ?? 0)); ?></span>
                                                        <?php if (!empty($item['article_url'])): ?>
                                                            <a class="v3a-rank-article-btn" href="<?php echo v3a_ranks_h($item['article_url'] ?? '', $charset); ?>" title="查看文章" aria-label="查看文章" target="_blank" rel="noreferrer noopener">查看</a>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span>暂无数据</span>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endfor; ?>
                            </ol>
                        </div>

                        <div class="v3a-rank-panel">
                            <h3>主题星数榜单（TOP 5）</h3>
                            <ol class="v3a-rank-list">
                                <?php for ($i = 0; $i < 5; $i++): $item = $themeStarRank[$i] ?? null; ?>
                                    <li class="v3a-rank-item<?php echo $item ? '' : ' empty'; ?>">
                                        <span class="v3a-rank-no"><?php echo (int) ($i + 1); ?></span>
                                        <div class="v3a-rank-main">
                                            <?php if ($item): ?>
                                                <div class="v3a-rank-head">
                                                    <a class="v3a-rank-repo-link" href="<?php echo v3a_ranks_h($item['url'] ?? '', $charset); ?>" target="_blank" rel="noreferrer noopener"><?php echo v3a_ranks_h($item['repo'] ?? ($item['title'] ?? '（无标题）'), $charset); ?></a>
                                                    <span class="v3a-rank-tools">
                                                        <span class="v3a-rank-stars">★ <?php echo number_format((int) ($item['stars'] ?? 0)); ?></span>
                                                        <?php if (!empty($item['article_url'])): ?>
                                                            <a class="v3a-rank-article-btn" href="<?php echo v3a_ranks_h($item['article_url'] ?? '', $charset); ?>" title="查看文章" aria-label="查看文章" target="_blank" rel="noreferrer noopener">查看</a>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span>暂无数据</span>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endfor; ?>
                            </ol>
                        </div>
                    </section>

                    <section class="v3a-rank-col v3a-rank-col-split">
                        <div class="v3a-rank-panel">
                            <h3>评论榜单（TOP 5）</h3>
                            <ol class="v3a-rank-list">
                                <?php for ($i = 0; $i < 5; $i++): $item = $commentRank[$i] ?? null; ?>
                                    <li class="v3a-rank-item<?php echo $item ? '' : ' empty'; ?>">
                                        <span class="v3a-rank-no"><?php echo (int) ($i + 1); ?></span>
                                        <div class="v3a-rank-main">
                                            <?php if ($item): ?>
                                                <div class="v3a-rank-head">
                                                    <a class="v3a-rank-repo-link" href="<?php echo v3a_ranks_h($item['url'] ?? '', $charset); ?>"><?php echo v3a_ranks_h($item['title'] ?? '（无标题）', $charset); ?></a>
                                                    <span class="v3a-rank-tools">
                                                        <span class="v3a-rank-msg" aria-hidden="true">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"/></svg>
                                                        </span>
                                                        <span class="v3a-rank-stat"><?php echo number_format((int) ($item['count'] ?? 0)); ?></span>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span>暂无数据</span>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endfor; ?>
                            </ol>
                        </div>

                        <div class="v3a-rank-panel">
                            <h3>维护金榜（TOP 5）</h3>
                            <ol class="v3a-rank-list">
                                <?php for ($i = 0; $i < 5; $i++): $item = $maintenanceRank[$i] ?? null; ?>
                                    <li class="v3a-rank-item<?php echo $item ? '' : ' empty'; ?>">
                                        <span class="v3a-rank-no"><?php echo (int) ($i + 1); ?></span>
                                        <div class="v3a-rank-main">
                                            <?php if ($item): ?>
                                                <div class="v3a-rank-head">
                                                    <a class="v3a-rank-repo-link" href="<?php echo v3a_ranks_h($item['url'] ?? '', $charset); ?>" target="_blank" rel="noreferrer noopener"><?php echo v3a_ranks_h($item['repo'] ?? ($item['title'] ?? '（无标题）'), $charset); ?></a>
                                                    <span class="v3a-rank-tools">
                                                        <span class="v3a-rank-activity" aria-hidden="true">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>
                                                        </span>
                                                        <span class="v3a-rank-stat"><?php echo number_format((float) ($item['frequency_index'] ?? 0), 2); ?></span>
                                                        <?php if (!empty($item['article_url'])): ?>
                                                            <a class="v3a-rank-article-btn" href="<?php echo v3a_ranks_h($item['article_url'] ?? '', $charset); ?>" title="查看文章" aria-label="查看文章" target="_blank" rel="noreferrer noopener">查看</a>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span>暂无数据</span>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endfor; ?>
                            </ol>
                        </div>
                    </section>
                </div>

                <p class="v3a-rank-foot">
                    <?php if ($githubFetchedAt > 0): ?>
                        GitHub 数据更新时间：<?php echo date('Y-m-d H:i', $githubFetchedAt); ?>。
                    <?php else: ?>
                        GitHub 数据暂不可用（请检查文章内仓库链接与网络连通性）。
                    <?php endif; ?>
                </p>

                <?php $this->content(); ?>
            </div>
        </article>

        <hr class="post-separator">

        <?php $this->need('comments.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>
