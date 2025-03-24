<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\GravityForms\Gateways;

// @phpcs:disable Generic.Files.InlineHTML
// @phpcs:disable WordPress.Security.NonceVerification.Missing
// @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

use BeycanPress\CryptoPay\Integrator\Hook;
use BeycanPress\CryptoPay\Integrator\Helpers;

abstract class AbstractGateway extends \GF_Field
{
    /**
     * @var int
     */
    // @phpcs:ignore
    public $type;

    /**
     * @var int
     */
    // @phpcs:ignore
    public $id;

    /**
     * @var string
     */
    // @phpcs:ignore
    public $failed_validation = false;

    /**
     * @var string
     */
    // @phpcs:ignore
    public $validation_message = '';

    /**
     * @var string
     */
    // @phpcs:ignore
    public $errorMessage = '';

    /**
     * @var string
     */
    // @phpcs:ignore
    public $theme = '';

    /**
     * @var string
     */
    public string $field_input_id = '';

    /**
     * @param array<mixed> $properties
     */
    public function __construct(array $properties = [])
    {
        parent::__construct($properties);
        $this->field_input_id = 'input_' . $this->id;

        // Actions.
        if (!has_action('gform_editor_js_set_default_values', [$this, 'editor_js_set_default_values'])) {
            add_action('gform_editor_js_set_default_values', [$this, 'editor_js_set_default_values']);
        }

        if (!has_action('gform_editor_js', [$this, 'editor_script'])) {
            add_action('gform_editor_js', [$this, 'editor_script']);
        }

        add_filter('gform_submit_button', [$this, 'form_submit_button'], 10, 2);
        add_filter('gform_entry_post_save', [$this, 'add_entry_id_to_tx'], 10, 2);
        add_action('gform_after_delete_field', [$this, 'clean_form_ids_in_txs'], 10, 2);
    }

    /**
     * @param int $formId
     * @return string
     */
    abstract public function run(int $formId): string;

    /**
     * @return array<string,mixed>
     */
    public function get_form_editor_button(): array
    {
        /** @disregard */
        return [
            'group' => 'cryptopay_fields',
            'text'  => $this->get_form_editor_field_title()
        ];
    }

    /**
     * @param array<string,mixed> $fieldGroups
     * @return array<string,mixed>
     */
    private function add_pay_field_group(array $fieldGroups): array
    {
        if (!isset($fieldGroups['cryptopay_fields'])) {
            $fieldGroups['cryptopay_fields'] = [
                'name'   => 'cryptopay_fields',
                'label'  => __('CryptoPay Fields', 'cryptopay-gateway-for-gravity-forms'),
                'fields' => [],
            ];
        }

        return $fieldGroups;
    }

    /**
     * @param array<string,mixed> $fieldGroups
     * @return array<string,mixed>
     */
    // @phpcs:ignore
    public function add_button($fieldGroups): array
    {
        return parent::add_button(self::add_pay_field_group($fieldGroups));
    }

    /**
     * @return array<string>
     */
    public function get_form_editor_field_settings(): array
    {
        return ['cryptopay_field_setting'];
    }

    /**
     * @param bool $works
     * @return string
     */
    private function get_field_works_or_expect_msg(bool $works = true): string
    {
        $msg = $works
            /* translators: %s: field title */
            ? esc_html__('The %s process will appear on the front-end and the form can be sent when the user completes the payment.', 'cryptopay-gateway-for-gravity-forms') // phpcs:ignore
            /* translators: %s: field title */
            : esc_html__('Please add a total field to your form for %s works.', 'cryptopay-gateway-for-gravity-forms');

        /** @disregard */
        return sprintf($msg, $this->get_form_editor_field_title());
    }
    /**
     * @return void
     */
    public function editor_script(): void
    {
        // phpcs:disable
        wp_add_inline_script(
            'crypto-pay-gateway-for-gravity-forms',
            `;(function ($) {
                $(document).ready(() => {
                    let currentFieldId = 0;
                    const fieldList = $("#gform_fields");
                    const customSubmit = $(".custom-submit-placeholder");
                    const customSubmitParent = customSubmit.closest('#field_submit');
                    if (customSubmitParent) {
                        customSubmitParent.hide();
                    }

                    const hideSubmit = () => {
                        currentFieldId = 0;
                        customSubmit.hide();
                        customSubmitParent.hide();
                        $('#field_submit').hide();
                    }

                    const showSubmit = () => {
                        customSubmit.show();
                        customSubmitParent.show();
                        $('#field_submit').show();
                    }

                    $(document).on('gform_field_added', function (event, form, field) {
                        if (field.type === '` . esc_js($this->type) . `') {
                            hideSubmit()
                            currentFieldId = parseInt(field.id);
                        }
                        if (form.fields.some(field => field.type === 'total')) {
                            $('#field_' + currentFieldId + ' .ginput_container')?.html(
                                '` . esc_js($this->get_field_works_or_expect_msg()) . `'
                            );
                        }
                    });
                    $(document).on('gform_field_deleted', function (event, form, fieldId) {
                        if (parseInt(fieldId) === currentFieldId) {
                            showSubmit();
                        }
                        if (!form.fields.some(field => field.type === 'total')) {
                            $('#field_' + currentFieldId + ' .ginput_container')?.html(
                                '` . esc_js($this->get_field_works_or_expect_msg(false)) . `'
                            );
                        }
                        if (!form.fields.some(field => field.type === '` . esc_js($this->type) . `')) {
                            showSubmit();
                        }
                    });
                })
            })(jQuery);`,
            'after'
        );
        // phpcs:enable
    }

