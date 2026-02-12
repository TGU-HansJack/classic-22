<?php
/**
 * 投稿页
 *
 * @package custom
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

// Ensure theme functions are available even when Archive is invoked from outside.
if (
    !function_exists('classic22LinuxDoGetOption')
    || !function_exists('classic22AiRequest')
    || !function_exists('classic22AiExtractAnswerByMode')
) {
    $v3aPostThemeFunctions = __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';
    if (is_file($v3aPostThemeFunctions)) {
        require_once $v3aPostThemeFunctions;
    }
}

if (!function_exists('v3aPostH')) {
    function v3aPostH($value, string $charset = 'UTF-8'): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, $charset);
    }
}

if (!function_exists('v3aPostBool')) {
    function v3aPostBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $text = strtolower(trim((string) $value));
        return in_array($text, ['1', 'true', 'yes', 'on', 'y'], true);
    }
}

if (!function_exists('v3aPostSubstr')) {
    function v3aPostSubstr(string $text, int $length): string
    {
        if ($length <= 0) {
            return '';
        }
        return function_exists('mb_substr') ? (string) mb_substr($text, 0, $length) : substr($text, 0, $length);
    }
}

if (!function_exists('v3aPostLen')) {
    function v3aPostLen(string $text): int
    {
        return function_exists('mb_strlen') ? (int) mb_strlen($text) : strlen($text);
    }
}

if (!function_exists('v3aPostPreview')) {
    function v3aPostPreview($value, int $max = 48): string
    {
        if (is_array($value)) {
            $value = implode('、', array_map('strval', $value));
        }
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        if (v3aPostLen($text) <= $max) {
            return $text;
        }
        return rtrim(v3aPostSubstr($text, $max)) . '…';
    }
}

if (!function_exists('v3aPostRenderMarkdown')) {
    function v3aPostRenderMarkdown(string $markdown, string $charset = 'UTF-8'): string
    {
        $markdown = trim((string) $markdown);
        if ($markdown === '') {
            return '';
        }

        $html = '';
        try {
            if (class_exists('\\Utils\\Markdown') && method_exists('\\Utils\\Markdown', 'convert')) {
                $html = (string) \Utils\Markdown::convert($markdown);
            }
        } catch (\Throwable $e) {
            $html = '';
        }

        if (trim($html) === '') {
            return nl2br(v3aPostH($markdown, $charset));
        }

        $allowed = '<h1><h2><h3><h4><h5><h6>'
            . '<p><br>'
            . '<strong><b><em><i><del>'
            . '<blockquote><pre><code class="">' 
            . '<ul><ol><li><hr>'
            . '<a href="">';

        try {
            if (class_exists('\\Typecho\\Common') && method_exists('\\Typecho\\Common', 'stripTags')) {
                $html = (string) \Typecho\Common::stripTags($html, $allowed);
            } else {
                $html = strip_tags($html);
            }
        } catch (\Throwable $e) {
            $html = strip_tags($html);
        }

        if (class_exists('\\Typecho\\Common') && method_exists('\\Typecho\\Common', 'safeUrl')) {
            $html = preg_replace_callback(
                '/\shref=(\"|\')([^\"\']*)(\\1)/i',
                static function (array $match) use ($charset): string {
                    $safe = \Typecho\Common::safeUrl((string) ($match[2] ?? ''));
                    return ' href="' . htmlspecialchars($safe, ENT_QUOTES, $charset) . '"';
                },
                (string) $html
            );
        }

        return (string) $html;
    }
}

if (!function_exists('v3aPostFormatLocalTime')) {
    function v3aPostFormatLocalTime(int $timestamp, string $format = 'Y-m-d H:i:s'): string
    {
        if ($timestamp <= 0) {
            return '';
        }

        try {
            if (class_exists('\\Typecho\\Date')) {
                $date = new \Typecho\Date($timestamp);
                return (string) $date->format($format);
            }
        } catch (\Throwable $e) {
        }

        return date($format, $timestamp);
    }
}

if (!function_exists('v3aPostFieldRaw')) {
    function v3aPostFieldRaw(array $row): string
    {
        $type = strtolower(trim((string) ($row['type'] ?? 'str')));
        if ($type === 'int') {
            return (string) ($row['int_value'] ?? '');
        }
        if ($type === 'float') {
            return (string) ($row['float_value'] ?? '');
        }
        return (string) ($row['str_value'] ?? $row['value'] ?? '');
    }
}

if (!function_exists('v3aPostLoadFields')) {
    function v3aPostLoadFields(int $cid, $fallback = null): array
    {
        try {
            $db = \Typecho\Db::get();
            $rows = (array) $db->fetchAll(
                $db->select('name', 'type', 'str_value', 'int_value', 'float_value')
                    ->from('table.fields')
                    ->where('cid = ?', $cid)
                    ->order('name', \Typecho\Db::SORT_ASC)
            );
            if (!empty($rows)) {
                return $rows;
            }
        } catch (\Throwable $e) {
        }

        $rows = [];
        if (is_object($fallback)) {
            foreach (get_object_vars($fallback) as $name => $value) {
                $rows[] = [
                    'name' => $name,
                    'type' => 'str',
                    'str_value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                ];
            }
        }
        return $rows;
    }
}

if (!function_exists('v3aPostAssoc')) {
    function v3aPostAssoc(array $array): bool
    {
        $i = 0;
        foreach (array_keys($array) as $key) {
            if ($key !== $i) {
                return true;
            }
            $i++;
        }
        return false;
    }
}

if (!function_exists('v3aPostOptions')) {
    function v3aPostOptions($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        if (v3aPostAssoc($raw)) {
            foreach ($raw as $value => $label) {
                $value = trim((string) $value);
                $label = trim((string) $label);
                if ($value === '' && $label !== '') {
                    $value = $label;
                }
                if ($value === '') {
                    continue;
                }
                $out[] = ['value' => $value, 'label' => $label !== '' ? $label : $value];
            }
            return $out;
        }
        foreach ($raw as $item) {
            if (is_array($item)) {
                $value = trim((string) ($item['value'] ?? $item['id'] ?? ''));
                $label = trim((string) ($item['label'] ?? $item['text'] ?? ''));
                if ($value === '' && $label !== '') {
                    $value = $label;
                }
                if ($label === '' && $value !== '') {
                    $label = $value;
                }
            } else {
                $value = trim((string) $item);
                $label = $value;
            }
            if ($value === '') {
                continue;
            }
            $out[] = ['value' => $value, 'label' => $label];
        }
        return $out;
    }
}

if (!function_exists('v3aPostBuildSchema')) {
    function v3aPostBuildSchema(array $rows): array
    {
        $schemas = [];
        $limit = 0;
        $siteKey = '';
        $secretKey = '';
        $seq = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $lname = strtolower($name);
            $raw = trim(v3aPostFieldRaw($row));

            if ($lname === 'limit') {
                $limit = max(0, (int) $raw);
                continue;
            }
            if ($lname === 'recaptcha_v3_id') {
                $siteKey = $raw;
                continue;
            }
            if ($lname === 'recaptcha_v3_key') {
                $secretKey = $raw;
                continue;
            }

            if ($raw === '') {
                continue;
            }

            $json = json_decode($raw, true);
            if (!is_array($json)) {
                continue;
            }

            $type = strtolower(trim((string) ($json['type'] ?? '')));
            if ($type === 'textarea') {
                $type = 'editor';
            }
            if (!in_array($type, ['input', 'editor', 'checkbox', 'radio', 'select'], true)) {
                continue;
            }

            $key = 'k_' . substr(md5($name), 0, 12);
            while (isset($schemas[$key])) {
                $key .= 'x';
            }

            $seqValue = $seq++;
            $orderValue = array_key_exists('order', $json) && is_numeric($json['order'])
                ? (int) $json['order']
                : $seqValue;

            $schema = [
                'key' => $key,
                'name' => $name,
                'label' => trim((string) ($json['label'] ?? '')) ?: $name,
                'type' => $type,
                'order' => $orderValue,
                '_seq' => $seqValue,
                'required' => v3aPostBool($json['required'] ?? false),
                'description' => trim((string) ($json['description'] ?? '')),
                'placeholder' => trim((string) ($json['placeholder'] ?? '')),
                'input_type' => 'text',
                'rows' => 6,
                'multiple' => false,
                'max_length' => min(20000, max(0, (int) ($json['max_length'] ?? $json['maxLength'] ?? 0))),
                'min_length' => min(20000, max(0, (int) ($json['min_length'] ?? $json['minLength'] ?? 0))),
                'default' => '',
                'options' => [],
            ];

            if ($type === 'input') {
                $inputType = strtolower(trim((string) ($json['input_type'] ?? $json['inputType'] ?? 'text')));
                if (!in_array($inputType, ['text', 'email', 'url', 'number', 'tel', 'password', 'date', 'time', 'datetime-local'], true)) {
                    $inputType = 'text';
                }
                $schema['input_type'] = $inputType;
                $schema['default'] = (string) ($json['default'] ?? '');
            } elseif ($type === 'editor') {
                $schema['rows'] = min(20, max(3, (int) ($json['rows'] ?? 6)));
                $schema['default'] = (string) ($json['default'] ?? '');
            } else {
                $schema['options'] = v3aPostOptions($json['options'] ?? []);
                if (empty($schema['options'])) {
                    continue;
                }
                $schema['multiple'] = $type === 'checkbox' || ($type === 'select' && v3aPostBool($json['multiple'] ?? false));
                $default = $json['default'] ?? ($schema['multiple'] ? [] : '');
                if ($schema['multiple']) {
                    if (!is_array($default)) {
                        $default = [$default];
                    }
                    $schema['default'] = array_values(array_filter(array_map(static function ($v): string {
                        return trim((string) $v);
                    }, $default), static function (string $v): bool {
                        return $v !== '';
                    }));
                } else {
                    $schema['default'] = is_array($default) ? '' : trim((string) $default);
                }
            }

            $schemas[$key] = $schema;
        }

        $schemaList = array_values($schemas);
        usort($schemaList, static function (array $a, array $b): int {
            $oa = (int) ($a['order'] ?? 0);
            $ob = (int) ($b['order'] ?? 0);
            if ($oa !== $ob) {
                return $oa <=> $ob;
            }

            $sa = (int) ($a['_seq'] ?? 0);
            $sb = (int) ($b['_seq'] ?? 0);
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }

            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        foreach ($schemaList as &$schema) {
            unset($schema['_seq']);
        }
        unset($schema);

        return [
            'schemas' => $schemaList,
            'limit' => $limit,
            'recaptcha_id' => $siteKey,
            'recaptcha_key' => $secretKey,
        ];
    }
}

if (!function_exists('v3aPostIsAdmin')) {
    function v3aPostIsAdmin($archive): bool
    {
        $user = null;
        if (is_object($archive)) {
            try {
                $candidate = $archive->user ?? null;
                if (is_object($candidate)) {
                    $user = $candidate;
                }
            } catch (\Throwable $e) {
                $user = null;
            }
        }

        if (!is_object($user)) {
            try {
                $user = \Widget\User::alloc();
            } catch (\Throwable $e) {
                $user = null;
            }
        }

        if (!is_object($user) || !method_exists($user, 'hasLogin') || !$user->hasLogin()) {
            return false;
        }

        try {
            if (method_exists($user, 'pass') && ($user->pass('administrator', true) || $user->pass('editor', true))) {
                return true;
            }
        } catch (\Throwable $e) {
        }

        $group = '';
        try {
            $group = strtolower(trim((string) ($user->group ?? '')));
        } catch (\Throwable $e) {
            $group = '';
        }

        return in_array($group, ['administrator', 'editor'], true);
    }
}

if (!function_exists('v3aPostPrepareDir')) {
    function v3aPostPrepareDir(string $dir): bool
    {
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            return false;
        }
        if (!is_writable($dir)) {
            return false;
        }

        $htaccess = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
        }
        $webConfig = $dir . DIRECTORY_SEPARATOR . 'web.config';
        if (!is_file($webConfig)) {
            @file_put_contents($webConfig, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>\n  <system.webServer>\n    <security>\n      <authorization>\n        <clear />\n        <add accessType=\"Deny\" users=\"*\" />\n      </authorization>\n    </security>\n  </system.webServer>\n</configuration>\n");
        }
        $indexPhp = $dir . DIRECTORY_SEPARATOR . 'index.php';
        if (!is_file($indexPhp)) {
            @file_put_contents($indexPhp, "<?php\nhttp_response_code(404);\nexit;\n");
        }
        return true;
    }
}

if (!function_exists('v3aPostStoreLoad')) {
    function v3aPostStoreLoad(string $file, int $cid): array
    {
        $default = ['version' => 1, 'page_id' => $cid, 'updated_at' => 0, 'submissions' => []];
        if (!is_file($file)) {
            return $default;
        }
        $raw = @file_get_contents($file);
        if (!is_string($raw) || trim($raw) === '') {
            return $default;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $default;
        }
        $items = [];
        foreach ((array) ($decoded['submissions'] ?? []) as $item) {
            if (!is_array($item) || trim((string) ($item['id'] ?? '')) === '') {
                continue;
            }
            $items[] = [
                'id' => (string) $item['id'],
                'created' => max(0, (int) ($item['created'] ?? 0)),
                'updated' => max(0, (int) ($item['updated'] ?? 0)),
                'status' => trim((string) ($item['status'] ?? 'pending')),
                'fingerprint' => trim((string) ($item['fingerprint'] ?? '')),
                'values' => is_array($item['values'] ?? null) ? $item['values'] : [],
            ];
        }
        return [
            'version' => 1,
            'page_id' => (int) ($decoded['page_id'] ?? $cid),
            'updated_at' => max(0, (int) ($decoded['updated_at'] ?? 0)),
            'submissions' => $items,
        ];
    }
}

if (!function_exists('v3aPostStoreSave')) {
    function v3aPostStoreSave(string $file, array $store): bool
    {
        $json = json_encode($store, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if (!is_string($json)) {
            return false;
        }
        $written = @file_put_contents($file, $json, LOCK_EX);
        if ($written === false) {
            return false;
        }
        @chmod($file, 0600);
        return true;
    }
}

if (!function_exists('v3aPostFingerprint')) {
    function v3aPostFingerprint(string $ip, string $ua, string $salt): string
    {
        return hash('sha256', trim($ip) . '|' . trim($ua) . '|' . trim($salt));
    }
}

if (!function_exists('v3aPostLimitWait')) {
    function v3aPostLimitWait(array $submissions, string $fingerprint, int $limit): int
    {
        if ($limit <= 0 || $fingerprint === '') {
            return 0;
        }
        $last = 0;
        foreach ($submissions as $item) {
            if (!is_array($item) || (string) ($item['fingerprint'] ?? '') !== $fingerprint) {
                continue;
            }
            $created = max(0, (int) ($item['created'] ?? 0));
            if ($created > $last) {
                $last = $created;
            }
        }
        if ($last <= 0) {
            return 0;
        }
        $wait = ($last + $limit) - time();
        return $wait > 0 ? $wait : 0;
    }
}

if (!function_exists('v3aPostCollect')) {
    function v3aPostCollect(array $schemas, array $payload): array
    {
        $values = [];
        $errors = [];
        foreach ($schemas as $schema) {
            $key = (string) ($schema['key'] ?? '');
            $name = (string) ($schema['name'] ?? '');
            $label = (string) ($schema['label'] ?? $name);
            $type = (string) ($schema['type'] ?? 'input');
            $required = !empty($schema['required']);
            $raw = $payload[$key] ?? null;

            $allowed = [];
            foreach ((array) ($schema['options'] ?? []) as $option) {
                $allowed[] = (string) ($option['value'] ?? '');
            }

            if ($type === 'checkbox' || ($type === 'select' && !empty($schema['multiple']))) {
                $list = is_array($raw) ? $raw : (($raw !== null && $raw !== '') ? [$raw] : []);
                $value = [];
                foreach ($list as $item) {
                    $item = trim((string) $item);
                    if ($item === '' || !in_array($item, $allowed, true) || in_array($item, $value, true)) {
                        continue;
                    }
                    $value[] = $item;
                }
                if ($required && empty($value)) {
                    $errors[] = $label . ' 为必填项。';
                }
                $values[$name] = $value;
                continue;
            }

            if ($type === 'radio' || $type === 'select') {
                $value = trim((string) (is_array($raw) ? '' : $raw));
                if ($value !== '' && !in_array($value, $allowed, true)) {
                    $value = '';
                }
                if ($required && $value === '') {
                    $errors[] = $label . ' 为必填项。';
                }
                $values[$name] = $value;
                continue;
            }

            $value = str_replace(["\r\n", "\r"], "\n", trim((string) (is_array($raw) ? '' : $raw)));
            $max = max(0, (int) ($schema['max_length'] ?? 0));
            $min = max(0, (int) ($schema['min_length'] ?? 0));
            if ($max > 0 && v3aPostLen($value) > $max) {
                $value = v3aPostSubstr($value, $max);
            }
            if ($required && $value === '') {
                $errors[] = $label . ' 为必填项。';
            } elseif ($value !== '' && $min > 0 && v3aPostLen($value) < $min) {
                $errors[] = $label . ' 至少需要 ' . $min . ' 个字符。';
            }
            $values[$name] = $value;
        }
        return ['values' => $values, 'errors' => $errors];
    }
}

if (!function_exists('v3aPostHttpPost')) {
    function v3aPostHttpPost(string $url, array $params, int $timeout = 8): string
    {
        $query = http_build_query($params);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch !== false) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
                $res = curl_exec($ch);
                curl_close($ch);
                if (is_string($res)) {
                    return $res;
                }
            }
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $query,
                'timeout' => $timeout,
            ],
        ]);
        $res = @file_get_contents($url, false, $ctx);
        return is_string($res) ? $res : '';
    }
}

if (!function_exists('v3aPostVerifyCaptcha')) {
    function v3aPostVerifyCaptcha(string $secret, string $token, string $ip): array
    {
        if ($secret === '') {
            return ['ok' => true, 'message' => ''];
        }
        if ($token === '') {
            return ['ok' => false, 'message' => '请先完成人机验证。'];
        }
        foreach (['https://www.recaptcha.net/recaptcha/api/siteverify', 'https://www.google.com/recaptcha/api/siteverify'] as $endpoint) {
            $raw = v3aPostHttpPost($endpoint, ['secret' => $secret, 'response' => $token, 'remoteip' => $ip]);
            if ($raw === '') {
                continue;
            }
            $json = json_decode($raw, true);
            if (!is_array($json) || empty($json['success'])) {
                continue;
            }
            $action = trim((string) ($json['action'] ?? ''));
            if ($action !== '' && $action !== 'v3a_post_submit') {
                return ['ok' => false, 'message' => '验证动作不匹配，请重试。'];
            }
            $score = isset($json['score']) ? (float) $json['score'] : 1.0;
            if ($score < 0.3) {
                return ['ok' => false, 'message' => '人机验证评分过低，请稍后重试。'];
            }
            return ['ok' => true, 'message' => ''];
        }
        return ['ok' => false, 'message' => '人机验证服务暂不可用，请稍后重试。'];
    }
}

if (!function_exists('v3aPostUrlWithParams')) {
    function v3aPostUrlWithParams(string $url, array $set = [], array $remove = []): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $query = [];
        if (!empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
        }

        foreach ($remove as $name) {
            $name = trim((string) $name);
            if ($name !== '') {
                unset($query[$name]);
            }
        }

        foreach ($set as $name => $value) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            if ($value === null || $value === '') {
                unset($query[$name]);
                continue;
            }
            $query[$name] = (string) $value;
        }

        $built = '';
        if (isset($parts['scheme'])) {
            $built .= $parts['scheme'] . '://';
        }
        if (isset($parts['user'])) {
            $built .= $parts['user'];
            if (isset($parts['pass'])) {
                $built .= ':' . $parts['pass'];
            }
            $built .= '@';
        }
        if (isset($parts['host'])) {
            $built .= $parts['host'];
        }
        if (isset($parts['port'])) {
            $built .= ':' . $parts['port'];
        }
        $built .= (string) ($parts['path'] ?? '');

        $queryString = http_build_query($query);
        if ($queryString !== '') {
            $built .= '?' . $queryString;
        }
        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $built .= '#' . $parts['fragment'];
        }

        return $built !== '' ? $built : $url;
    }
}

if (!function_exists('v3aPostNoticeEncode')) {
    function v3aPostNoticeEncode(string $type, string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return '';
        }

        $payload = json_encode([
            't' => $type === 'error' ? 'error' : 'success',
            'm' => $message,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!is_string($payload) || $payload === '') {
            return '';
        }

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }
}

if (!function_exists('v3aPostNoticeDecode')) {
    function v3aPostNoticeDecode(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            return [];
        }

        $raw = strtr($token, '-_', '+/');
        $pad = strlen($raw) % 4;
        if ($pad > 0) {
            $raw .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($raw, true);
        if (!is_string($decoded) || $decoded === '') {
            return [];
        }

        $data = json_decode($decoded, true);
        if (!is_array($data)) {
            return [];
        }

        $type = (string) ($data['t'] ?? '');
        if ($type !== 'error') {
            $type = 'success';
        }

        $message = trim((string) ($data['m'] ?? ''));
        if ($message === '') {
            return [];
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }
}

if (!function_exists('v3aPostRedirectWithNotice')) {
    function v3aPostRedirectWithNotice(string $baseUrl, string $type, string $message): void
    {
        $token = v3aPostNoticeEncode($type, $message);
        if ($token === '') {
            return;
        }

        $target = v3aPostUrlWithParams($baseUrl, ['v3a_post_notice' => $token]);
        if (!headers_sent()) {
            header('Location: ' . $target, true, 303);
            exit;
        }
    }
}

if (!function_exists('v3aPostGetOptionsWidget')) {
    function v3aPostGetOptionsWidget()
    {
        try {
            if (class_exists('\\Widget\\Options')) {
                return \Widget\Options::alloc();
            }
        } catch (\Throwable $e) {
        }

        try {
            if (class_exists('Widget_Options')) {
                return \Widget_Options::alloc();
            }
        } catch (\Throwable $e) {
        }

        return null;
    }
}

if (!function_exists('v3aPostGetThemeOption')) {
    function v3aPostGetThemeOption($archive, string $key, string $default = ''): string
    {
        $options = v3aPostGetOptionsWidget();
        if (function_exists('classic22LinuxDoGetOption') && is_object($options)) {
            return (string) classic22LinuxDoGetOption($options, $key, $default);
        }

        if (is_object($options)) {
            try {
                $value = $options->{$key};
                if ($value !== null && !is_array($value) && !is_object($value)) {
                    $normalized = trim((string) $value);
                    if ($normalized !== '') {
                        return $normalized;
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        return $default;
    }
}

if (!function_exists('v3aPostDefaultAiPrompt')) {
    function v3aPostDefaultAiPrompt(): string
    {
        return "你是博客投稿内容生成助手。你将得到一个网页链接，以及从网页解析出的标题/作者/描述/正文文本。\n"
            . "请基于这些信息生成一篇用于本站发布的投稿文章，并只返回严格 JSON（不要代码块、不要附加文字）。\n\n"
            . "JSON 格式：\n"
            . "{\n"
            . "  \"title\": \"\",\n"
            . "  \"content\": \"\",\n"
            . "  \"project_link\": \"\",\n"
            . "  \"project_type\": \"typecho|halo\",\n"
            . "  \"project_author\": \"\"\n"
            . "}\n\n"
            . "要求：\n"
            . "- 禁止编造不存在的功能、数据、作者信息；不确定的字段请返回空字符串。\n"
            . "- title：简短明确（<=60字）。\n"
            . "- project_link：必须是有效 URL，优先项目主页/仓库地址；否则使用原始链接。\n"
            . "- project_type：只能是 typecho 或 halo；无法判断则根据内容关键词推断，仍不确定则留空。\n"
            . "- content：使用 Markdown，包含：简介、主要特性、安装/使用、相关链接（至少包含 project_link）。";
    }
}

if (!function_exists('v3aPostDefaultSchemas')) {
    function v3aPostDefaultSchemas(): array
    {
        return [
            [
                'key' => 'source_url',
                'name' => 'source_url',
                'label' => '投稿链接',
                'type' => 'input',
                'order' => 0,
                'required' => true,
                'description' => '仅需粘贴链接，系统将自动解析页面并生成投稿内容。',
                'placeholder' => 'https://',
                'input_type' => 'url',
                'rows' => 6,
                'multiple' => false,
                'max_length' => 2048,
                'min_length' => 10,
                'default' => '',
                'options' => [],
            ],
            [
                'key' => 'title',
                'name' => 'title',
                'label' => '标题',
                'type' => 'input',
                'order' => 10,
                'required' => false,
                'description' => '',
                'placeholder' => '',
                'input_type' => 'text',
                'rows' => 6,
                'multiple' => false,
                'max_length' => 200,
                'min_length' => 0,
                'default' => '',
                'options' => [],
            ],
            [
                'key' => 'project_author',
                'name' => 'project_author',
                'label' => '项目作者',
                'type' => 'input',
                'order' => 20,
                'required' => false,
                'description' => '',
                'placeholder' => '',
                'input_type' => 'text',
                'rows' => 6,
                'multiple' => false,
                'max_length' => 120,
                'min_length' => 0,
                'default' => '',
                'options' => [],
            ],
            [
                'key' => 'project_type',
                'name' => 'project_type',
                'label' => '项目类型',
                'type' => 'select',
                'order' => 30,
                'required' => false,
                'description' => '',
                'placeholder' => '',
                'input_type' => 'text',
                'rows' => 6,
                'multiple' => false,
                'max_length' => 32,
                'min_length' => 0,
                'default' => '',
                'options' => [
                    ['value' => 'typecho', 'label' => 'Typecho'],
                    ['value' => 'halo', 'label' => 'Halo'],
                ],
            ],
            [
                'key' => 'project_link',
                'name' => 'project_link',
                'label' => '项目链接',
                'type' => 'input',
                'order' => 40,
                'required' => false,
                'description' => '',
                'placeholder' => 'https://',
                'input_type' => 'url',
                'rows' => 6,
                'multiple' => false,
                'max_length' => 2048,
                'min_length' => 0,
                'default' => '',
                'options' => [],
            ],
            [
                'key' => 'content',
                'name' => 'content',
                'label' => '文章内容（Markdown）',
                'type' => 'editor',
                'order' => 50,
                'required' => false,
                'description' => '',
                'placeholder' => '',
                'input_type' => 'text',
                'rows' => 12,
                'multiple' => false,
                'max_length' => 20000,
                'min_length' => 0,
                'default' => '',
                'options' => [],
            ],
        ];
    }
}

if (!function_exists('v3aPostNormalizeUrl')) {
    function v3aPostNormalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $url = preg_replace('/[\\x00-\\x1F\\x7F]+/', '', $url) ?? $url;
        $url = trim($url);

        if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $url) && !preg_match('~^https?://~i', $url)) {
            return $url;
        }

        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        return $url;
    }
}

if (!function_exists('v3aPostIpv4ToLong')) {
    function v3aPostIpv4ToLong(string $ip): ?int
    {
        $long = ip2long($ip);
        if ($long === false) {
            return null;
        }

        return (int) sprintf('%u', $long);
    }
}

if (!function_exists('v3aPostIpv6MatchPrefix')) {
    function v3aPostIpv6MatchPrefix(string $ip, string $network, int $prefixBits): bool
    {
        $ipBin = @inet_pton($ip);
        $netBin = @inet_pton($network);
        if ($ipBin === false || $netBin === false) {
            return false;
        }

        $bytes = intdiv($prefixBits, 8);
        $bits = $prefixBits % 8;

        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) {
            return false;
        }

        if ($bits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $bits)) & 0xff;
        return (ord($ipBin[$bytes]) & $mask) === (ord($netBin[$bytes]) & $mask);
    }
}

if (!function_exists('v3aPostIsPrivateIp')) {
    function v3aPostIsPrivateIp(string $ip): bool
    {
        $ip = trim($ip);
        if ($ip === '') {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = v3aPostIpv4ToLong($ip);
            if ($long === null) {
                return true;
            }

            $ranges = [
                ['0.0.0.0', '0.255.255.255'],
                ['10.0.0.0', '10.255.255.255'],
                ['100.64.0.0', '100.127.255.255'],
                ['127.0.0.0', '127.255.255.255'],
                ['169.254.0.0', '169.254.255.255'],
                ['172.16.0.0', '172.31.255.255'],
                ['192.0.0.0', '192.0.0.255'],
                ['192.168.0.0', '192.168.255.255'],
                ['192.0.2.0', '192.0.2.255'],
                ['198.18.0.0', '198.19.255.255'],
                ['198.51.100.0', '198.51.100.255'],
                ['203.0.113.0', '203.0.113.255'],
                ['224.0.0.0', '255.255.255.255'],
            ];

            foreach ($ranges as $range) {
                $start = v3aPostIpv4ToLong($range[0]);
                $end = v3aPostIpv4ToLong($range[1]);
                if ($start === null || $end === null) {
                    continue;
                }
                if ($long >= $start && $long <= $end) {
                    return true;
                }
            }

            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($ip === '::' || $ip === '::1') {
                return true;
            }
            if (v3aPostIpv6MatchPrefix($ip, 'fc00::', 7)) {
                return true;
            }
            if (v3aPostIpv6MatchPrefix($ip, 'fe80::', 10)) {
                return true;
            }
            if (v3aPostIpv6MatchPrefix($ip, 'ff00::', 8)) {
                return true;
            }
            if (v3aPostIpv6MatchPrefix($ip, '2001:db8::', 32)) {
                return true;
            }

            return false;
        }

        return true;
    }
}

if (!function_exists('v3aPostResolveHostIps')) {
    function v3aPostResolveHostIps(string $host): array
    {
        $host = trim($host);
        if ($host === '') {
            return [];
        }

        $ips = [];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
            return $ips;
        }

        $v4 = @gethostbynamel($host);
        if (is_array($v4)) {
            foreach ($v4 as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $ips[] = $ip;
                }
            }
        }

        if (function_exists('dns_get_record') && defined('DNS_AAAA')) {
            $records = @dns_get_record($host, DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $record) {
                    $ip = trim((string) ($record['ipv6'] ?? ''));
                    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        return array_values(array_unique($ips));
    }
}

if (!function_exists('v3aPostValidateRemoteUrl')) {
    function v3aPostValidateRemoteUrl(string $url): array
    {
        $url = v3aPostNormalizeUrl($url);
        if ($url === '') {
            return ['ok' => false, 'url' => '', 'message' => '请输入链接。'];
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return ['ok' => false, 'url' => '', 'message' => '链接格式无效。'];
        }

        $scheme = strtolower(trim((string) ($parts['scheme'] ?? '')));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return ['ok' => false, 'url' => '', 'message' => '仅支持 http/https 链接。'];
        }

        if (!empty($parts['user']) || !empty($parts['pass'])) {
            return ['ok' => false, 'url' => '', 'message' => '不支持带账号密码的链接。'];
        }

        $host = strtolower(trim((string) ($parts['host'] ?? '')));
        if ($host === '') {
            return ['ok' => false, 'url' => '', 'message' => '链接缺少域名。'];
        }

        if ($host === 'localhost' || substr($host, -10) === '.localhost') {
            return ['ok' => false, 'url' => '', 'message' => '不允许访问本机地址。'];
        }

        $ips = v3aPostResolveHostIps($host);
        if (empty($ips)) {
            return ['ok' => false, 'url' => '', 'message' => '无法解析域名，请检查链接是否正确。'];
        }

        foreach ($ips as $ip) {
            if (v3aPostIsPrivateIp($ip)) {
                return ['ok' => false, 'url' => '', 'message' => '不允许访问内网地址。'];
            }
        }

        return ['ok' => true, 'url' => $url, 'message' => ''];
    }
}

if (!function_exists('v3aPostBuildAbsoluteUrl')) {
    function v3aPostBuildAbsoluteUrl(string $baseUrl, string $location): string
    {
        $location = trim($location);
        if ($location === '') {
            return '';
        }

        if (preg_match('~^https?://~i', $location)) {
            return $location;
        }

        if (strpos($location, '//') === 0) {
            $scheme = (string) (parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https');
            return $scheme . ':' . $location;
        }

        $scheme = (string) (parse_url($baseUrl, PHP_URL_SCHEME) ?? '');
        $host = (string) (parse_url($baseUrl, PHP_URL_HOST) ?? '');
        $port = (string) (parse_url($baseUrl, PHP_URL_PORT) ?? '');
        $path = (string) (parse_url($baseUrl, PHP_URL_PATH) ?? '/');

        if ($scheme === '' || $host === '') {
            return '';
        }

        $origin = $scheme . '://' . $host;
        if ($port !== '') {
            $origin .= ':' . $port;
        }

        if (strpos($location, '/') === 0) {
            return $origin . $location;
        }

        $dir = rtrim(str_replace('\\', '/', dirname($path)), '/');
        if ($dir === '.') {
            $dir = '';
        }

        return $origin . $dir . '/' . ltrim($location, '/');
    }
}

if (!function_exists('v3aPostHttpFetch')) {
    function v3aPostHttpFetch(string $url, int $timeout = 12, int $maxBytes = 1048576): array
    {
        $timeout = max(3, min(30, (int) $timeout));
        $maxBytes = max(32 * 1024, min(2 * 1024 * 1024, (int) $maxBytes));

        $headers = [];
        $body = '';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return ['ok' => false, 'status' => 0, 'headers' => [], 'body' => '', 'error' => '初始化请求失败'];
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Typecho SubmitBot/1.0)');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function ($ch, string $line) use (&$headers): int {
                $trimmed = trim($line);
                if ($trimmed !== '' && strpos($trimmed, ':') !== false) {
                    [$name, $value] = explode(':', $trimmed, 2);
                    $name = strtolower(trim($name));
                    $value = trim($value);
                    if ($name !== '') {
                        if (isset($headers[$name])) {
                            if (is_array($headers[$name])) {
                                $headers[$name][] = $value;
                            } else {
                                $headers[$name] = [$headers[$name], $value];
                            }
                        } else {
                            $headers[$name] = $value;
                        }
                    }
                }

                return strlen($line);
            });

            curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($ch, string $data) use (&$body, $maxBytes): int {
                $remaining = $maxBytes - strlen($body);
                if ($remaining > 0) {
                    if (strlen($data) > $remaining) {
                        $body .= substr($data, 0, $remaining);
                    } else {
                        $body .= $data;
                    }
                }

                return strlen($data);
            });

            $ok = curl_exec($ch);
            $error = curl_error($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if ($ok === false) {
                return [
                    'ok' => false,
                    'status' => $status,
                    'headers' => $headers,
                    'body' => $body,
                    'error' => $error !== '' ? $error : '请求失败',
                ];
            }

            return [
                'ok' => $status >= 200 && $status < 300,
                'status' => $status,
                'headers' => $headers,
                'body' => $body,
                'error' => $error,
            ];
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeout,
                'header' => "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                    "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8\r\n" .
                    "User-Agent: Mozilla/5.0 (compatible; Typecho SubmitBot/1.0)\r\n",
            ],
        ]);

        $res = @file_get_contents($url, false, $ctx);
        if (!is_string($res)) {
            return ['ok' => false, 'status' => 0, 'headers' => [], 'body' => '', 'error' => '请求失败'];
        }

        if (strlen($res) > $maxBytes) {
            $res = substr($res, 0, $maxBytes);
        }

        return ['ok' => true, 'status' => 200, 'headers' => [], 'body' => $res, 'error' => ''];
    }
}

if (!function_exists('v3aPostFetchHtml')) {
    function v3aPostFetchHtml(string $url, int $maxRedirects = 3): array
    {
        $current = $url;
        $lastStatus = 0;
        $lastError = '';

        for ($i = 0; $i <= $maxRedirects; $i++) {
            $response = v3aPostHttpFetch($current, 12, 1024 * 1024);
            $status = (int) ($response['status'] ?? 0);
            $headers = is_array($response['headers'] ?? null) ? $response['headers'] : [];
            $body = (string) ($response['body'] ?? '');
            $lastStatus = $status;
            $lastError = (string) ($response['error'] ?? '');

            $location = $headers['location'] ?? '';
            if (is_array($location)) {
                $location = (string) (end($location) ?: '');
            }
            $location = trim((string) $location);

            if ($status >= 300 && $status < 400 && $location !== '') {
                $next = v3aPostBuildAbsoluteUrl($current, $location);
                if ($next === '') {
                    return ['ok' => false, 'url' => $current, 'status' => $status, 'body' => '', 'message' => '跳转链接无效。'];
                }

                $validated = v3aPostValidateRemoteUrl($next);
                if (empty($validated['ok'])) {
                    return ['ok' => false, 'url' => $current, 'status' => $status, 'body' => '', 'message' => '跳转链接不被允许。'];
                }

                $current = (string) ($validated['url'] ?? $next);
                continue;
            }

            if ($status >= 200 && $status < 300) {
                $ctype = $headers['content-type'] ?? '';
                if (is_array($ctype)) {
                    $ctype = (string) (end($ctype) ?: '');
                }
                $ctype = strtolower((string) $ctype);
                if ($ctype !== '' && strpos($ctype, 'text/html') === false && strpos($ctype, 'application/xhtml+xml') === false) {
                    return ['ok' => false, 'url' => $current, 'status' => $status, 'body' => '', 'message' => '仅支持解析 HTML 页面。'];
                }

                $body = trim($body);
                if ($body === '') {
                    return ['ok' => false, 'url' => $current, 'status' => $status, 'body' => '', 'message' => '页面内容为空。'];
                }

                return ['ok' => true, 'url' => $current, 'status' => $status, 'body' => $body, 'message' => ''];
            }

            $statusText = $status > 0 ? ('HTTP ' . $status) : '请求失败';
            $message = $lastError !== '' ? $statusText . '：' . $lastError : $statusText;
            return ['ok' => false, 'url' => $current, 'status' => $status, 'body' => '', 'message' => $message];
        }

        $statusText = $lastStatus > 0 ? ('HTTP ' . $lastStatus) : '请求失败';
        $message = $lastError !== '' ? $statusText . '：' . $lastError : ($statusText . '：跳转次数过多');
        return ['ok' => false, 'url' => $current, 'status' => $lastStatus, 'body' => '', 'message' => $message];
    }
}

if (!function_exists('v3aPostExtractPageInfo')) {
    function v3aPostExtractPageInfo(string $html): array
    {
        $title = '';
        $author = '';
        $description = '';
        $text = '';

        if (class_exists('DOMDocument')) {
            $prev = libxml_use_internal_errors(true);
            $doc = new \DOMDocument();
            $loaded = false;
            try {
                $loaded = @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET);
            } catch (\Throwable $e) {
                $loaded = false;
            }

            if ($loaded) {
                $xpath = new \DOMXPath($doc);

                $stripList = $xpath->query('//script|//style|//noscript');
                if ($stripList instanceof \DOMNodeList) {
                    foreach ($stripList as $node) {
                        if ($node instanceof \DOMNode && $node->parentNode) {
                            $node->parentNode->removeChild($node);
                        }
                    }
                }

                $meta = static function (string $query) use ($xpath): string {
                    $list = $xpath->query($query);
                    if (!($list instanceof \DOMNodeList)) {
                        return '';
                    }

                    $node = $list->item(0);
                    if (!($node instanceof \DOMNode)) {
                        return '';
                    }

                    return trim((string) ($node->nodeValue ?? ''));
                };

                $title = $meta("//meta[@property='og:title']/@content");
                if ($title === '') {
                    $title = $meta("//meta[@name='twitter:title']/@content");
                }
                if ($title === '') {
                    $titleList = $xpath->query('//title');
                    $titleNode = $titleList instanceof \DOMNodeList ? $titleList->item(0) : null;
                    $title = $titleNode instanceof \DOMNode ? trim((string) ($titleNode->textContent ?? '')) : '';
                }

                $description = $meta("//meta[@property='og:description']/@content");
                if ($description === '') {
                    $description = $meta("//meta[@name='description']/@content");
                }
                if ($description === '') {
                    $description = $meta("//meta[@name='twitter:description']/@content");
                }

                $author = $meta("//meta[@name='author']/@content");
                if ($author === '') {
                    $author = $meta("//meta[@property='article:author']/@content");
                }

                $candidates = [];
                $candidateList = $xpath->query('//article|//main');
                if ($candidateList instanceof \DOMNodeList) {
                    foreach ($candidateList as $node) {
                        if ($node instanceof \DOMNode) {
                            $candidates[] = $node;
                        }
                    }
                }
                $best = null;
                $bestLen = 0;
                foreach ($candidates as $node) {
                    $candidateText = trim((string) ($node->textContent ?? ''));
                    $candidateText = preg_replace('/\\s+/u', ' ', $candidateText) ?? '';
                    $len = function_exists('mb_strlen') ? (int) mb_strlen($candidateText) : strlen($candidateText);
                    if ($len > $bestLen) {
                        $bestLen = $len;
                        $best = $node;
                    }
                }

                if ($best instanceof \DOMNode) {
                    $text = trim((string) ($best->textContent ?? ''));
                } else {
                    $bodyList = $xpath->query('//body');
                    $bodyNode = $bodyList instanceof \DOMNodeList ? $bodyList->item(0) : null;
                    $text = $bodyNode instanceof \DOMNode ? trim((string) ($bodyNode->textContent ?? '')) : '';
                }

                libxml_clear_errors();
            }
            libxml_use_internal_errors($prev);
        }

        if ($text === '') {
            $text = strip_tags($html);
        }

        $title = trim((string) preg_replace('/\\s+/u', ' ', $title));
        $author = trim((string) preg_replace('/\\s+/u', ' ', $author));
        $description = trim((string) preg_replace('/\\s+/u', ' ', $description));
        $text = trim((string) preg_replace('/\\s+/u', ' ', $text));

        if (function_exists('mb_substr')) {
            if (mb_strlen($text) > 12000) {
                $text = (string) mb_substr($text, 0, 12000);
            }
        } else {
            if (strlen($text) > 12000) {
                $text = substr($text, 0, 12000);
            }
        }

        return [
            'title' => $title,
            'author' => $author,
            'description' => $description,
            'text' => $text,
        ];
    }
}

if (!function_exists('v3aPostGuessProjectType')) {
    function v3aPostGuessProjectType(string $url, array $pageInfo): string
    {
        $haystack = strtolower($url . ' ' . (string) ($pageInfo['title'] ?? '') . ' ' . (string) ($pageInfo['text'] ?? ''));
        if (strpos($haystack, 'halo') !== false) {
            return 'halo';
        }
        if (strpos($haystack, 'typecho') !== false || strpos($haystack, 'typecho ') !== false) {
            return 'typecho';
        }
        return '';
    }
}

if (!function_exists('v3aPostAiGenerate')) {
    function v3aPostAiGenerate($archive, string $sourceUrl, array $pageInfo): array
    {
        $options = function_exists('v3aPostGetOptionsWidget') ? v3aPostGetOptionsWidget() : null;

        $prompt = trim((string) v3aPostGetThemeOption($archive, 'v3aPostAiPrompt', ''));
        if ($prompt === '') {
            $prompt = v3aPostDefaultAiPrompt();
        }

        $seed = [
            'source_url' => $sourceUrl,
            'page_title' => (string) ($pageInfo['title'] ?? ''),
            'page_author' => (string) ($pageInfo['author'] ?? ''),
            'page_description' => (string) ($pageInfo['description'] ?? ''),
            'page_text' => (string) ($pageInfo['text'] ?? ''),
        ];

        $messages = [
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user', 'content' => '请根据以下网页信息生成投稿内容：' . json_encode($seed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
        ];

        $answer = '';

        $themeApiKey = trim((string) v3aPostGetThemeOption($archive, 'aiApiKey', ''));
        $themeMissing = [];
        foreach ([
            'classic22AiSanitizeBaseUrl',
            'classic22AiResolveApiMode',
            'classic22AiBuildChatCompletionsPayload',
            'classic22AiBuildResponsesPayload',
            'classic22AiDefaultModel',
            'classic22AiRequest',
            'classic22AiExtractAnswerByMode',
        ] as $fn) {
            if (!function_exists($fn)) {
                $themeMissing[] = $fn;
            }
        }
        $themeAiReady = $themeApiKey !== '' && $options !== null && empty($themeMissing);

        if ($themeAiReady) {
            $provider = strtolower(trim((string) v3aPostGetThemeOption($archive, 'aiProvider', 'openai')));
            if (!in_array($provider, ['openai', 'rightcode'], true)) {
                $provider = 'openai';
            }

            $baseUrl = classic22AiSanitizeBaseUrl((string) v3aPostGetThemeOption($archive, 'aiApiBaseUrl', 'https://api.openai.com/v1'), $provider);
            if ($provider === 'rightcode') {
                $baseLower = strtolower($baseUrl);
                if ($baseLower === '' || strpos($baseLower, 'api.openai.com') !== false) {
                    $baseUrl = 'https://www.right.codes/codex/v1';
                }
            }

            $mode = classic22AiResolveApiMode($options);
            if ($provider === 'rightcode' && $mode !== 'responses') {
                $mode = 'responses';
            }

            $model = classic22AiDefaultModel($options);
            $apiUrl = rtrim($baseUrl, '/') . ($mode === 'responses' ? '/responses' : '/chat/completions');

            $payload = $mode === 'responses'
                ? classic22AiBuildResponsesPayload($model, $messages)
                : classic22AiBuildChatCompletionsPayload($model, $messages);

            if (!is_string($payload) || trim($payload) === '') {
                return ['ok' => false, 'message' => 'AI 请求体生成失败。', 'data' => []];
            }

            $response = classic22AiRequest($apiUrl, $payload, $themeApiKey);
            if (empty($response['ok'])) {
                $remoteMsg = function_exists('classic22AiExtractRemoteErrorMessage')
                    ? classic22AiExtractRemoteErrorMessage($response)
                    : '';
                $message = $remoteMsg !== '' ? $remoteMsg : ('AI 请求失败（' . (int) ($response['status'] ?? 0) . '）');
                return ['ok' => false, 'message' => $message, 'data' => []];
            }

            $decoded = function_exists('classic22AiDecodeJsonBody')
                ? classic22AiDecodeJsonBody((string) ($response['body'] ?? ''))
                : json_decode((string) ($response['body'] ?? ''), true);
            if (!is_array($decoded)) {
                $message = 'AI 返回格式无效。';
                if (function_exists('v3aPostIsAdmin') && v3aPostIsAdmin($archive) && function_exists('classic22AiResponseBodyExcerpt')) {
                    $excerpt = classic22AiResponseBodyExcerpt($response, 240);
                    if ($excerpt !== '') {
                        $message .= '（' . $excerpt . '）';
                    }
                }
                return ['ok' => false, 'message' => $message, 'data' => []];
            }

            if (function_exists('classic22AiExtractErrorMessageFromDecoded')) {
                $decodedError = trim((string) classic22AiExtractErrorMessageFromDecoded($decoded));
                if ($decodedError !== '') {
                    if (function_exists('classic22AiNormalizeRemoteError')) {
                        $decodedError = classic22AiNormalizeRemoteError($decodedError);
                    }
                    return ['ok' => false, 'message' => $decodedError, 'data' => []];
                }
            }

            $answer = trim(classic22AiExtractAnswerByMode($decoded, $mode));
            if ($answer === '' && $mode === 'responses' && function_exists('classic22AiBuildChatCompletionsPayload')) {
                $fallbackApiUrl = rtrim($baseUrl, '/') . '/chat/completions';
                $fallbackPayload = classic22AiBuildChatCompletionsPayload($model, $messages);
                if (is_string($fallbackPayload) && trim($fallbackPayload) !== '') {
                    $fallbackResponse = classic22AiRequest($fallbackApiUrl, $fallbackPayload, $themeApiKey);
                    if (!empty($fallbackResponse['ok'])) {
                        $fallbackDecoded = function_exists('classic22AiDecodeJsonBody')
                            ? classic22AiDecodeJsonBody((string) ($fallbackResponse['body'] ?? ''))
                            : json_decode((string) ($fallbackResponse['body'] ?? ''), true);
                        if (is_array($fallbackDecoded) && function_exists('classic22AiExtractErrorMessageFromDecoded')) {
                            $fallbackError = trim((string) classic22AiExtractErrorMessageFromDecoded($fallbackDecoded));
                            if ($fallbackError !== '') {
                                if (function_exists('classic22AiNormalizeRemoteError')) {
                                    $fallbackError = classic22AiNormalizeRemoteError($fallbackError);
                                }
                                return ['ok' => false, 'message' => $fallbackError, 'data' => []];
                            }
                        }

                        if (is_array($fallbackDecoded)) {
                            $fallbackAnswer = trim(classic22AiExtractAnswerByMode($fallbackDecoded, 'chat_completions'));
                            if ($fallbackAnswer !== '') {
                                $answer = $fallbackAnswer;
                            }
                        }
                    }
                }
            }
            if ($answer === '') {
                return ['ok' => false, 'message' => 'AI 未返回有效内容。', 'data' => []];
            }
        } elseif ($themeApiKey !== '') {
            $reason = [];
            if ($options === null) {
                $reason[] = 'options 未加载';
            }
            if (!empty($themeMissing)) {
                $reason[] = '缺少函数：' . implode(', ', $themeMissing);
            }
            $detail = !empty($reason) ? ('（' . implode('；', $reason) . '）') : '';
            return ['ok' => false, 'message' => '主题 AI 组件未就绪' . $detail . '。', 'data' => []];
        } else {
            $vue3AdminApiKey = '';
            try {
                $vue3AdminApiKey = trim((string) (($options !== null ? ($options->v3a_ai_api_key ?? '') : '') ?? ''));
            } catch (\Throwable $e) {
                $vue3AdminApiKey = '';
            }

            if ($vue3AdminApiKey === '') {
                return ['ok' => false, 'message' => '未配置 AI API Key，将仅使用基础解析生成内容。', 'data' => []];
            }

            $aiClass = '\\TypechoPlugin\\Vue3Admin\\Ai';
            if (!class_exists($aiClass)) {
                $aiFile = '';
                if (defined('__TYPECHO_ROOT_DIR__')) {
                    $candidate = rtrim((string) __TYPECHO_ROOT_DIR__, DIRECTORY_SEPARATOR)
                        . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'Vue3Admin' . DIRECTORY_SEPARATOR . 'Ai.php';
                    if (is_file($candidate)) {
                        $aiFile = $candidate;
                    }
                }
                if ($aiFile === '') {
                    $candidate = dirname(__DIR__, 3)
                        . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'Vue3Admin' . DIRECTORY_SEPARATOR . 'Ai.php';
                    if (is_file($candidate)) {
                        $aiFile = $candidate;
                    }
                }
                if ($aiFile !== '' && defined('__TYPECHO_ROOT_DIR__')) {
                    require_once $aiFile;
                }
            }

            if (!class_exists($aiClass)) {
                return ['ok' => false, 'message' => '未找到 Vue3Admin AI 组件，请确认插件已安装。', 'data' => []];
            }

            $cfg = [];
            try {
                $cfg = $aiClass::getRuntimeConfig($options);
            } catch (\Throwable $e) {
                $cfg = [];
            }
            if (!is_array($cfg)) {
                $cfg = [];
            }
            $cfg['enabled'] = 1;
            $cfg['apiKey'] = $vue3AdminApiKey;

            try {
                $chat = $aiClass::chat($cfg, $messages);
            } catch (\Throwable $e) {
                $msg = trim((string) $e->getMessage());
                return ['ok' => false, 'message' => $msg !== '' ? $msg : 'AI 请求失败。', 'data' => []];
            }

            $answer = trim((string) ($chat['content'] ?? ''));
            if ($answer === '') {
                return ['ok' => false, 'message' => 'AI 未返回有效内容。', 'data' => []];
            }
        }

        if (preg_match('/```(?:json)?\\s*(\\{.*\\})\\s*```/is', $answer, $match)) {
            $answer = trim((string) ($match[1] ?? ''));
        }

        $parsed = json_decode($answer, true);
        if (!is_array($parsed)) {
            $start = strpos($answer, '{');
            $end = strrpos($answer, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $snippet = substr($answer, $start, $end - $start + 1);
                $parsed = json_decode((string) $snippet, true);
            }
        }

        if (!is_array($parsed)) {
            return ['ok' => false, 'message' => 'AI 输出无法解析为 JSON。', 'data' => []];
        }

        $title = trim((string) ($parsed['title'] ?? ''));
        $content = trim((string) ($parsed['content'] ?? ''));
        $projectLink = trim((string) ($parsed['project_link'] ?? ''));
        $projectType = trim((string) ($parsed['project_type'] ?? ''));
        $projectAuthor = trim((string) ($parsed['project_author'] ?? ''));

        if ($projectType !== '' && !in_array($projectType, ['typecho', 'halo'], true)) {
            $projectType = '';
        }

        return [
            'ok' => true,
            'message' => '',
            'data' => [
                'title' => $title,
                'content' => $content,
                'project_link' => $projectLink,
                'project_type' => $projectType,
                'project_author' => $projectAuthor,
            ],
        ];
    }
}

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
    header('X-Content-Type-Options: nosniff', true);
}

$charset = (string) ($this->options->charset ?? 'UTF-8');
$isAdmin = v3aPostIsAdmin($this);
$schemas = v3aPostDefaultSchemas();
$submitSchemas = array_values(array_filter($schemas, static function (array $schema): bool {
    return (string) ($schema['name'] ?? '') === 'source_url';
}));
$previewSchemas = array_values(array_filter($schemas, static function (array $schema): bool {
    $name = (string) ($schema['name'] ?? '');
    return in_array($name, ['source_url', 'title', 'project_type', 'project_author', 'project_link'], true);
}));

$legacyRows = v3aPostLoadFields((int) $this->cid, $this->fields ?? null);
$legacyConfig = v3aPostBuildSchema($legacyRows);
$legacyLimitSeconds = max(0, (int) ($legacyConfig['limit'] ?? 0));
$legacyRecaptchaId = trim((string) ($legacyConfig['recaptcha_id'] ?? ''));
$legacyRecaptchaKey = trim((string) ($legacyConfig['recaptcha_key'] ?? ''));

$themeLimitRaw = trim(v3aPostGetThemeOption($this, 'v3aPostLimitSeconds', ''));
if ($themeLimitRaw !== '') {
    $limitSeconds = max(0, (int) $themeLimitRaw);
} else {
    $limitSeconds = $legacyLimitSeconds > 0 ? $legacyLimitSeconds : 60;
}

$themeRecaptchaId = trim(v3aPostGetThemeOption($this, 'v3aPostRecaptchaV3SiteKey', ''));
$themeRecaptchaKey = trim(v3aPostGetThemeOption($this, 'v3aPostRecaptchaV3SecretKey', ''));
$recaptchaId = $themeRecaptchaId !== '' ? $themeRecaptchaId : $legacyRecaptchaId;
$recaptchaKey = $themeRecaptchaKey !== '' ? $themeRecaptchaKey : $legacyRecaptchaKey;
$recaptchaSiteEnabled = $recaptchaId !== '';
$recaptchaVerifyEnabled = $recaptchaSiteEnabled && $recaptchaKey !== '';

$aiSource = '';
$themeAiApiKey = trim(v3aPostGetThemeOption($this, 'aiApiKey', ''));
if ($themeAiApiKey !== '') {
    $aiSource = 'theme';
} else {
    $vue3AdminApiKey = '';
    try {
        $vue3AdminApiKey = trim((string) ($this->options->v3a_ai_api_key ?? ''));
    } catch (\Throwable $e) {
        $vue3AdminApiKey = '';
    }

    if ($vue3AdminApiKey !== '') {
        $aiSource = 'vue3admin';
    }
}

$aiEnabled = $aiSource !== '';

$storeDir = __DIR__ . DIRECTORY_SEPARATOR . 'v3a_post';
$storeFile = $storeDir . DIRECTORY_SEPARATOR . 'page-' . (int) $this->cid . '.json';
$storeReady = v3aPostPrepareDir($storeDir);
$store = $storeReady ? v3aPostStoreLoad($storeFile, (int) $this->cid) : ['version' => 1, 'page_id' => (int) $this->cid, 'updated_at' => 0, 'submissions' => []];
$submissions = is_array($store['submissions'] ?? null) ? $store['submissions'] : [];

$noticeType = '';
$noticeMessage = '';

$formValues = [];
foreach ($submitSchemas as $schema) {
    $default = $schema['default'] ?? '';
    $formValues[(string) ($schema['name'] ?? '')] = is_array($default) ? $default : (string) $default;
}

$request = $this->request;
$security = \Helper::security();
$currentRequestUrl = (string) $request->getRequestUrl();
$noticeFlash = v3aPostNoticeDecode((string) ($request->get('v3a_post_notice', '') ?? ''));
$formAction = v3aPostUrlWithParams($currentRequestUrl, [], ['v3a_post_notice']);
$csrfRef = $formAction;
$csrfToken = (string) $security->getToken($csrfRef);

if (!empty($noticeFlash)) {
    $noticeType = (string) ($noticeFlash['type'] ?? 'success');
    $noticeMessage = (string) ($noticeFlash['message'] ?? '');
}

if (!$storeReady) {
    $noticeType = 'error';
    $noticeMessage = '投稿存储目录不可写，请检查 classic-22/v3a_post 目录权限。';
}

if ($storeReady && isset($_SERVER['REQUEST_METHOD']) && strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST') {
    $action = trim((string) ($_POST['v3a_action'] ?? 'submit'));
    $token = trim((string) ($_POST['_'] ?? ''));
    if ($token === '' || !hash_equals($csrfToken, $token)) {
        $noticeType = 'error';
        $noticeMessage = '请求已失效，请刷新后重试。';
    } elseif (trim((string) ($_POST['v3a_hp'] ?? '')) !== '') {
        v3aPostRedirectWithNotice($formAction, 'success', '提交成功，请等待审核。');
        $noticeType = 'success';
        $noticeMessage = '提交成功，请等待审核。';
    } elseif ($action === 'admin_export') {
        if (!$isAdmin) {
            $noticeType = 'error';
            $noticeMessage = '仅管理员可导出数据。';
        } else {
            $data = [
                'page_id' => (int) $this->cid,
                'exported_at' => date('c'),
                'schemas' => $schemas,
                'submissions' => $submissions,
            ];
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            if (is_string($json)) {
                $name = 'v3a_post_page_' . (int) $this->cid . '_' . date('Ymd_His') . '.json';
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=UTF-8');
                    header('Content-Disposition: attachment; filename="' . $name . '"');
                    header('Cache-Control: private, no-store, max-age=0');
                    header('Pragma: no-cache');
                }
                echo $json;
                exit;
            }
            $noticeType = 'error';
            $noticeMessage = '导出失败：JSON 编码异常。';
        }
    } else {
        $entryId = trim((string) ($_POST['entry_id'] ?? ''));
        $payload = isset($_POST['v3a_post_data']) && is_array($_POST['v3a_post_data']) ? $_POST['v3a_post_data'] : [];

        if ($action === 'admin_update') {
            if (!$isAdmin) {
                $noticeType = 'error';
                $noticeMessage = '仅管理员可编辑数据。';
            } else {
                $parsed = v3aPostCollect($schemas, $payload);
                $values = (array) ($parsed['values'] ?? []);

                if (!empty($parsed['errors'])) {
                    $noticeType = 'error';
                    $noticeMessage = (string) $parsed['errors'][0];
                } else {
                    $found = -1;
                    foreach ($submissions as $index => $item) {
                        if ((string) ($item['id'] ?? '') === $entryId) {
                            $found = (int) $index;
                            break;
                        }
                    }

                    if ($found < 0) {
                        $noticeType = 'error';
                        $noticeMessage = '未找到对应投稿记录。';
                    } else {
                        $submissions[$found]['values'] = $values;
                        $submissions[$found]['updated'] = time();
                        $store['updated_at'] = time();
                        $store['submissions'] = array_values($submissions);
                        if (v3aPostStoreSave($storeFile, $store)) {
                            v3aPostRedirectWithNotice($formAction, 'success', '投稿记录已更新。');
                            $noticeType = 'success';
                            $noticeMessage = '投稿记录已更新。';
                        } else {
                            $noticeType = 'error';
                            $noticeMessage = '写入失败，请检查目录权限。';
                        }
                    }
                }
            }
        } elseif ($action === 'admin_status') {
            if (!$isAdmin) {
                $noticeType = 'error';
                $noticeMessage = '仅管理员可修改审核状态。';
            } elseif ($entryId === '') {
                $noticeType = 'error';
                $noticeMessage = '缺少投稿记录 ID。';
            } else {
                $newStatus = trim((string) ($_POST['status'] ?? ''));
                $allowedStatuses = ['pending', 'approved', 'rejected'];
                if (!in_array($newStatus, $allowedStatuses, true)) {
                    $noticeType = 'error';
                    $noticeMessage = '审核状态无效。';
                } else {
                    $found = -1;
                    foreach ($submissions as $index => $item) {
                        if ((string) ($item['id'] ?? '') === $entryId) {
                            $found = (int) $index;
                            break;
                        }
                    }

                    if ($found < 0) {
                        $noticeType = 'error';
                        $noticeMessage = '未找到对应投稿记录。';
                    } else {
                        $submissions[$found]['status'] = $newStatus;
                        $submissions[$found]['updated'] = time();
                        $store['updated_at'] = time();
                        $store['submissions'] = array_values($submissions);
                        if (v3aPostStoreSave($storeFile, $store)) {
                            v3aPostRedirectWithNotice($formAction, 'success', '审核状态已更新。');
                            $noticeType = 'success';
                            $noticeMessage = '审核状态已更新。';
                        } else {
                            $noticeType = 'error';
                            $noticeMessage = '写入失败，请检查目录权限。';
                        }
                    }
                }
            }
        } else {
            if (empty($submitSchemas)) {
                $noticeType = 'error';
                $noticeMessage = '投稿表单未初始化，请更新模板文件。';
            } else {
                $parsed = v3aPostCollect($submitSchemas, $payload);
                $values = (array) ($parsed['values'] ?? []);
                $formValues = $values;

                if (!empty($parsed['errors'])) {
                    $noticeType = 'error';
                    $noticeMessage = (string) $parsed['errors'][0];
                } else {
                    $sourceUrl = trim((string) ($values['source_url'] ?? ''));
                    $validatedUrl = v3aPostValidateRemoteUrl($sourceUrl);
                    if (empty($validatedUrl['ok'])) {
                        $noticeType = 'error';
                        $noticeMessage = (string) ($validatedUrl['message'] ?? '链接无效。');
                    } else {
                        $sourceUrl = (string) ($validatedUrl['url'] ?? $sourceUrl);

                        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
                        $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
                        $salt = (string) ($this->options->siteUrl ?? __TYPECHO_ROOT_DIR__);
                        $finger = v3aPostFingerprint($ip, $ua, $salt);
                        $wait = v3aPostLimitWait($submissions, $finger, $limitSeconds);

                        if ($wait > 0) {
                            $noticeType = 'error';
                            $noticeMessage = '提交过于频繁，请 ' . $wait . ' 秒后再试。';
                        } else {
                            if ($recaptchaVerifyEnabled) {
                                $verify = v3aPostVerifyCaptcha($recaptchaKey, trim((string) ($_POST['recaptcha_token'] ?? '')), $ip);
                                if (empty($verify['ok'])) {
                                    $noticeType = 'error';
                                    $noticeMessage = (string) ($verify['message'] ?? '人机验证失败。');
                                }
                            }

                            if ($noticeType !== 'error') {
                                $fetch = v3aPostFetchHtml($sourceUrl, 3);
                                if (empty($fetch['ok'])) {
                                    $noticeType = 'error';
                                    $noticeMessage = (string) ($fetch['message'] ?? '网页解析失败，请稍后重试。');
                                } else {
                                    $finalUrl = (string) ($fetch['url'] ?? $sourceUrl);
                                    $pageInfo = v3aPostExtractPageInfo((string) ($fetch['body'] ?? ''));

                                    $generated = [
                                        'title' => '',
                                        'content' => '',
                                        'project_link' => '',
                                        'project_type' => '',
                                        'project_author' => '',
                                    ];

                                    if ($aiEnabled) {
                                        $aiResult = v3aPostAiGenerate($this, $finalUrl, $pageInfo);
                                        if (empty($aiResult['ok'])) {
                                            $noticeType = 'error';
                                            $noticeMessage = 'AI 生成失败：' . (string) ($aiResult['message'] ?? '');
                                        } else {
                                            $generated = array_merge($generated, (array) ($aiResult['data'] ?? []));
                                        }
                                    }

                                    if ($noticeType !== 'error') {
                                        $storeValues = [];
                                        foreach ($schemas as $schema) {
                                            $name = (string) ($schema['name'] ?? '');
                                            if ($name === '') {
                                                continue;
                                            }
                                            $default = $schema['default'] ?? '';
                                            $storeValues[$name] = is_array($default) ? $default : (string) $default;
                                        }

                                        $fallbackTitle = trim((string) ($pageInfo['title'] ?? ''));
                                        if ($fallbackTitle === '') {
                                            $fallbackTitle = (string) (parse_url($finalUrl, PHP_URL_HOST) ?? '投稿');
                                        }

                                        $projectTitle = trim((string) ($generated['title'] ?? ''));
                                        if ($projectTitle === '') {
                                            $projectTitle = $fallbackTitle;
                                        }

                                        $projectAuthor = trim((string) ($generated['project_author'] ?? ''));
                                        if ($projectAuthor === '') {
                                            $projectAuthor = trim((string) ($pageInfo['author'] ?? ''));
                                        }

                                        $projectType = trim((string) ($generated['project_type'] ?? ''));
                                        if ($projectType === '') {
                                            $projectType = v3aPostGuessProjectType($finalUrl, $pageInfo);
                                        }

                                        $projectLink = trim((string) ($generated['project_link'] ?? ''));
                                        if ($projectLink === '') {
                                            $projectLink = $finalUrl;
                                        } else {
                                            $linkValidated = v3aPostValidateRemoteUrl($projectLink);
                                            if (empty($linkValidated['ok'])) {
                                                $projectLink = $finalUrl;
                                            } else {
                                                $projectLink = (string) ($linkValidated['url'] ?? $projectLink);
                                            }
                                        }

                                        $content = trim((string) ($generated['content'] ?? ''));
                                        if ($content === '') {
                                            $excerpt = trim((string) ($pageInfo['description'] ?? ''));
                                            if ($excerpt === '' && !empty($pageInfo['text'])) {
                                                $excerpt = v3aPostSubstr((string) $pageInfo['text'], 200);
                                            }

                                            $content = '# ' . $projectTitle . "\n\n";
                                            if ($excerpt !== '') {
                                                $content .= $excerpt . "\n\n";
                                            }
                                            if ($projectAuthor !== '') {
                                                $content .= "- 作者：{$projectAuthor}\n";
                                            }
                                            if ($projectType !== '') {
                                                $content .= "- 类型：{$projectType}\n";
                                            }
                                            $content .= "- 项目链接：{$projectLink}\n";
                                            $content .= "- 来源链接：{$finalUrl}\n";
                                        }

                                        $storeValues['source_url'] = $finalUrl;
                                        $storeValues['title'] = $projectTitle;
                                        $storeValues['project_author'] = $projectAuthor;
                                        $storeValues['project_type'] = $projectType;
                                        $storeValues['project_link'] = $projectLink;
                                        $storeValues['content'] = $content;

                                        foreach ($schemas as $schema) {
                                            $name = (string) ($schema['name'] ?? '');
                                            if ($name === '' || !isset($storeValues[$name]) || is_array($storeValues[$name])) {
                                                continue;
                                            }
                                            $max = max(0, (int) ($schema['max_length'] ?? 0));
                                            if ($max > 0 && v3aPostLen((string) $storeValues[$name]) > $max) {
                                                $storeValues[$name] = v3aPostSubstr((string) $storeValues[$name], $max);
                                            }
                                        }

                                        try {
                                            $rand = bin2hex(random_bytes(4));
                                        } catch (\Throwable $e) {
                                            $rand = dechex(mt_rand(0, 0xfffffff));
                                        }

                                        $submissions[] = [
                                            'id' => date('YmdHis') . '-' . strtolower($rand),
                                            'created' => time(),
                                            'updated' => 0,
                                            'status' => 'pending',
                                            'fingerprint' => $finger,
                                            'values' => $storeValues,
                                        ];

                                        $store['updated_at'] = time();
                                        $store['submissions'] = array_values($submissions);
                                        if (v3aPostStoreSave($storeFile, $store)) {
                                            v3aPostRedirectWithNotice($formAction, 'success', '投稿已提交成功，感谢你的参与！');
                                            $noticeType = 'success';
                                            $noticeMessage = '投稿已提交成功，感谢你的参与！';
                                            foreach ($submitSchemas as $schema) {
                                                $default = $schema['default'] ?? '';
                                                $formValues[(string) $schema['name']] = is_array($default) ? $default : (string) $default;
                                            }
                                        } else {
                                            $noticeType = 'error';
                                            $noticeMessage = '投稿保存失败，请稍后重试。';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    $store = v3aPostStoreLoad($storeFile, (int) $this->cid);
    $submissions = is_array($store['submissions'] ?? null) ? $store['submissions'] : [];
}

usort($submissions, static function (array $a, array $b): int {
    return (int) ($b['created'] ?? 0) <=> (int) ($a['created'] ?? 0);
});

$statusCounts = [
    'all' => count($submissions),
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
];
foreach ($submissions as $item) {
    $status = (string) ($item['status'] ?? 'pending');
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}

$allowedStatusFilters = ['all', 'pending', 'approved', 'rejected'];
$statusFilter = trim((string) ($request->get('v3a_status') ?? ''));
if ($statusFilter === '' || !in_array($statusFilter, $allowedStatusFilters, true)) {
    $statusFilter = $isAdmin ? 'pending' : 'all';
}

$statusFilterLabels = [
    'all' => '全部',
    'pending' => '待审核',
    'approved' => '已通过',
    'rejected' => '已拒绝',
];
$statusFilterLabel = $statusFilterLabels[$statusFilter] ?? $statusFilter;

$displaySubmissions = $submissions;
if ($statusFilter !== 'all') {
    $displaySubmissions = array_values(array_filter($submissions, static function (array $item) use ($statusFilter): bool {
        return (string) ($item['status'] ?? 'pending') === $statusFilter;
    }));
}

if (!function_exists('v3aPostRenderField')) {
    function v3aPostRenderField(array $schema, $value, string $name, string $id, string $charset, bool $required = false): void
    {
        $type = (string) ($schema['type'] ?? 'input');
        if ($type === 'input') {
            ?>
            <input id="<?php echo v3aPostH($id, $charset); ?>" type="<?php echo v3aPostH((string) ($schema['input_type'] ?? 'text'), $charset); ?>" name="<?php echo v3aPostH($name, $charset); ?>" value="<?php echo v3aPostH((string) $value, $charset); ?>" <?php if (!empty($schema['placeholder'])): ?>placeholder="<?php echo v3aPostH((string) $schema['placeholder'], $charset); ?>"<?php endif; ?> <?php if ((int) ($schema['max_length'] ?? 0) > 0): ?>maxlength="<?php echo (int) $schema['max_length']; ?>"<?php endif; ?> <?php if ($required): ?>required<?php endif; ?>>
            <?php
            return;
        }
        if ($type === 'editor') {
            ?>
            <textarea id="<?php echo v3aPostH($id, $charset); ?>" name="<?php echo v3aPostH($name, $charset); ?>" rows="<?php echo (int) ($schema['rows'] ?? 6); ?>" <?php if (!empty($schema['placeholder'])): ?>placeholder="<?php echo v3aPostH((string) $schema['placeholder'], $charset); ?>"<?php endif; ?> <?php if ((int) ($schema['max_length'] ?? 0) > 0): ?>maxlength="<?php echo (int) $schema['max_length']; ?>"<?php endif; ?> <?php if ($required): ?>required<?php endif; ?>><?php echo v3aPostH((string) $value, $charset); ?></textarea>
            <?php
            return;
        }

        $options = (array) ($schema['options'] ?? []);
        if ($type === 'checkbox') {
            $checked = is_array($value) ? $value : [];
            ?>
            <div class="v3a-post-check-list" id="<?php echo v3aPostH($id, $charset); ?>">
                <?php foreach ($options as $option): ?>
                    <?php $ov = (string) ($option['value'] ?? ''); $ol = (string) ($option['label'] ?? $ov); ?>
                    <label><input type="checkbox" name="<?php echo v3aPostH($name . '[]', $charset); ?>" value="<?php echo v3aPostH($ov, $charset); ?>" <?php echo in_array($ov, $checked, true) ? 'checked' : ''; ?>><span><?php echo v3aPostH($ol, $charset); ?></span></label>
                <?php endforeach; ?>
            </div>
            <?php
            return;
        }
        if ($type === 'radio') {
            ?>
            <div class="v3a-post-check-list" id="<?php echo v3aPostH($id, $charset); ?>">
                <?php foreach ($options as $option): ?>
                    <?php $ov = (string) ($option['value'] ?? ''); $ol = (string) ($option['label'] ?? $ov); ?>
                    <label><input type="radio" name="<?php echo v3aPostH($name, $charset); ?>" value="<?php echo v3aPostH($ov, $charset); ?>" <?php echo ((string) $value === $ov) ? 'checked' : ''; ?>><span><?php echo v3aPostH($ol, $charset); ?></span></label>
                <?php endforeach; ?>
            </div>
            <?php
            return;
        }

        $selected = is_array($value) ? $value : [(string) $value];
        $multiple = !empty($schema['multiple']);
        ?>
        <select id="<?php echo v3aPostH($id, $charset); ?>" name="<?php echo v3aPostH($name . ($multiple ? '[]' : ''), $charset); ?>" <?php echo $multiple ? 'multiple' : ''; ?> <?php if ($required && !$multiple): ?>required<?php endif; ?>>
            <?php if (!$multiple): ?><option value="">请选择</option><?php endif; ?>
            <?php foreach ($options as $option): ?>
                <?php $ov = (string) ($option['value'] ?? ''); $ol = (string) ($option['label'] ?? $ov); ?>
                <option value="<?php echo v3aPostH($ov, $charset); ?>" <?php echo in_array($ov, $selected, true) ? 'selected' : ''; ?>><?php echo v3aPostH($ol, $charset); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
}

?>

<?php $this->need('header.php'); ?>

<main class="container">
    <div class="container-thin">
        <article class="post" itemscope itemtype="http://schema.org/Article">
            <?php postMeta($this, 'page'); ?>

            <div class="entry-content fmt" itemprop="articleBody">
                <?php $this->content(); ?>

                <section class="v3a-post-board" data-nosnippet>
                     <style>
                         .v3a-post-form-card,.v3a-post-list-card{border:1px solid var(--pico-muted-border-color);border-radius:var(--pico-border-radius);padding:1rem;margin:1rem 0;background:var(--pico-card-background-color)}
                         .v3a-post-form-card h3,.v3a-post-list-card h3{margin:0 0 .75rem}
                        .v3a-post-list-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-bottom:.5rem}
                        .v3a-post-list-head h3{margin:0}
                        .v3a-post-status-tabs{display:flex;align-items:center;justify-content:flex-end;gap:.5rem;flex-wrap:wrap;margin:0}
                        .v3a-post-status-tabs a[role="button"],.v3a-post-status-tabs button{padding:0 .55rem;font-size:.86rem;line-height:1.1;white-space:nowrap;flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;box-sizing:border-box;height:2rem;min-height:2rem;margin:0}
                        .v3a-post-status-tabs a[role="button"].is-active{background:var(--pico-primary-background);border-color:var(--pico-primary-border);color:var(--pico-primary-inverse)}
                        .v3a-post-status-tabs form{margin:0;flex:0 0 auto;display:inline-flex;align-items:center}
                        .v3a-post-notice{padding:.7rem .85rem;border:1px solid var(--pico-muted-border-color);border-radius:var(--pico-border-radius);margin-bottom:.85rem}
                        .v3a-post-notice.is-success{border-color:rgba(46,160,67,.45);color:#2ea043}
                        .v3a-post-notice.is-error{border-color:rgba(214,57,57,.45);color:#d63939}
                        .v3a-post-grid{display:grid;gap:.8rem}
                        .v3a-post-field > label{display:block;font-weight:600;margin-bottom:.35rem}
                        .v3a-post-field.v3a-post-field-inline{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap}
                        .v3a-post-field.v3a-post-field-inline > label{display:inline-flex;align-items:center;margin:0;white-space:nowrap;flex:0 0 auto}
                        .v3a-post-field.v3a-post-field-inline > .v3a-post-check-list{display:inline-flex;align-items:center;gap:.75rem;flex-wrap:nowrap;overflow-x:auto;overflow-y:hidden;padding-bottom:0;flex:1 1 auto}
                        .v3a-post-field.v3a-post-field-inline > .v3a-post-help{flex:1 0 100%;margin-top:0}
                        .v3a-post-required{color:#d63939;margin-left:.2rem}
                        .v3a-post-help{display:block;margin-top:.35rem;color:var(--pico-muted-color);font-size:.9rem}
                        .v3a-post-check-list{display:flex;align-items:center;gap:.75rem;flex-wrap:nowrap;overflow-x:auto;overflow-y:hidden;padding-bottom:.1rem}
                        .v3a-post-check-list label{display:inline-flex;align-items:center;gap:.4rem;margin:0;font-weight:400;white-space:nowrap;flex:0 0 auto}
                        .v3a-post-form-actions{margin-top:.85rem;display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}
                        .v3a-post-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
                        .v3a-post-config-tip{margin-top:.75rem;padding:.75rem;border:1px dashed var(--pico-muted-border-color);border-radius:var(--pico-border-radius);color:var(--pico-muted-color);font-size:.92rem;white-space:pre-wrap}
                        .v3a-post-list-item{border-top:1px solid var(--pico-muted-border-color);padding-top:.75rem;margin-top:.75rem}
                        .v3a-post-list-item:first-child{border-top:0;padding-top:0;margin-top:0}
                        .v3a-post-item-head{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;flex-wrap:wrap}
                        .v3a-post-item-meta{color:var(--pico-muted-color);font-size:.9rem;margin-bottom:.35rem}
                        .v3a-post-item-meta-row{display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex:1 1 auto;min-width:0}
                        .v3a-post-item-meta-row > div{flex:1 1 auto;min-width:0}
                        .v3a-post-item-controls{display:flex;align-items:center;justify-content:flex-end;gap:.5rem;flex-wrap:wrap}
                        .v3a-post-item-controls button,.v3a-post-item-controls a[role="button"],.v3a-post-item-controls select{padding:.25rem .45rem;font-size:.86rem;line-height:1.1}
                        .v3a-post-item-controls select{min-height:unset}
                        .v3a-post-status-badge{display:inline-flex;align-items:center;gap:.25rem;padding:.18rem .5rem;border:1px solid var(--pico-muted-border-color);border-radius:999px;font-size:.84rem;line-height:1;white-space:nowrap}
                        .v3a-post-status-badge.is-pending{border-color:rgba(217,119,6,.45);color:#d97706}
                        .v3a-post-status-badge.is-approved{border-color:rgba(46,160,67,.45);color:#2ea043}
                        .v3a-post-status-badge.is-rejected{border-color:rgba(214,57,57,.45);color:#d63939}
                        .v3a-post-preview-row{margin:.15rem 0;line-height:1.5}
                        .v3a-post-admin-actions{display:flex;align-items:center;justify-content:space-between;gap:.65rem;flex-wrap:wrap;margin-top:.65rem;max-width:100%}
                        .v3a-post-admin-actions-left{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
                        .v3a-post-admin-actions-right{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-left:auto}
                        .v3a-post-admin-actions button,.v3a-post-admin-actions a[role="button"]{padding:0 .55rem;font-size:.86rem;line-height:1.1;white-space:nowrap;flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;box-sizing:border-box;height:2rem;min-height:2rem;margin:0}
                        .v3a-post-admin-actions select{padding:0 .45rem;font-size:.86rem;line-height:1.1;white-space:nowrap;flex:0 0 auto;box-sizing:border-box;height:2rem;min-height:2rem;margin:0}
                        .v3a-post-admin-details{margin-top:.55rem;border:1px solid var(--pico-muted-border-color);border-radius:var(--pico-border-radius);padding:.55rem .7rem}
                        .v3a-post-admin-details summary{cursor:pointer;user-select:none;color:var(--pico-primary)}
                        .v3a-post-admin-grid{display:grid;gap:.65rem;margin-top:.65rem}
                        .v3a-post-inline-form{margin-top:.8rem;padding-top:.7rem;border-top:1px dashed var(--pico-muted-border-color)}
                        .v3a-post-admin-status-form{display:flex;align-items:center;justify-content:flex-end;gap:.45rem;flex-wrap:nowrap;margin:0;flex:0 0 auto;max-width:100%}
                        .v3a-post-admin-status-form select{margin:0;min-width:6rem;max-width:9rem;min-height:2rem}
                        .v3a-post-admin-status-form button{padding:0 .45rem;min-width:unset}
                        .v3a-post-dialog{border:1px solid var(--pico-muted-border-color);border-radius:var(--pico-border-radius);padding:0;max-width:min(860px,92vw);width:100%}
                        .v3a-post-dialog::backdrop{background:rgba(0,0,0,.55)}
                        .v3a-post-dialog-header{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.75rem 1rem;border-bottom:1px solid var(--pico-muted-border-color);margin:0}
                        .v3a-post-dialog-header button{padding:.25rem .55rem;font-size:.86rem;line-height:1.1;width:auto;flex:0 0 auto}
                        .v3a-post-dialog-content{padding:1rem;max-height:min(70vh,720px);overflow:auto;overflow-x:hidden;overflow-wrap:anywhere;word-break:break-word}
                        .v3a-post-dialog-content h4{margin-top:0}
                        .v3a-post-dialog-content :is(a,code){overflow-wrap:anywhere;word-break:break-word}
                        .v3a-post-admin-grid > div > div{overflow-wrap:anywhere;word-break:break-word}
                        .v3a-post-md :is(h1,h2,h3,h4){margin:.75rem 0 .45rem}
                        .v3a-post-md p{margin:.45rem 0}
                        .v3a-post-md ul,.v3a-post-md ol{padding-left:1.2rem;margin:.45rem 0}
                        .v3a-post-md blockquote{margin:.6rem 0;padding:.3rem .75rem;border-left:4px solid var(--pico-muted-border-color);color:var(--pico-muted-color)}
                        .v3a-post-md pre{margin:.6rem 0;padding:.7rem;border:1px solid var(--pico-muted-border-color);border-radius:var(--pico-border-radius);overflow:auto;white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word}
                        .v3a-post-md code{font-family:var(--pico-font-family-monospace,ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,\"Liberation Mono\",\"Courier New\",monospace)}
                        .grecaptcha-badge{z-index:99999 !important;visibility:visible !important;opacity:1 !important;display:block !important}
                     </style>

                    <?php if ($noticeMessage !== ''): ?>
                        <div class="v3a-post-notice <?php echo $noticeType === 'success' ? 'is-success' : 'is-error'; ?>" role="alert">
                            <?php echo v3aPostH($noticeMessage, $charset); ?>
                        </div>
                    <?php endif; ?>

                    <div class="v3a-post-form-card">
                        <h3>链接投稿</h3>

                        <form id="v3a-post-form" method="post" action="<?php echo v3aPostH($formAction, $charset); ?>" data-recaptcha-sitekey="<?php echo v3aPostH($recaptchaId, $charset); ?>">
                            <input type="hidden" name="v3a_action" value="submit">
                            <input type="hidden" name="_" value="<?php echo v3aPostH($csrfToken, $charset); ?>">
                            <input type="hidden" name="recaptcha_token" value="">
                            <div class="v3a-post-hp" aria-hidden="true"><label>请勿填写<input type="text" name="v3a_hp" autocomplete="off" tabindex="-1"></label></div>

                            <div class="v3a-post-grid">
                                <?php foreach ($submitSchemas as $schema): ?>
                                    <?php
                                    $fieldName = (string) ($schema['name'] ?? '');
                                    $fieldLabel = (string) ($schema['label'] ?? $fieldName);
                                    $fieldKey = (string) ($schema['key'] ?? '');
                                    $fieldType = (string) ($schema['type'] ?? 'input');
                                    $fieldValue = $formValues[$fieldName] ?? ($schema['default'] ?? '');
                                    $fieldClass = 'v3a-post-field';
                                    if (in_array($fieldType, ['checkbox', 'radio'], true)) {
                                        $fieldClass .= ' v3a-post-field-inline';
                                    }
                                    ?>
                                    <div class="<?php echo v3aPostH($fieldClass, $charset); ?>">
                                        <label for="<?php echo v3aPostH('v3a-post-' . $fieldKey, $charset); ?>"><?php echo v3aPostH($fieldLabel, $charset); ?><?php if (!empty($schema['required'])): ?><span class="v3a-post-required">*</span><?php endif; ?></label>
                                        <?php v3aPostRenderField($schema, $fieldValue, 'v3a_post_data[' . $fieldKey . ']', 'v3a-post-' . $fieldKey, $charset, !empty($schema['required'])); ?>
                                        <?php if (!empty($schema['description'])): ?><small class="v3a-post-help"><?php echo v3aPostH((string) $schema['description'], $charset); ?></small><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="v3a-post-form-actions">
                                <button type="submit">生成并提交</button>
                                <?php if ($limitSeconds > 0): ?><small class="v3a-post-help">提交频率限制：每 <?php echo (int) $limitSeconds; ?> 秒一次</small><?php endif; ?>
                            </div>
                            <?php if ($recaptchaSiteEnabled): ?>
                                <small class="v3a-post-help">已启用 reCAPTCHA v3 防刷验证<?php if (!$recaptchaVerifyEnabled): ?>（未配置 Secret Key，将不会进行服务端校验）<?php endif; ?>。</small>
                            <?php endif; ?>
                            <?php if ($aiEnabled): ?>
                                <small class="v3a-post-help">已启用 AI 生成内容<?php if ($aiSource === 'vue3admin'): ?>（使用 Vue3Admin 配置）<?php elseif ($aiSource === 'theme'): ?>（使用主题配置）<?php endif; ?>。</small>
                            <?php else: ?>
                                <small class="v3a-post-help">未配置 AI API Key，将仅使用基础解析生成内容。</small>
                            <?php endif; ?>
                        </form>
                    </div>

                     <div class="v3a-post-list-card" id="v3a-post-submission-list">
                         <div class="v3a-post-list-head">
                             <h3>投稿列表</h3>
                             <?php if (!empty($submissions)): ?>
                                 <div class="v3a-post-status-tabs" aria-label="投稿状态筛选">
                                     <?php
                                     $tabDefs = [
                                         'all' => ['label' => '全部', 'count' => (int) ($statusCounts['all'] ?? 0)],
                                         'pending' => ['label' => '待审核', 'count' => (int) ($statusCounts['pending'] ?? 0)],
                                         'approved' => ['label' => '已通过', 'count' => (int) ($statusCounts['approved'] ?? 0)],
                                         'rejected' => ['label' => '已拒绝', 'count' => (int) ($statusCounts['rejected'] ?? 0)],
                                     ];
                                     foreach ($tabDefs as $key => $tab) {
                                         $url = v3aPostUrlWithParams($formAction, ['v3a_status' => $key], ['v3a_post_notice']);
                                         $active = $statusFilter === $key ? ' is-active' : '';
                                         ?>
                                         <a href="<?php echo v3aPostH($url, $charset); ?>" role="button" class="secondary<?php echo $active; ?>"><?php echo v3aPostH((string) ($tab['label'] ?? $key), $charset); ?>（<?php echo (int) ($tab['count'] ?? 0); ?>）</a>
                                     <?php } ?>
                                     <?php if ($isAdmin && !empty($submissions)): ?>
                                         <form method="post" action="<?php echo v3aPostH($formAction, $charset); ?>">
                                             <input type="hidden" name="v3a_action" value="admin_export">
                                             <input type="hidden" name="_" value="<?php echo v3aPostH($csrfToken, $charset); ?>">
                                             <button type="submit" class="secondary">导出 JSON</button>
                                         </form>
                                     <?php endif; ?>
                                 </div>
                             <?php endif; ?>
                         </div>
 
                         <?php if (empty($displaySubmissions)): ?>
                             <p><?php echo $isAdmin ? '当前筛选下暂无投稿记录。' : '暂时没有投稿。'; ?></p>
                         <?php else: ?>
                             <?php foreach ($displaySubmissions as $entry): ?>
                                <?php
                                $entryId = (string) ($entry['id'] ?? '');
                                $entryValues = is_array($entry['values'] ?? null) ? $entry['values'] : [];
                                $created = (int) ($entry['created'] ?? 0);
                                $updated = (int) ($entry['updated'] ?? 0);
                                $status = (string) ($entry['status'] ?? 'pending');
                                $statusLabel = $statusFilterLabels[$status] ?? $status;
                                ?>
                                 <div class="v3a-post-list-item" data-entry-status="<?php echo v3aPostH($status, $charset); ?>">
                                     <div class="v3a-post-item-head">
                                         <div class="v3a-post-item-meta v3a-post-item-meta-row">
                                             <div>
                                                 提交时间：<?php echo $created > 0 ? v3aPostFormatLocalTime($created, 'Y-m-d H:i:s') : '-'; ?>
                                                 <?php if ($updated > 0): ?> ｜最后编辑：<?php echo v3aPostFormatLocalTime($updated, 'Y-m-d H:i:s'); ?><?php endif; ?>
                                             </div>
                                             <span class="v3a-post-status-badge is-<?php echo v3aPostH($status, $charset); ?>"><?php echo v3aPostH($statusLabel, $charset); ?></span>
                                         </div>
                                     </div>

                                    <?php foreach ($previewSchemas as $schema): ?>
                                        <?php
                                        $name = (string) ($schema['name'] ?? '');
                                        $label = (string) ($schema['label'] ?? $name);
                                        $value = $entryValues[$name] ?? '';
                                        $preview = v3aPostPreview($value, 48);
                                        ?>
                                        <div class="v3a-post-preview-row"><strong><?php echo v3aPostH($label, $charset); ?>：</strong><?php echo $preview !== '' ? v3aPostH($preview, $charset) : '（空）'; ?></div>
                                    <?php endforeach; ?>

                                    <?php if ($isAdmin): ?>
                                        <template id="v3a-post-view-tpl-<?php echo v3aPostH($entryId, $charset); ?>">
                                            <h4>投稿详情</h4>
                                            <div class="v3a-post-item-meta">
                                                投稿 ID：<?php echo v3aPostH($entryId, $charset); ?>
                                                ｜状态：<?php echo v3aPostH($statusLabel, $charset); ?>
                                                ｜提交时间：<?php echo $created > 0 ? v3aPostFormatLocalTime($created, 'Y-m-d H:i:s') : '-'; ?>
                                                <?php if ($updated > 0): ?>｜最后编辑：<?php echo v3aPostFormatLocalTime($updated, 'Y-m-d H:i:s'); ?><?php endif; ?>
                                            </div>
                                            <div class="v3a-post-admin-grid">
                                                <?php foreach ($schemas as $schema): ?>
                                                    <?php
                                                    $name = (string) ($schema['name'] ?? '');
                                                    $label = (string) ($schema['label'] ?? $name);
                                                    $value = $entryValues[$name] ?? '';
                                                    $fullText = is_array($value) ? implode('、', array_map('strval', $value)) : (string) $value;
                                                    ?>
                                                    <div>
                                                        <strong><?php echo v3aPostH($label, $charset); ?>：</strong>
                                                        <div>
                                                            <?php if ($name === 'content'): ?>
                                                                <div class="v3a-post-md"><?php echo v3aPostRenderMarkdown($fullText, $charset); ?></div>
                                                            <?php else: ?>
                                                                <?php echo nl2br(v3aPostH($fullText, $charset)); ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </template>

                                         <details class="v3a-post-admin-details">
                                             <summary>在线编辑</summary>
                                             <form method="post" action="<?php echo v3aPostH($formAction, $charset); ?>" class="v3a-post-inline-form" id="v3a-post-edit-<?php echo v3aPostH($entryId, $charset); ?>">
                                                <input type="hidden" name="v3a_action" value="admin_update">
                                                <input type="hidden" name="entry_id" value="<?php echo v3aPostH($entryId, $charset); ?>">
                                                <input type="hidden" name="_" value="<?php echo v3aPostH($csrfToken, $charset); ?>">

                                                <div class="v3a-post-admin-grid">
                                                    <?php foreach ($schemas as $schema): ?>
                                                        <?php
                                                        $fieldName = (string) ($schema['name'] ?? '');
                                                        $fieldLabel = (string) ($schema['label'] ?? $fieldName);
                                                        $fieldKey = (string) ($schema['key'] ?? '');
                                                        $fieldType = (string) ($schema['type'] ?? 'input');
                                                        $fieldValue = $entryValues[$fieldName] ?? ($schema['default'] ?? '');
                                                        $fieldClass = 'v3a-post-field';
                                                        if (in_array($fieldType, ['checkbox', 'radio'], true)) {
                                                            $fieldClass .= ' v3a-post-field-inline';
                                                        }
                                                        ?>
                                                        <div class="<?php echo v3aPostH($fieldClass, $charset); ?>">
                                                            <label for="<?php echo v3aPostH('v3a-edit-' . $entryId . '-' . $fieldKey, $charset); ?>"><?php echo v3aPostH($fieldLabel, $charset); ?></label>
                                                            <?php v3aPostRenderField($schema, $fieldValue, 'v3a_post_data[' . $fieldKey . ']', 'v3a-edit-' . $entryId . '-' . $fieldKey, $charset, false); ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                 <div class="v3a-post-form-actions"><button type="submit">保存修改</button></div>
                                             </form>
                                         </details>

                                        <div class="v3a-post-admin-actions" aria-label="管理员操作">
                                             <div class="v3a-post-admin-actions-left">
                                                 <button type="button" class="secondary" data-v3a-post-view data-entry-id="<?php echo v3aPostH($entryId, $charset); ?>">查看</button>
                                                 <button type="button" class="secondary" data-v3a-post-edit data-entry-id="<?php echo v3aPostH($entryId, $charset); ?>">编辑</button>
                                             </div>
                                             <div class="v3a-post-admin-actions-right">
                                                 <form method="post" action="<?php echo v3aPostH($formAction, $charset); ?>" class="v3a-post-admin-status-form" onsubmit="return confirm('确定要更新该投稿的审核状态吗？');">
                                                     <input type="hidden" name="v3a_action" value="admin_status">
                                                     <input type="hidden" name="entry_id" value="<?php echo v3aPostH($entryId, $charset); ?>">
                                                     <input type="hidden" name="_" value="<?php echo v3aPostH($csrfToken, $charset); ?>">
                                                     <select name="status" aria-label="审核状态">
                                                         <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待审核</option>
                                                         <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>通过</option>
                                                         <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>拒绝</option>
                                                     </select>
                                                     <button type="submit" class="contrast">更新</button>
                                                 </form>
                                             </div>
                                        </div>
                                     <?php endif; ?>
                                 </div>
                             <?php endforeach; ?>
                         <?php endif; ?>
                     </div>

                    <dialog id="v3a-post-view-dialog" class="v3a-post-dialog">
                        <form method="dialog" class="v3a-post-dialog-header">
                            <strong>投稿详情</strong>
                            <button type="submit" class="secondary">关闭</button>
                        </form>
                        <div id="v3a-post-view-body" class="v3a-post-dialog-content"></div>
                    </dialog>

                    <script>
                        (function () {
                            var dialog = document.getElementById('v3a-post-view-dialog');
                            var body = document.getElementById('v3a-post-view-body');

                            function openDialog(entryId) {
                                if (!dialog || !body || !entryId) return;
                                var tpl = document.getElementById('v3a-post-view-tpl-' + entryId);
                                if (!tpl || !tpl.content) return;
                                body.innerHTML = '';
                                body.appendChild(tpl.content.cloneNode(true));
                                if (typeof dialog.showModal === 'function') {
                                    dialog.showModal();
                                } else {
                                    alert('当前浏览器不支持弹窗显示。');
                                }
                            }

                            function openEdit(entryId) {
                                if (!entryId) return;
                                var form = document.getElementById('v3a-post-edit-' + entryId);
                                if (!form) return;
                                var details = form.closest('details');
                                if (details) {
                                    details.open = true;
                                    try {
                                        details.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                    } catch (e) {
                                        details.scrollIntoView();
                                    }
                                }
                                var first = form.querySelector('input,textarea,select');
                                if (first && typeof first.focus === 'function') {
                                    first.focus();
                                }
                            }

                            document.addEventListener('click', function (event) {
                                var viewBtn = event.target.closest('[data-v3a-post-view]');
                                if (viewBtn) {
                                    event.preventDefault();
                                    openDialog(viewBtn.getAttribute('data-entry-id') || '');
                                    return;
                                }

                                var editBtn = event.target.closest('[data-v3a-post-edit]');
                                if (editBtn) {
                                    event.preventDefault();
                                    openEdit(editBtn.getAttribute('data-entry-id') || '');
                                }
                            });

                            if (dialog) {
                                dialog.addEventListener('click', function (event) {
                                    if (event.target === dialog && typeof dialog.close === 'function') {
                                        dialog.close();
                                    }
                                });
                            }
                        })();
                    </script>
                </section>
            </div>
        </article>
    </div>
</main>

<?php if ($recaptchaSiteEnabled): ?>
    <script src="https://www.recaptcha.net/recaptcha/api.js?render=<?php echo rawurlencode($recaptchaId); ?>" async defer></script>
    <script>
        (function () {
            var form = document.getElementById('v3a-post-form');
            if (!form) return;
            var siteKey = form.getAttribute('data-recaptcha-sitekey') || '';
            if (!siteKey) return;

            form.addEventListener('submit', function (event) {
                var tokenInput = form.querySelector('input[name="recaptcha_token"]');
                if (!tokenInput || tokenInput.value) return;
                if (typeof grecaptcha === 'undefined') return;

                event.preventDefault();
                grecaptcha.ready(function () {
                    grecaptcha.execute(siteKey, { action: 'v3a_post_submit' }).then(function (token) {
                        tokenInput.value = token || '';
                        form.submit();
                    }).catch(function () {
                        form.submit();
                    });
                });
            });
        })();
    </script>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
