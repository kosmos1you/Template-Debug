<?php
/**
 * Plugin Name: Показать путь к шаблону / Template Debug
 * Description: Показывает путь или имя текущего шаблона на фронтенде (только для администраторов). В меню «Настройки» появится пункт Template Debug, где можно включить/выключить отображение, выбрать режим показа и сделать путь кликабельным.
 * Version: 1.3
 * Author: https://github.com/kosmos1you/Template-Debug
 */

// Добавляем пункт меню в админку
add_action('admin_menu', 'tdv_add_admin_menu');
add_action('admin_init', 'tdv_register_settings');

function tdv_add_admin_menu() {
    add_options_page(
        'Template Debug',           // Заголовок страницы
        'Template Debug',           // Название в меню
        'manage_options',           // Право доступа (только админы)
        'template-debug-viewer',    // Слаг
        'tdv_settings_page'         // Callback для рендера
    );
}

function tdv_register_settings() {
    register_setting('tdv_settings_group', 'tdv_enabled');
    register_setting('tdv_settings_group', 'tdv_show_mode');
    register_setting('tdv_settings_group', 'tdv_clickable');
}

function tdv_settings_page() {
    ?>
    <div class="wrap">
        <h1>Показать путь к шаблону (Template Debug)</h1>
        <form method="post" action="options.php">
            <?php settings_fields('tdv_settings_group'); ?>
            <?php do_settings_sections('tdv_settings_group'); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Включить показ шаблона</th>
                    <td>
                        <input type="checkbox" name="tdv_enabled" value="1" <?php checked(1, get_option('tdv_enabled', 0)); ?> />
                        <label for="tdv_enabled"> Показывать шаблон (только администраторам)</label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Режим отображения</th>
                    <td>
                        <select name="tdv_show_mode">
                            <option value="full" <?php selected(get_option('tdv_show_mode', 'full'), 'full'); ?>>Полный путь</option>
                            <option value="basename" <?php selected(get_option('tdv_show_mode', 'full'), 'basename'); ?>>Только имя файла</option>
                        </select>
                        <p class="description">Выберите, что показывать: полный путь к файлу или только его имя.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Кликабельный путь</th>
                    <td>
                        <input type="checkbox" name="tdv_clickable" value="1" <?php checked(1, get_option('tdv_clickable', 1)); ?> />
                        <label for="tdv_clickable"> Сделать путь кликабельным (открывать файл в редакторе тем)</label>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Функция для показа шаблона внизу страницы
add_action('wp_footer', 'tdv_show_template');
function tdv_show_template() {
    if (current_user_can('manage_options') && get_option('tdv_enabled', 0)) {
        global $template;

        $mode   = get_option('tdv_show_mode', 'full');
        $output = ($mode === 'basename') ? basename($template) : $template;
        
        // Если включен кликабельный режим
        if (get_option('tdv_clickable', 1)) {
            $theme      = wp_get_theme();
            $theme_slug = $theme->get_stylesheet();

            // Нормализуем путь (для совместимости Windows/Linux)
            $template_path = wp_normalize_path($template);

            // Убираем полный путь к директории темы → оставляем только относительный файл
            $theme_dir = wp_normalize_path(get_stylesheet_directory());
            $relative_path = ltrim(str_replace($theme_dir, '', $template_path), '/');

            // Если вдруг файл не в дочерней теме, проверяем родительскую
            if ($relative_path === $template_path) {
                $parent_dir = wp_normalize_path(get_template_directory());
                $relative_path = ltrim(str_replace($parent_dir, '', $template_path), '/');
            }

            // Ссылка на Theme Editor (только имя файла внутри темы)
            $file_url = admin_url('theme-editor.php?file=' . urlencode($relative_path) . '&theme=' . urlencode($theme_slug));

            $output = '<a href="' . esc_url($file_url) . '" target="_blank" style="color:white;text-decoration:underline;">' . esc_html($output) . '</a>';
        } else {
            $output = esc_html($output);
        }

        echo '<div style="position:fixed;bottom:50px;right:10px;background:red;color:white;padding:10px;z-index:99999999;font-family:monospace;max-width:80%;word-break:break-all;">';
        echo 'Template: ' . $output;
        echo '</div>';
    }
}
