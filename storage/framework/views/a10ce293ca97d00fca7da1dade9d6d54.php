<?php $__env->startSection('title', 'Enter'); ?>
<?php $__env->startSection('content'); ?>

<div class="wrapper">
<main class="page">
<div class="auth-page">
<div class="auth">
<div class="logo"><img alt="logo" src="<?php echo e(asset('personal-acc/img/logo.svg')); ?>"/></div>
<form class="enter-block" method="POST" action="<?php echo e(route('documents.store')); ?>" enctype="multipart/form-data">
<?php echo csrf_field(); ?>

<div class="enter-block__title">Attach your documents for verification</div>
<div class="enter-block__file">
<label class="file-btn">
<input hidden="" type="file" name="document" accept=".png,.jpg,.jpeg,.pdf" required/>
<span class="file-btn__icon"><img alt="download" src="<?php echo e(asset('personal-acc/img/icons/download.svg')); ?>"/></span>
<span>Upload file</span>
</label>
<?php $__errorArgs = ['document'];
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
<textarea placeholder="Write here..." name="comment" rows="4"><?php echo e(old('comment')); ?></textarea>
</div>
<div class="enter-block__info">
<div class="enter-block__info-item">
<span>Allowed format</span>
<br/>
								PNG, JPG, JPEG and PDF
							</div>
<div class="enter-block__info-item">
<span>Max file size</span>
<br/>
								10MB
							</div>
</div>
<?php $__errorArgs = ['comment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
<span class="field__error"><?php echo e($message); ?></span>
<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
<button class="btn btn--light" type="submit">Send</button>
<?php if(session('status')): ?>
<div class="field__status"><?php echo e(session('status')); ?></div>
<?php endif; ?>
</form>
</div>
</div>
</main>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/olaf-dashboard/resources/views/enter.blade.php ENDPATH**/ ?>