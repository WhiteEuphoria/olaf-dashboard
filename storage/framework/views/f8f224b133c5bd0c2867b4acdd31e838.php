<?php $__env->startSection('title', 'Вход администратора'); ?>
<?php $__env->startSection('content'); ?>

<div class="wrapper">
<main class="page">
<div class="auth-page auth-page--accent">
<div class="auth">
<div class="logo"><img alt="logo" src="<?php echo e(asset('personal-acc/img/logo.svg')); ?>"/></div>
<form class="auth-form" method="POST" action="<?php echo e(route('admin.login.attempt')); ?>">
<?php echo csrf_field(); ?>

<div class="field">
<input placeholder="E-mail" type="email" name="email" value="<?php echo e(old('email')); ?>" required autocomplete="email" autofocus/>
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
<input placeholder="Пароль" type="password" name="password" required autocomplete="current-password"/>
<button class="field__icon" type="button">
<img alt="eye" src="<?php echo e(asset('personal-acc/img/icons/eye.svg')); ?>"/>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/admin/Pictures/html_blade-codex-fix-edit-user-and-edit-account-functionality-50to50/resources/views/admin/login.blade.php ENDPATH**/ ?>