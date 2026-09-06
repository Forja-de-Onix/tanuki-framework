<div class="view-container">
    <div class="card">
        <h1><?= e(t('auth.forgot_title')) ?></h1>
        <form method="POST" action="/forgot-password">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email"><?= e(t('auth.email_label')) ?></label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= e(t('auth.send_reset_link_button')) ?></button>
            </div>
        </form>
    </div>
    <p class="text-muted" style="text-align:center;margin-top:1rem;">
        <a href="/login"><?= e(t('auth.back_to_login')) ?></a>
    </p>
</div>