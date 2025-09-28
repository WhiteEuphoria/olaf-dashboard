<?php $__env->startSection('title', 'Вход в личный кабинет'); ?>

<?php $__env->startSection('content'); ?>
<div class="wrapper">
    <main class="page">
        <div class="auth-page">
            <div class="auth">
                <div class="logo">
                    <img src="<?php echo e(asset('personal-acc/img/logo.svg')); ?>" alt="logo">
                </div>
                <form class="auth-form" method="POST" action="<?php echo e(route('user.login.attempt')); ?>">
                    <?php echo csrf_field(); ?>

                    <div class="field">
                        <input type="email" name="email" placeholder="E-mail" value="<?php echo e(old('email')); ?>" required autofocus autocomplete="email">
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="field__error"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="field">
                        <div class="field__wrapper">
                            <input type="password" name="password" placeholder="Пароль" required autocomplete="current-password">
                            <button class="field__icon" type="button" data-password-toggle>
                                <img src="<?php echo e(asset('personal-acc/img/icons/eye.svg')); ?>" alt="eye">
                            </button>
                        </div>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="field__error"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="field" style="display:flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                        <label class="checkbox__label" style="gap:0.5rem; margin:0;">
                            <input class="checkbox__input" type="checkbox" name="remember" value="1" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                            <span class="checkbox__text">Запомнить меня</span>
                        </label>
                        <a href="<?php echo e(route('user.register')); ?>" style="font-size:0.875rem; font-weight:600; color:#0B69B7;">Регистрация</a>
                    </div>

                    <button class="btn" type="submit">Войти</button>

                    <?php if(session('status')): ?>
                        <div class="field__status"><?php echo e(session('status')); ?></div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.querySelector('[data-password-toggle]');
        if (!toggle) {
            return;
        }
        const input = toggle.previousElementSibling;
        toggle.addEventListener('click', function () {
            if (!input) {
                return;
            }
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            toggle.classList.toggle('is-active', type === 'text');
        });
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/olaf-dashboard/resources/views/user/auth/login.blade.php ENDPATH**/ ?>