<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<?php
$classic22PostAiEnabled = function_exists('classic22AiEnabled') && classic22AiEnabled($this->options);
$classic22PostAiModels = $classic22PostAiEnabled && function_exists('classic22AiGetModels') ? classic22AiGetModels($this->options) : [];
$classic22PostAiDefaultModel = $classic22PostAiEnabled && function_exists('classic22AiDefaultModel') ? classic22AiDefaultModel($this->options) : '';
$classic22PostAiArticles = [];

if ($classic22PostAiEnabled) {
    $classic22PostAiArticles[] = [
        'id' => (int) ($this->cid ?? 0),
        'title' => trim((string) ($this->title ?? '')),
        'permalink' => trim((string) ($this->permalink ?? '')),
        'date' => trim((string) ($this->date ? $this->date->format('Y-m-d') : '')),
        'excerpt' => trim((string) (function_exists('postExcerptText') ? postExcerptText($this, 220, '...') : '')),
    ];
}

$classic22PostAiBaseUrl = $classic22PostAiEnabled && function_exists('classic22LinuxDoSiteBaseUrl')
    ? classic22LinuxDoSiteBaseUrl($this->options)
    : '';
$classic22PostAiChatUrl = $classic22PostAiEnabled
    ? $classic22PostAiBaseUrl . '?classic22_ai=chat'
    : '';
$classic22PostAiArticlesApiUrl = $classic22PostAiEnabled
    ? $classic22PostAiBaseUrl . '?classic22_ai=articles'
    : '';
?>

<main class="container classic22-post-container">
    <div class="classic22-post-layout">
        <div class="container-thin classic22-post-main">
            <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
                <?php postMeta($this, 'post'); ?>

                <div class="entry-content fmt" itemprop="articleBody" data-post-content>
                    <div data-post-content-body>
                        <?php $this->content(); ?>
                    </div>
                    <p itemprop="keywords"><?php _e('标签'); ?>：<?php $this->tags(', ', true, _t('无')); ?></p>
                </div>
            </article>

            <nav class="post-nav">
                <ul class="page-navigator">
                    <li class="prev"><?php $this->thePrev('%s', _t('没有了')); ?></li>
                    <li class="next"><?php $this->theNext('%s', _t('没有了')); ?></li>
                </ul>
            </nav>

            <?php $this->need('comments.php'); ?>
        </div>

        <aside class="classic22-post-toc" data-post-toc>
            <div class="classic22-post-toc-inner">
                <h6 class="classic22-post-toc-title"><?php _e('目录'); ?></h6>
                <nav class="classic22-post-toc-nav" aria-label="<?php _e('文章目录'); ?>">
                    <ul class="classic22-post-toc-list" data-post-toc-list></ul>
                </nav>
            </div>
        </aside>
    </div>
</main>

<?php if ($classic22PostAiEnabled): ?>
    <button type="button" class="classic22-post-ai-fab" data-post-ai-toggle aria-label="打开 AI 对话" title="打开 AI 对话">
        <span class="classic22-home-ai-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bot-icon lucide-bot"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
        </span>
    </button>

    <section class="classic22-post-ai-drawer" data-post-ai-drawer hidden>
        <div class="classic22-post-ai-drawer-inner">
            <section class="classic22-home-ai-wrap classic22-home-ai-wrap--post" data-home-ai-root>
                <div class="classic22-home-ai-chat-top" data-home-ai-chat-top hidden></div>
                <div class="classic22-home-ai-chat-list" data-home-ai-messages hidden></div>

                <div class="classic22-home-ai-panel" data-home-ai-panel>
                    <button type="button" class="classic22-post-ai-close" data-post-ai-close aria-label="关闭 AI 对话" title="关闭 AI 对话">
                        <span class="classic22-home-ai-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </span>
                    </button>

                    <form class="classic22-home-ai-form" data-home-ai-form>
                        <label class="classic22-home-ai-input-wrap">
                            <textarea data-home-ai-input rows="3" placeholder="输入问题，开始对话" aria-label="AI 问题输入"></textarea>
                        </label>

                        <div class="classic22-home-ai-actions">
                            <div class="classic22-home-ai-actions-left">
                                <label class="classic22-home-ai-select" title="选择文章">
                                    <span class="classic22-home-ai-icon" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text-icon lucide-file-text"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                                    </span>
                                    <select data-home-ai-article aria-label="选择文章"></select>
                                    <span class="classic22-home-ai-caret" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="classic22-home-ai-caret-right"><path d="m9 18 6-6-6-6"/></svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="classic22-home-ai-caret-down"><path d="m6 9 6 6 6-6"/></svg>
                                    </span>
                                </label>

                                <label class="classic22-home-ai-select" title="选择模型">
                                    <span class="classic22-home-ai-icon" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-brain-icon lucide-brain"><path d="M12 18V5"/><path d="M15 13a4.17 4.17 0 0 1-3-4 4.17 4.17 0 0 1-3 4"/><path d="M17.598 6.5A3 3 0 1 0 12 5a3 3 0 1 0-5.598 1.5"/><path d="M17.997 5.125a4 4 0 0 1 2.526 5.77"/><path d="M18 18a4 4 0 0 0 2-7.464"/><path d="M19.967 17.483A4 4 0 1 1 12 18a4 4 0 1 1-7.967-.517"/><path d="M6 18a4 4 0 0 1-2-7.464"/><path d="M6.003 5.125a4 4 0 0 0-2.526 5.77"/></svg>
                                    </span>
                                    <select data-home-ai-model aria-label="选择模型"></select>
                                    <span class="classic22-home-ai-caret" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="classic22-home-ai-caret-right"><path d="m9 18 6-6-6-6"/></svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="classic22-home-ai-caret-down"><path d="m6 9 6 6 6-6"/></svg>
                                    </span>
                                </label>
                            </div>

                            <div class="classic22-home-ai-actions-right">
                                <button type="submit" class="classic22-home-ai-send" data-home-ai-send>
                                    <span class="classic22-home-ai-icon" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-send-horizontal-icon lucide-send-horizontal"><path d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z"/><path d="M6 12h16"/></svg>
                                    </span>
                                    <span><?php _e('发送'); ?></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <script type="application/json" data-home-ai-bootstrap><?php echo json_encode([
                    'chatUrl' => $classic22PostAiChatUrl,
                    'articlesApiUrl' => $classic22PostAiArticlesApiUrl,
                    'models' => $classic22PostAiModels,
                    'defaultModel' => $classic22PostAiDefaultModel,
                    'currentArticleId' => (int) ($this->cid ?? 0),
                    'articles' => $classic22PostAiArticles,
                    'labels' => [
                        'allArticles' => '全部文章',
                        'thinking' => '正在思考中...',
                        'errorPrefix' => '请求失败：',
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
            </section>
        </div>
    </section>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
