<?php

/*
Plugin Name: Text Replacer
Description: Dynamically replaces any word or phrases across wordpress website.
Version: 1.0.0
Requires PHP: 7.3.5
Author: Uniqbank
Author URI: https://uniqbank.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    die('Monkeys cannot jump here...');
}

class TextReplacer
{

    function __construct()
    {
        add_action('admin_menu', array($this, 'adminMenu'));
        add_action('admin_init', array($this, 'adminSettings'));
        if (get_option('plugin_text_replacer_list')) {
            add_filter('the_content', array($this, 'replaceLogic'));
        }
    }

    function adminMenu()
    {
        $admin_menu_hook = add_menu_page(
            'Text Replacer',
            'Text Replacer',
            'manage_options',
            'text_replacer',
            array($this, 'textReplacerMainPageHTML'),
            'dashicons-text',
            50,
        );

        add_action("load-{$admin_menu_hook}", array($this, 'mainPageAssets'));
    }

    function textReplacerMainPageHTML()
    {
        $data = get_option('plugin_text_replacer_list', array());
        $counter = 1;
        ?>

        <div class="container">
            <br />
            <h3>Text Replacer</h3><br />
            <?php if (isset($_POST['justsubmitted']) && ($_POST['justsubmitted'] == "true"))
                $this->handleForm(); ?>
            <form method="POST">
                <input type="hidden" name="justsubmitted" value="true" />
                <?php wp_nonce_field('saveReplaceText', 'ourNonce') ?>
                <h6>Enter a list of words or exact phrases with replacement texts to replace in content of any post dynamically.
                </h6>
                <br />
                <?php
                if (count($data) == 0) { ?>
                    <div class="input-group mb-3 ">
                        <label class="input-group-text" for="inputGroupSelect01">#1</label>
                        <input type="text" class="form-control" placeholder="text to replace" aria-label=<?php echo esc_attr('text_to_replace__f1') ?> name=<?php echo esc_attr('text_to_replace__f1') ?> value="">
                        <span class="input-group-text">&#8594;</span>
                        <input type="text" class="form-control" placeholder="replacement text" aria-label=<?php echo esc_attr('replace_text_with__f1') ?> name=<?php echo esc_attr('replace_text_with__f1') ?> value="">
                    </div>
                    <div class="input-group mb-3 ">
                        <label class="input-group-text" for="inputGroupSelect01">#2</label>
                        <input type="text" class="form-control" placeholder="text to replace" aria-label=<?php echo esc_attr('text_to_replace__f2') ?> name=<?php echo esc_attr('text_to_replace__f2') ?> value="">
                        <span class="input-group-text">&#8594;</span>
                        <input type="text" class="form-control" placeholder="replacement text" aria-label=<?php echo esc_attr('replace_text_with__f2') ?> name=<?php echo esc_attr('replace_text_with__f2') ?> value="">
                    </div>
                <?php } else {
                    foreach ($data as $key => $value) {
                        foreach ($value as $k => $v) {
                            $fieldA = 'text_to_replace__f' . $counter;
                            $fieldB = 'replace_text_with__f' . $counter;
                            ?>
                            <div class="input-group mb-3 ">
                                <label class="input-group-text" for="inputGroupSelect01">#
                                    <?php echo esc_attr($counter); ?>
                                </label>
                                <input type="text" class="form-control" placeholder="text to replace" aria-label=<?php echo esc_attr($fieldA) ?> name=<?php echo esc_attr($fieldA) ?> value="<?php echo esc_attr($k); ?>">
                                <span class="input-group-text">&#8594;</span>
                                <input type="text" class="form-control" placeholder="replacement text" aria-label=<?php echo esc_attr($fieldB) ?> name=<?php echo esc_attr($fieldB) ?> value="<?php echo esc_attr($v); ?>">
                            </div>
                            <?php $counter++;
                        }
                    }
                }

                ?>
                </br>
                <input type="submit" name="submit" id="submit" class="btn btn-success" value="Save Changes" />
            </form>
        </div>
        <?php
    }

    function handleForm()
    {
        if (wp_verify_nonce($_POST['ourNonce'], 'saveReplaceText') and current_user_can('manage_options')) {

            $plugin_text_replacer_list = [];

            if (isset($_POST["text_to_replace__f1"]) && isset($_POST["replace_text_with__f1"])) {
                array_push(
                    $plugin_text_replacer_list,
                    array(
                        sanitize_text_field($_POST['text_to_replace__f1']) => sanitize_text_field($_POST['replace_text_with__f1'])
                    ),
                );
            }
            if (isset($_POST["text_to_replace__f2"]) && $_POST["replace_text_with__f2"]) {
                array_push(
                    $plugin_text_replacer_list,
                    array(
                        sanitize_text_field($_POST['text_to_replace__f2']) => sanitize_text_field($_POST['replace_text_with__f2'])
                    ),
                );
            }

            if ($plugin_text_replacer_list) {
                update_option('plugin_text_replacer_list', $plugin_text_replacer_list);
            }

            ?>
            <script type="text/javascript">
                window.location.reload();
            </script>

            <div class="updated">Your text list saved successfully</div>

        <?php } else { ?>
            <div class="error">
                <p>Sorry you don't have access to perform this operation.</p>
            </div>
        <?php }
    }

    function replaceLogic($content)
    {
        $new_content = $content;
        $data = get_option('plugin_text_replacer_list', array());

        foreach ($data as $key => $value) {
            foreach ($value as $k => $v) {
                $replaceWith = esc_html($v);
                $new_content = str_ireplace($k, $replaceWith, $new_content);
            }
        }

        return $new_content;
    }

    function mainPageAssets()
    {
        wp_enqueue_style('bootstrap-cdn-css', plugins_url('/assets/css/bootstrap.min.css', __FILE__));
        wp_enqueue_script('bootstrap-cdn-js', plugins_url('/assets/js/bootstrap.bundle.min.js', __FILE__));
    }

    function adminSettings()
    {
        add_settings_section('replacement-text-section', null, null, 'word-filter-options');
        register_setting('replacementFields', 'replacementText');
        add_settings_field('replacement-text', 'Filtered Text', array($this, 'replacementFieldHTML'), 'word-filter-options', 'replacement-text-section');

    }

}

$textReplacer = new TextReplacer();