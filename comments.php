<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$linuxDoEnabled = classic22LinuxDoIsConfigured($this->options);
$linuxDoUser = $linuxDoEnabled ? classic22LinuxDoCurrentUser($this->options) : null;
$linuxDoError = classic22LinuxDoConsumeError();

$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$requestUri = $requestUri === '' ? '/' : $requestUri;
$currentUrl = classic22LinuxDoNormalizeReturnTo($requestUri, $this->options);

$linuxDoLoginUrl = classic22LinuxDoBuildActionUrl($this->options, 'login', $currentUrl);
$linuxDoLogoutUrl = classic22LinuxDoBuildActionUrl($this->options, 'logout', $currentUrl);
?>

<style>
.classic22-linuxdo-box {
    margin: .75rem 0;
    padding: .65rem .8rem;
    border: 1px solid var(--pico-muted-border-color, #d6d9e0);
    border-radius: var(--pico-border-radius, .5rem);
    font-size: .95em;
}

.classic22-linuxdo-box p {
    margin: 0;
}

.classic22-linuxdo-box.is-error {
    border-color: #d63939;
    color: #d63939;
}

.classic22-linuxdo-login {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    text-decoration: none;
}

.classic22-linuxdo-login svg {
    width: 1em;
    height: 1em;
}
</style>

<div id="comments">
    <?php $this->comments()->to($comments); ?>
    <?php if ($comments->have()): ?>
        <h2 class="text-center"><?php $this->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></h2>

        <?php $comments->listComments(array(
            'commentStatus' => _t('你的评论正等待审核'),
            'avatarSize' => 64,
            'defaultAvatar' => 'identicon'
        )); ?>

        <nav><?php $comments->pageNav(_t('前一页'), _t('后一页'), 3, '...', array('wrapTag' => 'ul', 'itemTag' => 'li')); ?></nav>

    <?php endif; ?>

    <?php if ($this->allow('comment')): ?>
        <div id="<?php $this->respondId(); ?>" class="respond">
            <div class="cancel-comment-reply">
                <?php $comments->cancelReply(); ?>
            </div>

            <h5 id="response"><?php _e('你的评论'); ?></h5>

            <?php if ($linuxDoEnabled && $linuxDoError !== ''): ?>
                <div class="classic22-linuxdo-box is-error">
                    <p><?php echo htmlspecialchars($linuxDoError, ENT_QUOTES, $this->options->charset); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php $this->commentUrl() ?>" id="comment-form" role="form">
                <div class="grid">
                    <textarea placeholder="<?php _e('评论内容...'); ?>" rows="4" cols="300" name="text" id="textarea" required><?php $this->remember('text'); ?></textarea>
                </div>

                <?php if ($this->user->hasLogin()): ?>
                    <p>
                        <?php _e('登录身份：'); ?><a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a><span class="mx-2 text-muted">&middot;</span><a href="<?php $this->options->logoutUrl(); ?>"><?php _e('退出'); ?></a>
                    </p>
                <?php else: ?>
                    <?php if ($linuxDoEnabled && is_array($linuxDoUser)): ?>
                        <div class="classic22-linuxdo-box">
                            <p>
                                <?php _e('已使用 Linux Do 登录：'); ?>
                                <strong><?php echo htmlspecialchars((string) $linuxDoUser['author'], ENT_QUOTES, $this->options->charset); ?></strong>
                                <?php if (!empty($linuxDoUser['username'])): ?>
                                    <span class="text-muted">(@<?php echo htmlspecialchars((string) $linuxDoUser['username'], ENT_QUOTES, $this->options->charset); ?>)</span>
                                <?php endif; ?>
                                <span class="mx-2 text-muted">&middot;</span>
                                <a href="<?php echo htmlspecialchars($linuxDoLogoutUrl, ENT_QUOTES, $this->options->charset); ?>"><?php _e('退出 Linux Do'); ?></a>
                            </p>
                        </div>

                        <input type="hidden" name="author" value="<?php echo htmlspecialchars((string) $linuxDoUser['author'], ENT_QUOTES, $this->options->charset); ?>">
                        <input type="hidden" name="mail" value="<?php echo htmlspecialchars((string) $linuxDoUser['mail'], ENT_QUOTES, $this->options->charset); ?>">
                        <input type="hidden" name="url" value="<?php echo htmlspecialchars((string) ($linuxDoUser['url'] ?? ''), ENT_QUOTES, $this->options->charset); ?>">
                    <?php else: ?>
                        <?php if ($linuxDoEnabled): ?>
                            <div class="classic22-linuxdo-box">
                                <p>
                                    <a class="classic22-linuxdo-login" href="<?php echo htmlspecialchars($linuxDoLoginUrl, ENT_QUOTES, $this->options->charset); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                                        <span><?php _e('使用 Linux Do 登录评论'); ?></span>
                                    </a>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="grid">
                            <input type="text" placeholder="<?php _e('名字'); ?>" name="author" id="author" value="<?php $this->remember('author'); ?>" required/>
                            <input type="email" placeholder="<?php _e('Email'); ?>" name="mail" id="mail" value="<?php $this->remember('mail'); ?>"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?> />
                            <input type="url" placeholder="<?php _e('http://网站'); ?><?php if (!$this->options->commentsRequireUrl): ?><?php _e('（选填）'); ?><?php endif; ?>" name="url" id="url" value="<?php $this->remember('url'); ?>"<?php if ($this->options->commentsRequireUrl): ?> required<?php endif; ?> />
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <button type="submit"><?php _e('提交评论'); ?></button>
            </form>
        </div>
    <?php else: ?>
        <div class="text-center text-muted"><?php _e('评论已关闭'); ?></div>
    <?php endif; ?>
</div>
