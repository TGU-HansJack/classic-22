<?php
/**
 * Just another official theme
 *
 * @package Classic 22
 * @author Typecho Team
 * @version 1.0
 * @link http://typecho.org
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');

$classic22AiEnabled = $this->is('index') && function_exists('classic22AiEnabled') && classic22AiEnabled($this->options);
$classic22AiModels = $classic22AiEnabled && function_exists('classic22AiGetModels') ? classic22AiGetModels($this->options) : [];
$classic22AiDefaultModel = $classic22AiEnabled && function_exists('classic22AiDefaultModel') ? classic22AiDefaultModel($this->options) : '';
$classic22AiArticles = $classic22AiEnabled && function_exists('classic22AiBuildArticleListPayload') ? classic22AiBuildArticleListPayload($this, 120) : [];
$classic22AiBaseUrl = $classic22AiEnabled && function_exists('classic22LinuxDoSiteBaseUrl')
    ? classic22LinuxDoSiteBaseUrl($this->options)
    : '';
$classic22AiChatUrl = $classic22AiEnabled
    ? $classic22AiBaseUrl . '?classic22_ai=chat'
    : '';
$classic22AiArticlesApiUrl = $classic22AiEnabled
    ? $classic22AiBaseUrl . '?classic22_ai=articles'
    : '';
$classic22TimelineData = function_exists('classic22TimelineHomeData') ? classic22TimelineHomeData($this) : [
    'generatedAt' => 0,
    'updatedAt' => '',
    'timeline' => [],
    'rankings' => ['views' => [], 'comments' => []],
];
$classic22TimelineItems = is_array($classic22TimelineData['timeline'] ?? null) ? $classic22TimelineData['timeline'] : [];
$classic22TimelineItems = array_slice($classic22TimelineItems, 0, 5);
$classic22TimelineRankings = is_array($classic22TimelineData['rankings'] ?? null) ? $classic22TimelineData['rankings'] : ['views' => [], 'comments' => []];
$classic22TimelineViewRanks = is_array($classic22TimelineRankings['views'] ?? null) ? $classic22TimelineRankings['views'] : [];
$classic22TimelineCommentRanks = is_array($classic22TimelineRankings['comments'] ?? null) ? $classic22TimelineRankings['comments'] : [];
$classic22TimelineUpdatedAt = trim((string) ($classic22TimelineData['updatedAt'] ?? ''));
?>

<main class="container-fluid">
    <div class="container-thin container-wide classic22-home-layout" data-home-layout>
        <?php if (!($this->is('index')) && !($this->is('post'))): ?>
            <h6 class="text-center text-muted">
                <?php $this->archiveTitle([
                    'category' => _t('分类 %s 下的文章'),
                    'search'   => _t('包含关键字 %s 的文章'),
                    'tag'      => _t('标签 %s 下的文章'),
                    'author'   => _t('%s 发布的文章')
                ], '', ''); ?>
            </h6>
        <?php endif; ?>

        <?php if ($classic22AiEnabled): ?>
            <section class="classic22-home-ai-wrap" data-home-ai-root>
                <div class="classic22-home-ai-chat-top" data-home-ai-chat-top hidden>
                    <button type="button" class="classic22-home-ai-back" data-home-ai-back>
                        <span class="classic22-home-ai-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-undo2-icon lucide-undo-2"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5a5.5 5.5 0 0 1-5.5 5.5H11"/></svg>
                        </span>
                        <span><?php _e('返回首页'); ?></span>
                    </button>
                </div>

                <div class="classic22-home-ai-chat-list" data-home-ai-messages hidden></div>

                <div class="classic22-home-ai-panel" data-home-ai-panel>
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
                    'chatUrl' => $classic22AiChatUrl,
                    'articlesApiUrl' => $classic22AiArticlesApiUrl,
                    'models' => $classic22AiModels,
                    'defaultModel' => $classic22AiDefaultModel,
                    'provider' => (string) (function_exists('classic22LinuxDoGetOption') ? classic22LinuxDoGetOption($this->options, 'aiProvider', 'openai') : 'openai'),
                    'apiMode' => (string) (function_exists('classic22LinuxDoGetOption') ? classic22LinuxDoGetOption($this->options, 'aiApiMode', 'chat_completions') : 'chat_completions'),
                    'articles' => $classic22AiArticles,
                    'labels' => [
                        'allArticles' => '全部文章',
                        'thinking' => '正在思考中...',
                        'errorPrefix' => '请求失败：',
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
            </section>
        <?php endif; ?>

        <aside class="classic22-home-sidebar" aria-label="首页侧边栏">
            <section class="classic22-home-sidebar-card classic22-home-timeline-card" aria-label="站点动态时间线">
                <h3 class="classic22-home-sidebar-title">站点动态时间线</h3>
                <?php if ($classic22TimelineUpdatedAt !== ''): ?>
                    <p class="classic22-home-sidebar-meta">更新于 <?php echo htmlspecialchars($classic22TimelineUpdatedAt, ENT_QUOTES, $this->options->charset); ?></p>
                <?php endif; ?>

                <ol class="classic22-home-timeline-list">
                    <?php if (!empty($classic22TimelineItems)): ?>
                        <?php foreach ($classic22TimelineItems as $item): ?>
                            <?php
                                $timelineTitle = trim((string) ($item['title'] ?? '动态更新'));
                                $timelineSummary = trim((string) ($item['summary'] ?? ''));
                                $timelineRelative = trim((string) ($item['relativeTime'] ?? ''));
                                $timelineLink = trim((string) ($item['link'] ?? ''));
                                $timelineBody = $timelineSummary !== '' ? $timelineSummary : $timelineTitle;
                                if ($timelineBody !== '') {
                                    $timelineBody = (string) preg_replace('/(?:，\s*)?点击可直达原文[。.!！]?/u', '', $timelineBody);
                                    $timelineBody = trim($timelineBody);
                                    if (substr($timelineBody, -3) !== '...') {
                                        $timelineBody = (string) preg_replace('/[，。.!！]+$/u', '', $timelineBody);
                                    }
                                    $timelineBody = trim($timelineBody);
                                }
                            ?>
                            <li class="classic22-home-timeline-item">
                                <div class="classic22-home-timeline-dot" aria-hidden="true"></div>
                                <div class="classic22-home-timeline-content">
                                    <?php if ($timelineRelative !== ''): ?>
                                        <p class="classic22-home-timeline-time"><?php echo htmlspecialchars($timelineRelative, ENT_QUOTES, $this->options->charset); ?></p>
                                    <?php endif; ?>

                                    <?php if ($timelineBody !== ''): ?>
                                        <?php if ($timelineLink !== ''): ?>
                                            <a class="classic22-home-timeline-summary" href="<?php echo htmlspecialchars($timelineLink, ENT_QUOTES, $this->options->charset); ?>"><?php echo classic22RenderInlineMarkdown($timelineBody, (string) $this->options->charset); ?></a>
                                        <?php else: ?>
                                            <p class="classic22-home-timeline-summary"><?php echo classic22RenderInlineMarkdown($timelineBody, (string) $this->options->charset); ?></p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="classic22-home-timeline-item is-empty">
                            <div class="classic22-home-timeline-dot" aria-hidden="true"></div>
                            <div class="classic22-home-timeline-content">
                                <p class="classic22-home-timeline-time">暂无数据</p>
                                <p class="classic22-home-timeline-summary">时间线将在有新文章、评论或榜单变动时自动更新。</p>
                            </div>
                        </li>
                    <?php endif; ?>
                </ol>
            </section>

            <section class="classic22-home-sidebar-card classic22-home-rank-card" aria-label="浏览量排行榜">
                <h3 class="classic22-home-sidebar-title">浏览量排行榜</h3>
                <ol class="classic22-home-rank-list">
                    <?php if (!empty($classic22TimelineViewRanks)): ?>
                        <?php foreach ($classic22TimelineViewRanks as $index => $item): ?>
                            <?php
                                $rankTitle = trim((string) ($item['title'] ?? '未命名文章'));
                                $rankCount = (int) ($item['count'] ?? 0);
                                $rankLink = trim((string) ($item['permalink'] ?? ''));
                            ?>
                            <li class="classic22-home-rank-item">
                                <span class="classic22-home-rank-no"><?php echo (int) ($index + 1); ?></span>
                                <?php if ($rankLink !== ''): ?>
                                    <a class="classic22-home-rank-title" href="<?php echo htmlspecialchars($rankLink, ENT_QUOTES, $this->options->charset); ?>"><?php echo htmlspecialchars($rankTitle, ENT_QUOTES, $this->options->charset); ?></a>
                                <?php else: ?>
                                    <span class="classic22-home-rank-title"><?php echo htmlspecialchars($rankTitle, ENT_QUOTES, $this->options->charset); ?></span>
                                <?php endif; ?>
                                <span class="classic22-home-rank-count"><?php echo number_format($rankCount); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="classic22-home-rank-item is-empty"><span class="classic22-home-rank-title">暂无数据</span></li>
                    <?php endif; ?>
                </ol>
            </section>

            <section class="classic22-home-sidebar-card classic22-home-rank-card" aria-label="评论排行榜">
                <h3 class="classic22-home-sidebar-title">评论排行榜</h3>
                <ol class="classic22-home-rank-list">
                    <?php if (!empty($classic22TimelineCommentRanks)): ?>
                        <?php foreach ($classic22TimelineCommentRanks as $index => $item): ?>
                            <?php
                                $rankTitle = trim((string) ($item['title'] ?? '未命名文章'));
                                $rankCount = (int) ($item['count'] ?? 0);
                                $rankLink = trim((string) ($item['permalink'] ?? ''));
                            ?>
                            <li class="classic22-home-rank-item">
                                <span class="classic22-home-rank-no"><?php echo (int) ($index + 1); ?></span>
                                <?php if ($rankLink !== ''): ?>
                                    <a class="classic22-home-rank-title" href="<?php echo htmlspecialchars($rankLink, ENT_QUOTES, $this->options->charset); ?>"><?php echo htmlspecialchars($rankTitle, ENT_QUOTES, $this->options->charset); ?></a>
                                <?php else: ?>
                                    <span class="classic22-home-rank-title"><?php echo htmlspecialchars($rankTitle, ENT_QUOTES, $this->options->charset); ?></span>
                                <?php endif; ?>
                                <span class="classic22-home-rank-count"><?php echo number_format($rankCount); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="classic22-home-rank-item is-empty"><span class="classic22-home-rank-title">暂无数据</span></li>
                    <?php endif; ?>
                </ol>
            </section>
        </aside>

        <section class="classic22-home-post-list" data-home-post-list>
            <div class="classic22-home-main-grid">
                <div class="classic22-home-main-left">
                    <div class="post-cards">
                        <?php while ($this->next()): ?>
                            <?php $cover = postCoverUrl($this); ?>

                            <article class="post post-card" itemscope itemtype="http://schema.org/BlogPosting">
                                <?php if ($cover): ?>
                                    <a class="post-card-cover" href="<?php $this->permalink(); ?>">
                                        <img
                                            src="<?php echo htmlspecialchars($cover, ENT_QUOTES, $this->options->charset); ?>"
                                            alt="<?php $this->title(); ?>"
                                            loading="lazy"
                                            itemprop="image"
                                        >
                                    </a>
                                <?php endif; ?>

                                <div class="post-card-inner">
                                    <?php postMeta($this, 'card'); ?>

                                    <div class="entry-content fmt">
                                        <p class="post-card-excerpt text-muted" itemprop="description">
                                            <?php echo htmlspecialchars(postExcerptText($this, 160), ENT_QUOTES, $this->options->charset); ?>
                                        </p>
                                        <p class="more">
                                            <a href="<?php $this->permalink(); ?>" title="<?php $this->title(); ?>">
                                                <?php _e('阅读全文'); ?>
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>

                    <nav><?php $this->pageNav(_t('前一页'), _t('后一页'), 2, '...', array('wrapTag' => 'ul', 'itemTag' => 'li')); ?></nav>
                </div>

            </div>
        </section>
    </div>

</main>

<?php $this->need('footer.php'); ?>
