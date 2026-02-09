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

.classic22-comment-actions .classic22-linuxdo-box p {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
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
    width: 100%;
    height: 100%;
    line-height: 1;
    text-decoration: none;
}

.classic22-linuxdo-login svg {
    display: block;
    flex: 0 0 auto;
    width: 2em;
    height: 2em;
}

.classic22-linuxdo-login span {
    display: inline-flex;
    align-items: center;
}

.classic22-comment-actions {
    display: flex;
    align-items: stretch;
    gap: .75rem;
    margin-top: .75rem;
}

.classic22-comment-actions .classic22-comment-submit {
    margin: 0;
}

.classic22-comment-actions.has-linuxdo-login .classic22-comment-submit,
.classic22-comment-actions.has-linuxdo-login .classic22-linuxdo-box {
    min-height: calc(1.5rem + var(--pico-form-element-spacing-vertical) * 2 + var(--pico-border-width) * 2);
}

.classic22-comment-actions.has-linuxdo-login .classic22-comment-submit {
    flex: 0 0 calc(50% - .375rem);
    max-width: calc(50% - .375rem);
}

.classic22-comment-actions .classic22-linuxdo-box {
    margin: 0;
    flex: 1 1 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 .8rem;
}

@media (max-width: 640px) {
    .classic22-comment-actions {
        flex-direction: column;
        gap: .5rem;
    }

    .classic22-comment-actions.has-linuxdo-login .classic22-comment-submit,
    .classic22-comment-actions .classic22-linuxdo-box {
        flex: 1 1 auto;
        max-width: none;
    }
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
                        <div class="grid">
                            <input type="text" placeholder="<?php _e('名字'); ?>" name="author" id="author" value="<?php $this->remember('author'); ?>" required/>
                            <input type="email" placeholder="<?php _e('Email'); ?>" name="mail" id="mail" value="<?php $this->remember('mail'); ?>"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?> />
                            <input type="url" placeholder="<?php _e('http://网站'); ?><?php if (!$this->options->commentsRequireUrl): ?><?php _e('（选填）'); ?><?php endif; ?>" name="url" id="url" value="<?php $this->remember('url'); ?>"<?php if ($this->options->commentsRequireUrl): ?> required<?php endif; ?> />
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="classic22-comment-actions<?php echo (!$this->user->hasLogin() && $linuxDoEnabled && !is_array($linuxDoUser)) ? ' has-linuxdo-login' : ''; ?>">
                    <button type="submit" class="classic22-comment-submit"><?php _e('提交评论'); ?></button>

                    <?php if (!$this->user->hasLogin() && $linuxDoEnabled && !is_array($linuxDoUser)): ?>
                        <div class="classic22-linuxdo-box">
                            <p>
                                <a class="classic22-linuxdo-login" href="<?php echo htmlspecialchars($linuxDoLoginUrl, ENT_QUOTES, $this->options->charset); ?>">
                                    <svg width="240" height="240" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <clipPath id="a"><circle cx="60" cy="60" r="47"/></clipPath>
                                        <circle fill="#f0f0f0" cx="60" cy="60" r="50"/>
                                        <rect fill="#1c1c1e" clip-path="url(#a)" x="10" y="10" width="100" height="30"/>
                                        <rect fill="#f0f0f0" clip-path="url(#a)" x="10" y="40" width="100" height="40"/>
                                        <rect fill="#ffb003" clip-path="url(#a)" x="10" y="80" width="100" height="30"/>
                                    </svg>
                                    <span>Linux Do 登录</span>
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="text-center text-muted"><?php _e('评论已关闭'); ?></div>
    <?php endif; ?>
</div>
