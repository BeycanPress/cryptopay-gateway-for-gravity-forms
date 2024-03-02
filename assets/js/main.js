;(($) => {
    $(document).ready(() => {
        let startedApp;
        let oldAmount = 0;
        const currency = gf_cryptopay_vars.currency;
        const submitButton = gf_cryptopay_vars.submitButton;
        const fieldInputId = gf_cryptopay_vars.fieldInputId;

        $(".gform_body [aria-required='true']").each(function() {
            $(this).attr('required', true);
        });

        const paymentCompleted = async (ctx, formId) => {
            const form = $('#gform_' + formId);
            const helpers = window.cpHelpers || window.cplHelpers;
            const txHash = ctx.transaction.hash || ctx.transaction.id;

            console.log(ctx)
            // Modal yapısına geçilecek ve manuel olarak required alan kontrolü yapılacak
            // Create temporary payment completed record
            // Submit form
            // entry'ler ile transactionlar ilişkilendirilecek, 
            // eğer bir kullanıcıya ve form id'sine ait tx varsa ödeme yapılmıştır sayılacak
            // ve entry oluşturulduğunda tx ile ilişkilendirilecek
            // eğer yoksa ödeme yapılması zorunlu olacak
            helpers.closePopup();
            await helpers.sleep(100);
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
            });
        }

        gform?.addFilter('gform_product_total', function (amount, formId) {
            if (amount !== oldAmount) {
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