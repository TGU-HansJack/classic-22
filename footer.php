<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$liveWsEnabled = classic22LinuxDoGetOption($this->options, 'liveWsEnabled', '1') !== '0';
$liveWsEndpoint = classic22LinuxDoGetOption($this->options, 'liveWsEndpoint', '/ws');
$liveWsEndpoint = trim((string) $liveWsEndpoint);
if ($liveWsEndpoint === '') {
    $liveWsEndpoint = '/ws';
}
?>
<footer class="site-footer container-fluid">
    <div class="d-flex justify-content-between">
        <ul class="list-inline text-muted">
            <li>&copy; <?php echo date('Y'); ?> <a href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?></a></li>
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
                <div class="classic22-live-socket-tooltip" data-live-socket-tooltip hidden role="dialog" aria-label="WebSocket 说明">
                    <p>这是如何实现的？</p>
                    <p>当你打开这个页面时，会自动建立 WebSocket 连接，当成功连接后服务器会推送当前浏览页面的人数。</p>
                    <p>WebSocket 用于通知站点，站长在站点的实时活动，包括不限于文章的发布和更新。</p>
                    <p>当前 Socket 状态：<span data-live-socket-tooltip-state>连接中</span></p>
                </div>
            </li>
        </ul>
    </div>
</footer>

<script>
window.CLASSIC22_LIVE_WS = {
    enabled: <?php echo $liveWsEnabled ? 'true' : 'false'; ?>,
    endpoint: <?php echo json_encode($liveWsEndpoint, JSON_UNESCAPED_SLASHES); ?>,
    currentPath: <?php echo json_encode((string) (parse_url((string) $this->request->getRequestUri(), PHP_URL_PATH) ?? '/'), JSON_UNESCAPED_SLASHES); ?>
};
</script>

<script src="<?php $this->options->themeUrl('static/js/home-announcements.js'); ?>" defer></script>
<script src="<?php $this->options->themeUrl('static/js/post-toc.js'); ?>" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/highlight.min.js" defer></script>
<script src="<?php $this->options->themeUrl('static/js/content-enhance.js'); ?>" defer></script>
<script src="<?php $this->options->themeUrl('static/js/live-socket.js'); ?>" defer></script>

<?php $this->footer(); ?>

</body>
</html>
