<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$classic22LinuxDoFlashError = '';
if (function_exists('classic22LinuxDoHandleRequest')) {
    classic22LinuxDoHandleRequest($this);
}
if (function_exists('classic22LinuxDoConsumeError')) {
    $classic22LinuxDoFlashError = classic22LinuxDoConsumeError();
}
?>
<!DOCTYPE html>
<html lang="zh-Hans"<?php if ($this->options->colorSchema): ?> data-theme="<?php $this->options->colorSchema(); ?>"<?php endif; ?>>
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php $this->archiveTitle('', '', ' | '); ?><?php $this->options->title(); ?><?php if ($this->is('index')): ?> | <?php $this->options->description() ?><?php endif; ?></title>
    <link rel="stylesheet" href="<?php $this->options->themeUrl('static/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('static/css/post-cards.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('static/css/overrides.css'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlight.js@11.10.0/styles/github.min.css">
    <?php if ($this->options->colorSchema == 'customize'): ?>
    <link rel="stylesheet" href="<?php $this->options->themeUrl('theme.css'); ?>">
    <?php endif; ?>
    <?php $this->header(); ?>
</head>

<body>

<?php
$homeAnnouncements = [];
if ($this->is('index') && $this->getCurrentPage() === 1) {
    $homeAnnouncements = classic22ParseHomeAnnouncements($this->options->homeAnnouncements ?? null);
}
?>

<?php if ($classic22LinuxDoFlashError !== ''): ?>
    <style>
        .classic22-linuxdo-banner {
            margin: .75rem auto 0;
            padding: .65rem .8rem;
            border: 1px solid var(--pico-muted-border-color, #d6d9e0);
            border-radius: var(--pico-border-radius, .5rem);
            background: var(--pico-card-background-color, rgba(0, 0, 0, .03));
            color: var(--pico-muted-color, #666);
            font-size: .95em;
        }
    </style>
    <div class="classic22-linuxdo-banner container-fluid" role="alert">
        <?php echo htmlspecialchars($classic22LinuxDoFlashError, ENT_QUOTES, $this->options->charset); ?>
    </div>
<?php endif; ?>

<header class="site-navbar container-fluid">
    <nav>
        <ul class="site-name">
        <?php if ($this->options->logoUrl): ?>
            <li><a href="<?php $this->options->siteUrl(); ?>" class="brand"><img src="<?php $this->options->logoUrl() ?>" alt="<?php $this->options->title() ?>"></a></li>
        <?php else: ?>
            <li>
                <a href="<?php $this->options->siteUrl(); ?>" class="brand"><?php $this->options->title() ?></a>
            </li>
            <li class="desc"><?php $this->options->description() ?></li>
        <?php endif; ?>

        <?php if (!empty($homeAnnouncements)): ?>
            <li class="classic22-home-announcements">
                <div class="classic22-announcement-rotator" data-announcement-rotator data-interval="3000">
                    <ul class="classic22-announcement-list" aria-label="<?php _e('公告'); ?>">
                        <?php foreach ($homeAnnouncements as $index => $announcement): ?>
                        <li class="classic22-announcement-item<?php echo $index === 0 ? ' is-active' : ''; ?>" data-type="<?php echo htmlspecialchars($announcement['type'], ENT_QUOTES, $this->options->charset); ?>">
                            <?php if (!empty($announcement['url'])): ?>
                            <a class="classic22-announcement-link" href="<?php echo htmlspecialchars($announcement['url'], ENT_QUOTES, $this->options->charset); ?>">
                            <?php else: ?>
                            <div class="classic22-announcement-link">
                            <?php endif; ?>
                                <span class="classic22-announcement-mark" aria-hidden="true">
                                    <?php if (!empty($announcement['emoji'])): ?>
                                        <span class="classic22-announcement-emoji"><?php echo htmlspecialchars($announcement['emoji'], ENT_QUOTES, $this->options->charset); ?></span>
                                    <?php else: ?>
                                        <?php echo classic22HomeAnnouncementIconSvg($announcement['type']); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="classic22-announcement-text">
                                    <?php echo htmlspecialchars($announcement['content'], ENT_QUOTES, $this->options->charset); ?>
                                </span>
                            <?php if (!empty($announcement['url'])): ?>
                            </a>
                            <?php else: ?>
                            </div>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </li>
        <?php endif; ?>
        </ul>

        <ul>
            <li>
                <label for="nav-toggler" class="nav-toggler-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12" /><line x1="3" y1="6" x2="21" y2="6" /><line x1="3" y1="18" x2="21" y2="18" /></svg>
                </label>
            </li>
        </ul>
    </nav>

    <nav class="site-nav">
        <input type="checkbox" id="nav-toggler">

        <ul class="nav-menu">
            <li>
                <a href="<?php $this->options->siteUrl(); ?>"<?php if ($this->is('index')): ?> class="active"<?php endif; ?>><?php _e('首页'); ?></a>
            </li>

            <?php \Widget\Contents\Page\Rows::alloc()->to($pages); ?>
            <?php while ($pages->next()): ?>
            <li>
                <a href="<?php $pages->permalink(); ?>"<?php if ($this->is('page', $pages->slug)): ?> class="active"<?php endif; ?>><?php $pages->title(); ?></a>
            </li>
            <?php endwhile; ?>
            <li class="nav-search-item">
                <form method="post" action="<?php $this->options->siteUrl(); ?>" class="nav-search-form">
                    <input type="search" id="s" name="s">
                </form>
                <a href="<?php echo htmlspecialchars($this->options->adminUrl, ENT_QUOTES, $this->options->charset); ?>" class="nav-login-btn nav-login-btn--search" title="登录后台" aria-label="登录后台">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-user-round-icon lucide-circle-user-round"><path d="M18 20a6 6 0 0 0-12 0"/><circle cx="12" cy="10" r="4"/><circle cx="12" cy="12" r="10"/></svg>
                </a>
            </li>
        </ul>
    </nav>
</header>
