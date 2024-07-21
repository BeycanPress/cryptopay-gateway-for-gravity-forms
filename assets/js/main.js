;(($) => {
    $(document).ready(() => {
        let startedApp;
        let oldAmount = 0;
        let currentAmount = 0;
        let completed = false;
        const {
            formId,
            currency,
            submitButton,
            fieldInputId,
            pleaseFillForm
        } = window.gf_cryptopay_vars;

        const checkFormEmpty = () => {
            var isEmpty = false;
            const el = $('#cryptopay, #cryptopay-lite');

            $('#gform_' + formId).find('[aria-required="true"]').each(function() {
                var elementType = $(this).prop('tagName').toLowerCase();
                var value = '';
                if (elementType === 'input' || elementType === 'select') {
                    value = $(this).val();
                } else if (elementType === 'textarea') {
                    value = $(this).text();
                } else if (elementType === 'checkbox') {
                    value = $(this).is(':checked') ? 'checked' : '';
                }
                if (!value) {
                    isEmpty = true;
                    return false;
                }
            });
        
            if (isEmpty || !currentAmount) {
                el.hide();
                if ($('#cpEmptyMessage').length === 0) {
                    $('<div id="cpEmptyMessage" class="gform_validation_errors" style="text-align:center">' + pleaseFillForm + '</div>').insertBefore(el);
                }
            } else {
                el.show();
                $('#cpEmptyMessage').remove();
            }
        }

        $('#gform_' + formId).on('change', checkFormEmpty);
        $('#gform_' + formId).on('keyup', checkFormEmpty);

        const paymentCompleted = async (ctx, formId) => {
            ctx.disablePopup = true;
            const form = $('#gform_' + formId);
            const helpers = window.cpHelpers || window.cplHelpers;
            const txHash = ctx.transaction.hash || ctx.transaction.id;
            helpers.successPopup('Payment completed successfully!').then(() => {
                $('.overlay').remove();
                startedApp.store.payment.$reset();
                $('#cryptopay, #cryptopay-lite').remove();

                // scroll to form
                const wrapperOffset = $('.gform_wrapper').offset().top;
                $('html, body').animate({scrollTop: wrapperOffset}, 1000);

                // set tx hash to hidden input
                $('#' + fieldInputId).val(txHash);
                $('#' + fieldInputId).closest('.gfield').hide();

                // submit form
                form.find('#custom-submit-placeholder').append(submitButton);
                form.submit();
                completed = true;
            });
        }

        gform?.addFilter('gform_product_total', function (amount, formId) {
            currentAmount = amount;
            if (amount && amount !== oldAmount && !completed) {
                oldAmount = amount;
                if (window.CryptoPayApp) {
                    CryptoPayApp.events.add('confirmationCompleted', async (ctx) => {
                        paymentCompleted(ctx, formId);
                    }, 'gravity_forms');
                    if (!startedApp) {
                        startedApp = window.CryptoPayApp.start({
                            amount,
                            currency,
                        }, { formId });
                    } else {
                        startedApp.reStart({
                            amount,
                            currency,
                        }, { formId })
                    }
                } else if (window.CryptoPayLiteApp) {
                    CryptoPayLiteApp.events.add('confirmationCompleted', async (ctx) => {
                        paymentCompleted(ctx, formId);
                    }, 'gravity_forms');
                    if (!startedApp) {
                        startedApp = window.CryptoPayLiteApp.start({
                            amount,
                            currency,
                        }, { formId });
                    } else {
                        startedApp.reStart({
                            amount,
                            currency,
                        }, { formId })
                    }
                }
            }
            return amount;
        });
    });
})(jQuery);