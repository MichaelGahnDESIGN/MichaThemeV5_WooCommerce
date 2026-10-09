<?php
defined('ABSPATH') || exit;

/** Theme-Anpassungen: Bereiche mit Einfach- und Experten-Modus. */
add_action('customize_register', function (WP_Customize_Manager $wp): void {
    $cat = mt_catalog();
    $wp->add_panel('mt_panel', ['title' => 'MichaThemeV5', 'priority' => 30]);
    $wp->add_section('mt_mode', ['title' => 'Ansicht', 'panel' => 'mt_panel', 'priority' => 1]);
    $wp->add_setting('mt_expert_mode', ['default' => false, 'sanitize_callback' => 'rest_sanitize_boolean', 'transport' => 'postMessage']);
    $wp->add_control('mt_expert_mode', [
        'section' => 'mt_mode', 'type' => 'checkbox', 'label' => 'Expertenmodus',
        'description' => 'Zeigt zusätzliche Optionen für Fortgeschrittene. Die Grundeinstellungen reichen für die meisten Shops.',
    ]);
    foreach ($cat['groups'] as $gid => $g) {
        $wp->add_section('mt_' . $gid, ['title' => $g['label'], 'description' => $g['help'], 'panel' => 'mt_panel']);
    }
    $expert = static fn (): bool => (bool) get_theme_mod('mt_expert_mode', false);
    foreach ($cat['options'] as $id => $o) {
        $args = [
            'default' => $o['default'],
            'transport' => 'refresh',
            'sanitize_callback' => static fn ($v) => mt_sanitize_value($o, $v),
        ];
        $wp->add_setting($o['key'], $args);
        $ctl = ['section' => 'mt_' . $o['group'], 'label' => $o['label'], 'description' => $o['help']];
        if ($o['mode'] === 'expert') {
            $ctl['active_callback'] = $expert;
        }
        if (!empty($o['locked_by_law'])) {
            $ctl['description'] = trim($o['help'] . ' (Pflichtangabe, bitte nur mit Bedacht ändern.)');
        }
        switch ($o['type']) {
            case 'select':
            case 'font':
                $wp->add_control($o['key'], $ctl + ['type' => 'select', 'choices' => $o['choices']]);
                break;
            case 'toggle':
                $wp->add_control($o['key'], $ctl + ['type' => 'checkbox']);
                break;
            case 'color':
                $wp->add_control(new WP_Customize_Color_Control($wp, $o['key'], $ctl));
                break;
            case 'range':
                $wp->add_control($o['key'], $ctl + ['type' => 'range', 'input_attrs' => ['min' => $o['min'], 'max' => $o['max'], 'step' => $o['step']]]);
                break;
            case 'media':
                $wp->add_control(new WP_Customize_Image_Control($wp, $o['key'], $ctl));
                break;
            case 'code':
                $ctl['description'] = mt_pro('custom-code')
                    ? 'Pro-Modul Eigener Code ist freigeschaltet. Externe Skripte können eine Einwilligung der Besucher erfordern.'
                    : 'Verfügbar mit dem Pro-Modul Eigener Code (gültige Lizenz erforderlich). Siehe https://theme.michael-gahn.de';
                $wp->add_control($o['key'], $ctl + ['type' => 'textarea']);
                break;
            default:
                if ($id === 'license_token') {
                    $ctl['description'] = mt_license_status_text() . ' ' . $o['help'];
                }
                $wp->add_control($o['key'], $ctl + ['type' => 'text']);
        }
    }
});