    /**
     * @return void
     */
    // @phpcs:ignore
    public function editor_js_set_default_values(): void
    {
        // phpcs:disable
        ?>
        case '<?php echo esc_js($this->type); ?>' :
            if (!field.label) {
                field.label = '<?php /** @disregard */ echo esc_js($this->get_form_editor_field_title()); ?>';
            }
        break;
        <?php
        // phpcs:enable
    }

    // @phpcs:disable Generic.Files.InlineHTML
    // @phpcs:disable WordPress.Security.NonceVerification.Missing
    // @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

    /**
     * @param array<string,mixed> $form
     * @param string $value
     * @param array<string,mixed> $entry
     * @return string
     */
    // @phpcs:ignore
    public function get_field_input($form, $value = '', $entry = null): string
    {
        ob_start();
        ?>
            <div class='ginput_container ginput_container_cp_info'>
                <?php echo esc_html($this->get_field_works_or_expect_msg($this->form_hash_total_field($form))); ?>
            </div>
        <?php
        return ob_get_clean();
    }

    /**
     * @param array<string,mixed> $form
     * @return bool
     */
    private function form_hash_total_field(array $form): bool
    {
        return (bool) array_search('total', array_column($form['fields'], 'type'));
    }

    /**
     * @param array<string,mixed> $form
     * @return bool
     */
    private function form_has_this_field(array $form): bool
    {
        return (bool) array_search($this->id, array_column($form['fields'], 'id'));
    }

    /**
     * @param array<string,mixed> $form
     * @return bool
     */
    private function is_this_field_works(array $form): bool
    {
        return $this->form_hash_total_field($form) && $this->form_has_this_field($form);
    }

    /**
     * @param string $value
     * @return string
     */
    private function create_hidden_tx_input(string $value = ''): string
    {
        // phpcs:ignore
        $field = '<input type="hidden" name="' . esc_attr($this->field_input_id) . '" id="' . esc_attr($this->field_input_id) . '" value="' . esc_attr($value) . '" />';

        if ('' != $value) {
            wp_add_inline_style(
                'cryptopay-gateway-for-gravity-forms-css',
                '.gfield:has(> #' . esc_html($this->field_input_id) . ') {display:none}'
            );
        }

        return $field;
    }

    /**
     * @param array<string,mixed> $form
     * @param object|null $tx
     * @return bool
     */
    private function is_this_form_needs_payment(array $form, ?object $tx = null): bool
    {
        if (!$tx) {
            $tx = $this->get_tx_with_user_and_form_id(strval($form['id']));
        }

        if (!$tx) {
            return true;
        }

        return (bool) $this->get_entry_with_tx($tx);
    }

    /**
     * @param string $formId
     * @return object|null
     */
    private function get_tx_with_user_and_form_id(string $formId): ?object
    {
        $model = Helpers::run('getModelByAddon', 'gravityforms');
        return $model->findOneByUserAndFormId(get_current_user_id(), $formId);
    }
    /**
     * @param object $tx
     * @return array<mixed>|null
     */
    private function get_entry_with_tx(object $tx): ?array
    {
        $params = json_decode($tx?->params ?? '');
        $entries = \GFAPI::get_entries([$params?->formId], [
            'status' => 'active',
            'field_filters' => [
                [
                    'key' => $this->id,
                    'value' => $tx->hash,
                ]
            ]
        ]);

        return $entries[0] ?? null;
    }

    /**
     * @param string $formId
     * @return string
     */
    private function create_custom_submit_button(string $formId): string
    {
        return '<input type="submit" id="gform_submit_button_' . esc_attr($formId) . '" class="gform_button button" value="' . esc_attr__('Submit', 'cryptopay-gateway-for-gravity-forms') . '" onclick="if(window[&quot;gf_submitting_' . esc_attr($formId) . '&quot;]){return false;}  if( !jQuery(&quot;#gform_' . esc_attr($formId) . '&quot;)[0].checkValidity || jQuery(&quot;#gform_' . esc_attr($formId) . '&quot;)[0].checkValidity()){window[&quot;gf_submitting_' . esc_attr($formId) . '&quot;]=true;}  " onkeypress="if( event.keyCode == 13 ){ if(window[&quot;gf_submitting_' . esc_attr($formId) . '&quot;]){return false;} if( !jQuery(&quot;#gform_' . esc_attr($formId) . '&quot;)[0].checkValidity || jQuery(&quot;#gform_' . esc_attr($formId) . '&quot;)[0].checkValidity()){window[&quot;gf_submitting_' . esc_attr($formId) . '&quot;]=true;}  jQuery(&quot;#gform_' . esc_attr($formId) . '&quot;).trigger(&quot;submit&quot;,[true]); }" data-conditional-logic="visible">'; // phpcs:ignore
    }

