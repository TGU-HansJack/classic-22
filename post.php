<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<main class="container">
    <div class="classic22-post-layout">
        <div class="container-thin classic22-post-main">
            <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
                <?php postMeta($this, 'post'); ?>

                <div class="entry-content fmt" itemprop="articleBody" data-post-content>
                    <?php $this->content(); ?>
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

<?php $this->need('footer.php'); ?>
