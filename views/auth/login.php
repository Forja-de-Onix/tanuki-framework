<div class="view-container">
    <div class="card">
        <h1><?= e(t('auth.login_title')) ?></h1>
        <form method="POST" action="/login">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email"><?= e(t('auth.email_label')) ?></label>
                <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password"><?= e(t('auth.password_label')) ?></label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-actions" style="justify-content:space-between;">
                <a href="/forgot-password" class="text-muted"><?= e(t('auth.forgot_password_link')) ?></a>
                <button type="submit" class="btn btn-primary"><?= e(t('auth.login_button')) ?></button>
            </div>
        </form>
    </div>
    <p class="text-muted" style="text-align:center;margin-top:1rem;">
        <?= e(t('auth.no_account')) ?> <a href="/register"><?= e(t('auth.register_title')) ?></a>
    </p>
</div>