    /**
     * @param string $button
     * @param array<string> $form
     * @return string
     */
    // @phpcs:ignore
    public function form_submit_button($button, $form): string
    {
        if ($this->is_this_field_works($form) && $this->is_this_form_needs_payment($form)) {
            if (!$this->is_admin_side()) {
                return '<div id="custom-submit-placeholder"></div>';
            } else {
                return '<div class="custom-submit-placeholder" style="display:none">' . $button . '</div>';
            }
        }

        return $button;
    }

    /**
     * @param array<string,mixed> $entry
     * @param array<string,mixed> $form
     * @return mixed
     */
    // @phpcs:ignore
    public function add_entry_id_to_tx($entry, $form): mixed
    {
        if (!$this->form_has_this_field($form)) {
            return $entry;
        }

        $model = Helpers::run('getModelByAddon', 'gravityforms');
        // In Gravity Forms process already have nonce process
        $txHash = sanitize_text_field(wp_unslash($_POST[$this->field_input_id] ?? ''));
        $model->updateOrderIdByTxHash($txHash, intval($entry['id']));

        return $entry;
    }

    /**
     * @param string $formId
     * @param string $fieldId
     * @return void
     */
    // @phpcs:ignore
    public function clean_form_ids_in_txs($formId, $fieldId): void
    {
        Helpers::run('getModelByAddon', 'gravityforms')->cleanFormIdsInTxs(strval($formId));
    }

    /**
     * @param string $formId
     * @param array<string> $deps
     * @return void
     */
    public function custom_enqueue_scripts(string $formId, array $deps): void
    {
        wp_enqueue_script(
            'cryptopay_main_js',
            GF_CRYPTOPAY_URL . 'assets/js/main.js',
            array_merge($deps, ['jquery']),
            GF_CRYPTOPAY_VERSION,
            true
        );

        wp_localize_script(
            'cryptopay_main_js',
            'gf_cryptopay_vars',
            [
                'formId' => $formId,
                'fieldId' => $this->id,
                'fieldInputId' => $this->field_input_id,
                'currency' => \GFCommon::get_currency(),
                'submitButton' => $this->create_custom_submit_button($formId),
                'pleaseFillForm' => esc_html__('Please fill in the required fields in the form before proceeding to the payment step!', 'cryptopay-gateway-for-gravity-forms'), // phpcs:ignore
            ]
        );
    }

    /**
     * @return bool
     */
    private function is_admin_side(): bool
    {
        /** @disregard */
        $isEntryDetail = $this->is_entry_detail();
        /** @disregard */
        $isFormEditor  = $this->is_form_editor();
        return $isEntryDetail || $isFormEditor;
    }

    /**
     * @param string $value
     * @param bool $forceFrontendLabel
     * @param array<string,mixed> $form
     * @return string
     */
    // @phpcs:ignore
    public function get_field_content($value, $forceFrontendLabel, $form): string
    {
        $formId       = absint($form['id']);
        /** @disregard */
        $adminButtons = $this->get_admin_buttons();
        /** @disregard */
        $fieldLabel   = $this->get_field_label($forceFrontendLabel, $value);

        if ($this->is_admin_side()) {
            return sprintf(
                /* translators: %s: field label */
                "%s<label class='gfield_label' for='%s'>%s</label>{FIELD}",
                $adminButtons,
                "input_{$this->id}",
                esc_html($fieldLabel)
            );
        }

        if (!$this->form_hash_total_field($form)) {
            /* translators: %s: field title */
            $msg = esc_html__('Please add a total field to your form for %s works.', 'cryptopay-gateway-for-gravity-forms'); // phpcs:ignore
            /** @disregard */
            return sprintf($msg, $this->get_form_editor_field_title());
        }

        Hook::addFilter('theme', function (array $theme) {
            $theme['mode'] = $this->theme ? $this->theme : 'light';
            return $theme;
        });

        $tx = $this->get_tx_with_user_and_form_id(strval($formId));
        $status = $this->is_this_form_needs_payment($form, $tx);

        if (!$status) {
            return $this->create_hidden_tx_input($tx->hash);
        }

        $html = $this->run(intval($formId));
        $html .= $this->create_hidden_tx_input();
        $this->custom_enqueue_scripts(strval($formId), []);

        return $html;
    }

    /**
     * @param string $value
     * @param array<string,mixed> $form
     * @return string
     */
    // @phpcs:ignore
    public function validate($value, $form): void
    {
        // In Gravity Forms process already have nonce process
        $txHash = sanitize_text_field(wp_unslash($_POST[$this->field_input_id] ?? ''));

        if (empty($txHash)) {
            $this->failed_validation  = true;
            $msg = esc_html__('A transaction id was not found, please complete the payment process!', 'cryptopay-gateway-for-gravity-forms'); // phpcs:ignore
            $this->validation_message = empty($this->errorMessage) ? $msg : $this->errorMessage;
        }
    }
}
