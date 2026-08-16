<?php

class ProfileController extends Controller
{
    // ── GET /profile ─────────────────────────────────────────────────────────

    public function edit(): void
    {
        auth_require();

        $this->view('profile/edit', [
            'title' => t('profile.edit_title') . ' — ' . env('APP_NAME', 'Tanuki App'),
            'user'  => auth_user(),
        ]);
    }

    // ── POST /profile ────────────────────────────────────────────────────────

    public function update(): void
    {
        auth_require();

        if (!csrf_verify($this->request->post('_token'))) {
            $this->flash('error', t('auth.session_expired'));
            $this->redirect('/profile');
        }

        $user = auth_user();
        $name = trim((string) $this->request->post('name'));

        if (empty($name)) {
            keep_old(['name' => $name]);
            $this->flash('error', t('profile.name_required'));
            $this->redirect('/profile');
        }

        $data = ['name' => $name];

        $newPassword = (string) $this->request->post('new_password');

        // Password change is optional — only validate/apply it if the
        // developer actually typed something in the "new password" field.
        if ($newPassword !== '') {
            $currentPassword = (string) $this->request->post('current_password');
            $confirmation    = (string) $this->request->post('new_password_confirmation');

            if (!password_verify($currentPassword, $user['password'])) {
                keep_old(['name' => $name]);
                $this->flash('error', t('profile.current_password_incorrect'));
                $this->redirect('/profile');
            }

            if (strlen($newPassword) < 8) {
                keep_old(['name' => $name]);
                $this->flash('error', t('auth.password_too_short'));
                $this->redirect('/profile');
            }

            if ($newPassword !== $confirmation) {
                keep_old(['name' => $name]);
                $this->flash('error', t('profile.password_confirmation_mismatch'));
                $this->redirect('/profile');
            }

            $data['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        UserModel::update($user['id'], $data);

        $this->flash('success', t('profile.updated'));
        $this->redirect('/profile');
    }
}