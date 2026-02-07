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
?>

<main class="container-fluid">
    <div class="container-thin container-wide">
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

        <div class="post-cards">
            <?php while ($this->next()): ?>
                <?php $cover = postCoverUrl($this); ?>

                <?php
                $postPermalink = (string) $this->permalink;
                $postPath = (string) (parse_url($postPermalink, PHP_URL_PATH) ?? '');
                ?>

                <article class="post post-card" itemscope itemtype="http://schema.org/BlogPosting">
                    <div class="classic22-live-online-badge" data-live-online-card data-page-path="<?php echo htmlspecialchars($postPath, ENT_QUOTES, $this->options->charset); ?>" data-online-count="0" aria-label="在线人数">
                        <span class="classic22-live-online-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-activity-icon lucide-activity"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>
                        </span>
                        <span class="classic22-live-online-number" data-live-online-number>0</span>
                    </div>

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
                        <?php postMeta($this); ?>

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

</main>

<?php $this->need('footer.php'); ?>
