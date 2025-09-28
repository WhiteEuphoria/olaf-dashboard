<?php
    $violationErrors = $violationErrors ?? ($errors->violation ?? null);
    $violationStatus = $violationStatus ?? session('violation_status');
    $violationShouldOpen = $violationShouldOpen ?? (bool) ($violationStatus || ($violationErrors && $violationErrors->any()));
?>

<div aria-hidden="true" class="popup popup--md" id="violation">
    <div class="popup__wrapper">
        <div class="popup__content">
            <button class="popup__close" data-close type="button">
                <svg fill="none" height="20" viewBox="0 0 20 20" width="20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L19 19M19 1L1 19" stroke="black" stroke-linecap="round" stroke-width="2"></path>
                </svg>
            </button>
            <div class="modal-content">
                <div class="modal-content__top">
                    <div class="logo"><img alt="logo" src="<?php echo e(asset('personal-acc/img/logo.svg')); ?>"></div>
                    <div class="modal-content__text">
                        <p>Describe your complaint</p>
                    </div>
                </div>
                <div class="modal-content__body">
                    <?php if($violationStatus): ?>
                        <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 0.75rem; background: #ecfdf5; color: #047857; font-weight: 600; text-align: center;">
                            <?php echo e($violationStatus); ?>

                        </div>
                    <?php endif; ?>
                    <form action="<?php echo e(route('user.violation.store')); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <div class="field <?php echo e(($violationErrors && $violationErrors->has('details')) ? 'has-error' : ''); ?>">
                            <textarea name="details" placeholder="Write here..." rows="6" required><?php echo e(old('details')); ?></textarea>
                            <?php if($violationErrors && $violationErrors->has('details')): ?>
                                <span class="error-message"><?php echo e($violationErrors->first('details')); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="field field--upload" data-file-upload>
                            <label class="modal-content__file">
                                <input hidden type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.webp,.gif,.doc,.docx" data-file-upload-input data-max-files="5">
                                <span data-file-upload-label><?php echo e(__('Attach files (up to 5)')); ?></span>
                            </label>
                            <ul class="modal-content__file-list" data-file-upload-list></ul>
                            <?php ($attachmentError = $violationErrors ? ($violationErrors->first('attachments') ?: $violationErrors->first('attachments.*')) : null); ?>
                            <?php if($attachmentError): ?>
                                <span class="error-message"><?php echo e($attachmentError); ?></span>
                            <?php endif; ?>
                        </div>
                        <button class="btn" type="submit">Send</button>
                    </form>
</div>
</div>
</div>
</div>
</div>
<?php $__env->startPush('scripts'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (<?php echo json_encode((bool) $violationShouldOpen, 15, 512) ?>) {
                document.dispatchEvent(new CustomEvent('openPopup', { detail: '#violation' }));
            }
        });
    </script>
<?php $__env->stopPush(); ?>
<?php /**PATH /var/www/olaf-dashboard/resources/views/user/partials/violation-modal.blade.php ENDPATH**/ ?>