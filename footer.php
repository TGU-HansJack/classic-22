<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$liveWsEnabled = classic22LinuxDoGetOption($this->options, 'liveWsEnabled', '1') !== '0';
$liveWsEndpoint = classic22LinuxDoGetOption($this->options, 'liveWsEndpoint', '/ws');
$liveWsEndpoint = trim((string) $liveWsEndpoint);
if ($liveWsEndpoint === '') {
    $liveWsEndpoint = '/ws';
}
$echartsCdn = '';
try {
    $pluginOptions = $this->options->plugin('Vue3Admin');
    $echartsCdn = trim((string) ($pluginOptions->echartsCdn ?? ''));
} catch (\Throwable $e) {
    $echartsCdn = '';
}
if ($echartsCdn === '') {
    $echartsCdn = 'https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js';
}
?>
<footer class="site-footer container-fluid">
    <div class="d-flex justify-content-between">
        <ul class="list-inline text-muted">
            <li>&copy; <?php echo (new \Typecho\Date())->format('Y'); ?> <a href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?></a></li>
            <li><a href="<?php $this->options->feedUrl(); ?>"><?php _e('RSS'); ?></a></li>
        </ul>
        <ul class="list-inline text-muted">
            <li>
                <?php _e('Powered by <a href="https://typecho.org">Typecho</a>'); ?>
            </li>
            <li class="classic22-live-socket-wrap">
                <button type="button" class="classic22-live-socket-status" data-live-socket-status aria-label="实时连接状态" title="点击查看说明" aria-haspopup="dialog" aria-expanded="false">
                    <span class="classic22-live-socket-dot" aria-hidden="true"></span>
                    <span data-live-socket-state-text>连接中</span>
                </button>
                <div class="classic22-live-socket-tooltip" data-live-socket-tooltip hidden role="dialog" aria-label="WebSocket 连接说明">
                    <p class="classic22-live-socket-tip-title">实时在线说明</p>
                    <p>页面打开后会自动建立 WebSocket 连接，并同步当前页面在线人数。</p>
                    <p>用于展示站点实时活动（如文章发布、更新）。</p>
                    <p>当前状态：<span class="classic22-live-socket-state" data-live-socket-tooltip-state>连接中</span></p>
                </div>
            </li>
        </ul>
    </div>
</footer>

<?php if ($this->is('post') || $this->is('index')): ?>
<button type="button" class="classic22-back-to-top" data-back-to-top hidden aria-label="返回顶部" title="返回顶部">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-to-line-icon lucide-arrow-up-to-line" aria-hidden="true"><path d="M5 3h14"/><path d="m18 13-6-6-6 6"/><path d="M12 7v14"/></svg>
</button>
<?php endif; ?>

<script>
window.CLASSIC22_LIVE_WS = {
    enabled: <?php echo $liveWsEnabled ? 'true' : 'false'; ?>,
    endpoint: <?php echo json_encode($liveWsEndpoint, JSON_UNESCAPED_SLASHES); ?>,
    currentPath: <?php echo json_encode((string) (parse_url((string) $this->request->getRequestUri(), PHP_URL_PATH) ?? '/'), JSON_UNESCAPED_SLASHES); ?>
};
</script>

<script src="<?php $this->options->themeUrl('static/js/home-announcements.js'); ?>" defer></script>
<script src="<?php $this->options->themeUrl('static/js/post-toc.js'); ?>" defer></script>
<script src="<?php $this->options->themeUrl('static/js/post-lang-switch.js'); ?>" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/highlight.min.js" defer></script>
<?php if ($this->is('post') || $this->is('page')): ?>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.umd.min.js" defer></script>
<?php endif; ?>
<script src="<?php echo htmlspecialchars($echartsCdn, ENT_QUOTES); ?>" defer></script>
<script src="<?php $this->options->themeUrl('static/js/home-traffic.js'); ?>" defer></script>
<script src="<?php $this->options->themeUrl('static/js/content-enhance.js'); ?>" defer></script>
<script src="<?php $this->options->themeUrl('static/js/live-socket.js'); ?>" defer></script>
<script src="<?php $this->options->themeUrl('static/js/home-ai-chat.js'); ?>" defer></script>
<?php if ($this->is('post') || $this->is('index')): ?>
<script src="<?php $this->options->themeUrl('static/js/back-to-top.js'); ?>" defer></script>
<?php endif; ?>

<?php $this->footer(); ?>

</body>
</html>
