<?php $__env->startSection('title', 'Регистрация'); ?>
<?php $__env->startSection('content'); ?>

<div class="wrapper">
<main class="page">
<div class="auth-page">
<div class="auth">
<div class="logo"><img alt="logo" src="<?php echo e(asset('personal-acc/img/logo.svg')); ?>"/></div>
<form action="#" class="auth-form" method="post">
<?php echo csrf_field(); ?>

<div class="field">
<input placeholder="First Name" type="text"/>
</div>
<div class="field">
<input placeholder="Last Name" type="text"/>
</div>
<div class="field">
<input placeholder="E-mail" type="email"/>
</div>
<div class="field">
<select>
<option selected="" value="">Country</option>
<option value="2">Пункт №2</option>
<option value="3">Пункт №3</option>
<option value="4">Пункт №4</option>
</select>
</div>
<div class="field">
<input name="date-of-birth" type="date"/>
</div>
<div class="field">
<div class="field__wrapper">
<input placeholder="Password" type="password"/>
<button class="field__icon" type="button">
<img alt="eye" src="<?php echo e(asset('personal-acc/img/icons/eye.svg')); ?>"/>
</button>
</div>
</div>
<button class="btn" type="submit">Sign in</button>
</form>
</div>
</div>
</main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/olaf-dashboard/resources/views/auth/register.blade.php ENDPATH**/ ?>