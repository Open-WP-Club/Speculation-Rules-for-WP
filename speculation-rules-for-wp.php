<?php

/**
 * Plugin Name: Speculation Rules for WP
 * Description: Adds support for the Speculation Rules API to dynamically prefetch or prerender URLs based on user interaction.
 * Version: 1.1.5
 * Author: Gabriel Kanev
 * Author URI: https://openwpclub.com
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

class Speculation_Rules_API
{
  private $options;
  private $cache_key = 'speculation_rules_cache';

  public function __construct()
  {
    add_action('admin_menu', array($this, 'add_plugin_page'));
    add_action('admin_init', array($this, 'page_init'));
    add_action('wp_head', array($this, 'add_speculation_rules'), PHP_INT_MAX);
    add_action('save_post', array($this, 'clear_cache'));
    add_action('admin_notices', array($this, 'display_debug_info'));
  }

  public function add_plugin_page()
  {
    add_options_page(
      'Speculation Rules for WP Settings',
      'Speculation Rules for WP',
      'manage_options',
      'speculation-rules-wp',
      array($this, 'create_admin_page')
    );
  }

  public function create_admin_page()
  {
    $this->options = get_option('speculation_rules_options');
?>
    <div class="wrap">
      <h1>Speculation Rules for WP Settings</h1>
      <form method="post" action="options.php">
        <?php
        settings_fields('speculation_rules_options_group');
        do_settings_sections('speculation-rules-wp');
        submit_button();
        ?>
      </form>
    </div>
<?php
  }

  public function page_init()
  {
    register_setting(
      'speculation_rules_options_group',
      'speculation_rules_options',
      array($this, 'sanitize')
    );

    add_settings_section(
      'speculation_rules_setting_section',
      'Speculation Rules Settings',
      array($this, 'section_info'),
      'speculation-rules-wp'
    );

    $fields = array(
      'type' => 'Type',
      'eagerness' => 'Eagerness',
      'match_urls' => 'Match URLs',
      'exclude_urls' => 'Exclude URLs',
      'post_types' => 'Apply to Post Types',
      'debug_mode' => 'Debug Mode'
    );

    foreach ($fields as $field => $title) {
      add_settings_field(
        $field,
        $title,
        array($this, $field . '_callback'),
        'speculation-rules-wp',
        'speculation_rules_setting_section'
      );
    }
  }

  public function sanitize($input)
  {
    $sanitary_values = array();
    $fields = array('type', 'eagerness', 'match_urls', 'exclude_urls');
    foreach ($fields as $field) {
      if (isset($input[$field])) {
        $sanitary_values[$field] = sanitize_textarea_field($input[$field]);
      }
    }

    // Handle post_types as an array
    $sanitary_values['post_types'] = isset($input['post_types']) ? $input['post_types'] : array();

    // Ensure post_types is always an array
    if (!is_array($sanitary_values['post_types'])) {
      $sanitary_values['post_types'] = array($sanitary_values['post_types']);
    }

    // Sanitize each post type
    $sanitary_values['post_types'] = array_map('sanitize_text_field', $sanitary_values['post_types']);

    $sanitary_values['debug_mode'] = isset($input['debug_mode']) ? 1 : 0;

    // Debug: Log the input and sanitized values
    error_log('Speculation Rules Debug - Input: ' . print_r($input, true));
    error_log('Speculation Rules Debug - Sanitized: ' . print_r($sanitary_values, true));

    $this->clear_cache();
    return $sanitary_values;
  }

  public function section_info()
  {
    echo 'Enter your settings below:';
  }

  public function type_callback()
  {
    $this->render_select('type', array(
      'prefetch' => 'Prefetch – Load the page only, no subresources',
      'prerender' => 'Prerender – Fully load the page and all subresources'
    ));
  }

  public function eagerness_callback()
  {
    $this->render_select('eagerness', array(
      'conservative' => 'Conservative (typically on click)',
      'moderate' => 'Moderate (typically on hover)',
      'eager' => 'Eager (on slightest suggestion)'
    ));
  }

  public function match_urls_callback()
  {
    $this->render_textarea('match_urls', 'Enter URLs to prefetch or prerender (one per line). Example: /*, /products/*, /services');
  }

  public function exclude_urls_callback()
  {
    $this->render_textarea('exclude_urls', 'Enter URLs to exclude from prefetching or prerendering (one per line).');
  }

  public function post_types_callback()
  {
    $post_types = get_post_types(array('public' => true), 'objects');
    foreach ($post_types as $post_type) {
      $checked = isset($this->options['post_types']) && in_array($post_type->name, $this->options['post_types']) ? 'checked' : '';
      echo "<label><input type='checkbox' name='speculation_rules_options[post_types][]' value='{$post_type->name}' {$checked}> {$post_type->label}</label><br>";
    }
    // Debug: Output current post_types value
    echo '<div style="margin-top: 10px; color: #666;">Current saved post types: ' . implode(', ', $this->options['post_types'] ?? array()) . '</div>';
  }

  public function debug_mode_callback()
  {
    $checked = isset($this->options['debug_mode']) && $this->options['debug_mode'] ? 'checked' : '';
    echo "<label><input type='checkbox' name='speculation_rules_options[debug_mode]' value='1' {$checked}> Enable debug mode</label>";
  }

  private function render_select($name, $options)
  {
    echo "<select name='speculation_rules_options[$name]' id='$name'>";
    foreach ($options as $value => $label) {
      $selected = (isset($this->options[$name]) && $this->options[$name] === $value) ? 'selected' : '';
      echo "<option value='$value' $selected>$label</option>";
    }
    echo "</select>";
  }

  private function render_textarea($name, $description)
  {
    $value = isset($this->options[$name]) ? esc_textarea($this->options[$name]) : '';
    echo "<textarea name='speculation_rules_options[$name]' id='$name' rows='5' cols='50'>$value</textarea>";
    echo "<p class='description'>$description</p>";
  }

  public function add_speculation_rules()
  {
    $options = get_option('speculation_rules_options');
    if (!$options) {
      error_log('Speculation Rules Debug: No options found');
      return;
    }

    // Debug: Log the current options
    error_log('Speculation Rules Debug - Current Options: ' . print_r($options, true));

    // Check if we should apply rules to this post type
    if (!empty($options['post_types']) && is_singular()) {
      $post_type = get_post_type();
      if (!in_array($post_type, $options['post_types'])) {
        error_log('Speculation Rules Debug: Post type not in selected types');
        return;
      }
    }

    // Generate rules directly, bypassing cache for now
    $rules = $this->generate_rules($options);

    if (!empty($rules)) {
      echo "<!-- Speculation Rules added by Speculation Rules for WP plugin -->\n";
      echo "<script type=\"speculationrules\">\n";
      echo json_encode($rules, JSON_PRETTY_PRINT);
      echo "\n</script>\n";
      error_log('Speculation Rules Debug: Rules added to head');
    } else {
      error_log('Speculation Rules Debug: No rules generated');
    }

    if (!empty($options['debug_mode'])) {
      $this->add_debug_info($rules);
    }
  }

  private function generate_rules($options)
  {
    $type = isset($options['type']) ? $options['type'] : 'prefetch';
    $eagerness = isset($options['eagerness']) ? $options['eagerness'] : 'moderate';
    $match_urls = isset($options['match_urls']) ? explode("\n", $options['match_urls']) : array();
    $exclude_urls = isset($options['exclude_urls']) ? explode("\n", $options['exclude_urls']) : array();

    $rules = array(
      $type => array()
    );

    foreach ($match_urls as $url) {
      $url = trim($url);
      if (!empty($url)) {
        $rule = array(
          'source' => 'list',
          'urls' => array($url),
          'eagerness' => $eagerness
        );

        if (!empty($exclude_urls)) {
          $rule['not'] = array(
            'source' => 'list',
            'urls' => array_map('trim', $exclude_urls)
          );
        }

        $rules[$type][] = $rule;
      }
    }

    error_log('Speculation Rules Debug - Generated Rules: ' . print_r($rules, true));

    return $rules;
  }

  private function add_debug_info($rules)
  {
    echo "<!-- Speculation Rules Debug Info:\n";
    echo "Rules: " . print_r($rules, true) . "\n";
    echo "Current Post Type: " . get_post_type() . "\n";
    echo "Enabled Post Types: " . implode(', ', $this->options['post_types'] ?? array()) . "\n";
    echo "-->\n";
  }

  public function clear_cache()
  {
    delete_transient($this->cache_key);
  }

  public function display_debug_info()
  {
    if (isset($_GET['page']) && $_GET['page'] === 'speculation-rules-wp') {
      $options = get_option('speculation_rules_options');
      echo '<div class="notice notice-info is-dismissible">';
      echo '<p>Debug Info:</p>';
      echo '<pre>' . print_r($options, true) . '</pre>';
      echo '</div>';
    }
  }
}

$speculation_rules_api = new Speculation_Rules_API();
