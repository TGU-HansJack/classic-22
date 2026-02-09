<?php
/**
 * 投稿页
 *
 * @package custom
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
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
        if (!is_object($archive) || !isset($archive->user) || !is_object($archive->user)) {
            return false;
        }
        if (!method_exists($archive->user, 'hasLogin') || !$archive->user->hasLogin()) {
            return false;
        }
        try {
            if (method_exists($archive->user, 'pass') && $archive->user->pass('administrator', true)) {
                return true;
            }
        } catch (\Throwable $e) {
        }
        return strtolower(trim((string) ($archive->user->group ?? ''))) === 'administrator';
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

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
    header('X-Content-Type-Options: nosniff', true);
}

$charset = (string) ($this->options->charset ?? 'UTF-8');
$isAdmin = v3aPostIsAdmin($this);
$rows = v3aPostLoadFields((int) $this->cid, $this->fields ?? null);
$config = v3aPostBuildSchema($rows);
$schemas = (array) ($config['schemas'] ?? []);
$limitSeconds = (int) ($config['limit'] ?? 0);
$recaptchaId = trim((string) ($config['recaptcha_id'] ?? ''));
$recaptchaKey = trim((string) ($config['recaptcha_key'] ?? ''));

$storeDir = __DIR__ . DIRECTORY_SEPARATOR . 'v3a_post';
$storeFile = $storeDir . DIRECTORY_SEPARATOR . 'page-' . (int) $this->cid . '.json';
$storeReady = v3aPostPrepareDir($storeDir);
$store = $storeReady ? v3aPostStoreLoad($storeFile, (int) $this->cid) : ['version' => 1, 'page_id' => (int) $this->cid, 'updated_at' => 0, 'submissions' => []];
$submissions = is_array($store['submissions'] ?? null) ? $store['submissions'] : [];

$noticeType = '';
$noticeMessage = '';

$formValues = [];
foreach ($schemas as $schema) {
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
        $isEdit = $action === 'admin_update';
        if ($isEdit && !$isAdmin) {
            $noticeType = 'error';
            $noticeMessage = '仅管理员可编辑数据。';
        } elseif (empty($schemas)) {
            $noticeType = 'error';
            $noticeMessage = '当前页面未配置投稿表单字段。';
        } else {
            $payload = isset($_POST['v3a_post_data']) && is_array($_POST['v3a_post_data']) ? $_POST['v3a_post_data'] : [];
            $parsed = v3aPostCollect($schemas, $payload);
            $values = (array) ($parsed['values'] ?? []);
            if (!$isEdit) {
                $formValues = $values;
            }

            if (!empty($parsed['errors'])) {
                $noticeType = 'error';
                $noticeMessage = (string) $parsed['errors'][0];
            } elseif ($isEdit) {
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
            } else {
                $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
                $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
                $salt = (string) ($this->options->siteUrl ?? __TYPECHO_ROOT_DIR__);
                $finger = v3aPostFingerprint($ip, $ua, $salt);
                $wait = v3aPostLimitWait($submissions, $finger, $limitSeconds);
                if ($wait > 0) {
                    $noticeType = 'error';
                    $noticeMessage = '提交过于频繁，请 ' . $wait . ' 秒后再试。';
                } else {
                    $verify = v3aPostVerifyCaptcha($recaptchaKey, trim((string) ($_POST['recaptcha_token'] ?? '')), $ip);
                    if (empty($verify['ok'])) {
                        $noticeType = 'error';
                        $noticeMessage = (string) ($verify['message'] ?? '人机验证失败。');
                    } else {
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
                            'values' => $values,
                        ];
                        $store['updated_at'] = time();
                        $store['submissions'] = array_values($submissions);
                        if (v3aPostStoreSave($storeFile, $store)) {
                            v3aPostRedirectWithNotice($formAction, 'success', '投稿已提交成功，感谢你的参与！');
                            $noticeType = 'success';
                            $noticeMessage = '投稿已提交成功，感谢你的参与！';
                            foreach ($schemas as $schema) {
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
    $store = v3aPostStoreLoad($storeFile, (int) $this->cid);
    $submissions = is_array($store['submissions'] ?? null) ? $store['submissions'] : [];
}

usort($submissions, static function (array $a, array $b): int {
    return (int) ($b['created'] ?? 0) <=> (int) ($a['created'] ?? 0);
});
$pending = array_values(array_filter($submissions, static function (array $item): bool {
    return (string) ($item['status'] ?? 'pending') === 'pending';
}));

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
                        .v3a-post-notice{padding:.7rem .85rem;border:1px solid var(--pico-muted-border-color);border-radius:var(--pico-border-radius);margin-bottom:.85rem}
                        .v3a-post-notice.is-success{border-color:rgba(46,160,67,.45);color:#2ea043}
                        .v3a-post-notice.is-error{border-color:rgba(214,57,57,.45);color:#d63939}
                        .v3a-post-grid{display:grid;gap:.8rem}
                        .v3a-post-field label{display:block;font-weight:600;margin-bottom:.35rem}
                        .v3a-post-required{color:#d63939;margin-left:.2rem}
                        .v3a-post-help{display:block;margin-top:.35rem;color:var(--pico-muted-color);font-size:.9rem}
                        .v3a-post-check-list{display:flex;align-items:center;gap:.75rem;flex-wrap:nowrap;overflow-x:auto;overflow-y:hidden;padding-bottom:.1rem}
                        .v3a-post-check-list label{display:inline-flex;align-items:center;gap:.4rem;margin:0;font-weight:400;white-space:nowrap;flex:0 0 auto}
                        .v3a-post-form-actions{margin-top:.85rem;display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}
                        .v3a-post-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
                        .v3a-post-config-tip{margin-top:.75rem;padding:.75rem;border:1px dashed var(--pico-muted-border-color);border-radius:var(--pico-border-radius);color:var(--pico-muted-color);font-size:.92rem;white-space:pre-wrap}
                        .v3a-post-list-item{border-top:1px solid var(--pico-muted-border-color);padding-top:.75rem;margin-top:.75rem}
                        .v3a-post-list-item:first-child{border-top:0;padding-top:0;margin-top:0}
                        .v3a-post-item-meta{color:var(--pico-muted-color);font-size:.9rem;margin-bottom:.35rem}
                        .v3a-post-preview-row{margin:.15rem 0;line-height:1.5}
                        .v3a-post-admin-details{margin-top:.55rem;border:1px solid var(--pico-muted-border-color);border-radius:var(--pico-border-radius);padding:.55rem .7rem}
                        .v3a-post-admin-details summary{cursor:pointer;user-select:none;color:var(--pico-primary)}
                        .v3a-post-admin-grid{display:grid;gap:.65rem;margin-top:.65rem}
                        .v3a-post-inline-form{margin-top:.8rem;padding-top:.7rem;border-top:1px dashed var(--pico-muted-border-color)}
                    </style>

                    <?php if ($noticeMessage !== ''): ?>
                        <div class="v3a-post-notice <?php echo $noticeType === 'success' ? 'is-success' : 'is-error'; ?>" role="alert">
                            <?php echo v3aPostH($noticeMessage, $charset); ?>
                        </div>
                    <?php endif; ?>

                    <div class="v3a-post-form-card">
                        <h3>投稿表单</h3>

                        <?php if (empty($schemas)): ?>
                            <p>当前页面尚未检测到可用表单字段。</p>
                            <?php if ($isAdmin): ?>
                                <div class="v3a-post-config-tip">在本页「自定义字段」中新增字段，字段名即表单名，字段类型选 JSON，值示例：
{
  "type": "input",
  "label": "昵称",
  "required": true,
  "placeholder": "请输入昵称"
}

支持 type：input / editor / checkbox / radio / select
可选字段：description、default、options、multiple、rows、inputType、maxLength、minLength、order
限频字段：limit（秒）
reCAPTCHA v3 字段：reCAPTCHA_v3_id、reCAPTCHA_v3_key</div>
                            <?php endif; ?>
                        <?php else: ?>
                            <form id="v3a-post-form" method="post" action="<?php echo v3aPostH($formAction, $charset); ?>" data-recaptcha-sitekey="<?php echo v3aPostH($recaptchaId, $charset); ?>">
                                <input type="hidden" name="v3a_action" value="submit">
                                <input type="hidden" name="_" value="<?php echo v3aPostH($csrfToken, $charset); ?>">
                                <input type="hidden" name="recaptcha_token" value="">
                                <div class="v3a-post-hp" aria-hidden="true"><label>请勿填写<input type="text" name="v3a_hp" autocomplete="off" tabindex="-1"></label></div>

                                <div class="v3a-post-grid">
                                    <?php foreach ($schemas as $schema): ?>
                                        <?php
                                        $fieldName = (string) ($schema['name'] ?? '');
                                        $fieldLabel = (string) ($schema['label'] ?? $fieldName);
                                        $fieldKey = (string) ($schema['key'] ?? '');
                                        $fieldValue = $formValues[$fieldName] ?? ($schema['default'] ?? '');
                                        ?>
                                        <div class="v3a-post-field">
                                            <label for="<?php echo v3aPostH('v3a-post-' . $fieldKey, $charset); ?>"><?php echo v3aPostH($fieldLabel, $charset); ?><?php if (!empty($schema['required'])): ?><span class="v3a-post-required">*</span><?php endif; ?></label>
                                            <?php v3aPostRenderField($schema, $fieldValue, 'v3a_post_data[' . $fieldKey . ']', 'v3a-post-' . $fieldKey, $charset, !empty($schema['required'])); ?>
                                            <?php if (!empty($schema['description'])): ?><small class="v3a-post-help"><?php echo v3aPostH((string) $schema['description'], $charset); ?></small><?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="v3a-post-form-actions">
                                    <button type="submit">提交投稿</button>
                                    <?php if ($limitSeconds > 0): ?><small class="v3a-post-help">提交频率限制：每 <?php echo (int) $limitSeconds; ?> 秒一次</small><?php endif; ?>
                                </div>
                                <?php if ($recaptchaId !== '' && $recaptchaKey !== ''): ?><small class="v3a-post-help">已启用 reCAPTCHA v3 防刷验证。</small><?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="v3a-post-list-card" id="v3a-post-pending-list">
                        <h3>提交等待列表（<?php echo (int) count($pending); ?>）</h3>

                        <?php if ($isAdmin && !empty($pending)): ?>
                            <form method="post" action="<?php echo v3aPostH($formAction, $charset); ?>" style="margin-bottom:.8rem;">
                                <input type="hidden" name="v3a_action" value="admin_export">
                                <input type="hidden" name="_" value="<?php echo v3aPostH($csrfToken, $charset); ?>">
                                <button type="submit" class="secondary">导出 JSON</button>
                            </form>
                        <?php endif; ?>

                        <?php if (empty($pending)): ?>
                            <p>暂时没有等待中的投稿。</p>
                        <?php else: ?>
                            <?php foreach ($pending as $entry): ?>
                                <?php
                                $entryId = (string) ($entry['id'] ?? '');
                                $entryValues = is_array($entry['values'] ?? null) ? $entry['values'] : [];
                                $created = (int) ($entry['created'] ?? 0);
                                $updated = (int) ($entry['updated'] ?? 0);
                                ?>
                                <div class="v3a-post-list-item">
                                    <div class="v3a-post-item-meta">
                                        提交时间：<?php echo $created > 0 ? date('Y-m-d H:i:s', $created) : '-'; ?>
                                        <?php if ($updated > 0): ?> ｜最后编辑：<?php echo date('Y-m-d H:i:s', $updated); ?><?php endif; ?>
                                    </div>

                                    <?php foreach ($schemas as $schema): ?>
                                        <?php
                                        $name = (string) ($schema['name'] ?? '');
                                        $label = (string) ($schema['label'] ?? $name);
                                        $value = $entryValues[$name] ?? '';
                                        $preview = v3aPostPreview($value, 48);
                                        ?>
                                        <div class="v3a-post-preview-row"><strong><?php echo v3aPostH($label, $charset); ?>：</strong><?php echo $preview !== '' ? v3aPostH($preview, $charset) : '（空）'; ?></div>
                                    <?php endforeach; ?>

                                    <?php if ($isAdmin): ?>
                                        <details class="v3a-post-admin-details">
                                            <summary>管理员展开查看完整内容 / 在线编辑</summary>
                                            <div class="v3a-post-admin-grid">
                                                <?php foreach ($schemas as $schema): ?>
                                                    <?php
                                                    $name = (string) ($schema['name'] ?? '');
                                                    $label = (string) ($schema['label'] ?? $name);
                                                    $value = $entryValues[$name] ?? '';
                                                    $fullText = is_array($value) ? implode('、', array_map('strval', $value)) : (string) $value;
                                                    ?>
                                                    <div><strong><?php echo v3aPostH($label, $charset); ?>：</strong><div><?php echo nl2br(v3aPostH($fullText, $charset)); ?></div></div>
                                                <?php endforeach; ?>
                                            </div>

                                            <form method="post" action="<?php echo v3aPostH($formAction, $charset); ?>" class="v3a-post-inline-form">
                                                <input type="hidden" name="v3a_action" value="admin_update">
                                                <input type="hidden" name="entry_id" value="<?php echo v3aPostH($entryId, $charset); ?>">
                                                <input type="hidden" name="_" value="<?php echo v3aPostH($csrfToken, $charset); ?>">

                                                <div class="v3a-post-admin-grid">
                                                    <?php foreach ($schemas as $schema): ?>
                                                        <?php
                                                        $fieldName = (string) ($schema['name'] ?? '');
                                                        $fieldLabel = (string) ($schema['label'] ?? $fieldName);
                                                        $fieldKey = (string) ($schema['key'] ?? '');
                                                        $fieldValue = $entryValues[$fieldName] ?? ($schema['default'] ?? '');
                                                        ?>
                                                        <div class="v3a-post-field">
                                                            <label for="<?php echo v3aPostH('v3a-edit-' . $entryId . '-' . $fieldKey, $charset); ?>"><?php echo v3aPostH($fieldLabel, $charset); ?></label>
                                                            <?php v3aPostRenderField($schema, $fieldValue, 'v3a_post_data[' . $fieldKey . ']', 'v3a-edit-' . $entryId . '-' . $fieldKey, $charset, false); ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <div class="v3a-post-form-actions"><button type="submit">保存修改</button></div>
                                            </form>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </article>
    </div>
</main>

<?php if ($recaptchaId !== '' && $recaptchaKey !== ''): ?>
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
