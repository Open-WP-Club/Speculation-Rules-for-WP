<?php

/**
 * Plugin Name: Speculation Rules for WP
 * Description: Adds support for the Speculation Rules API to dynamically prefetch or prerender URLs based on user interaction.
 * Version: 1.0
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

  public function __construct()
  {
    add_action('admin_menu', array($this, 'add_plugin_page'));
    add_action('admin_init', array($this, 'page_init'));
    add_action('wp_head', array($this, 'add_speculation_rules'), PHP_INT_MAX);
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

    add_settings_field(
      'type',
      'Type',
      array($this, 'type_callback'),
      'speculation-rules-wp',
      'speculation_rules_setting_section'
    );

    add_settings_field(
      'eagerness',
      'Eagerness',
      array($this, 'eagerness_callback'),
      'speculation-rules-wp',
      'speculation_rules_setting_section'
    );

    add_settings_field(
      'match_urls',
      'Match URLs',
      array($this, 'match_urls_callback'),
      'speculation-rules-wp',
      'speculation_rules_setting_section'
    );

    add_settings_field(
      'exclude_urls',
      'Exclude URLs',
      array($this, 'exclude_urls_callback'),
      'speculation-rules-wp',
      'speculation_rules_setting_section'
    );
  }

  public function sanitize($input)
  {
    $sanitary_values = array();
    if (isset($input['type'])) {
      $sanitary_values['type'] = sanitize_text_field($input['type']);
    }
    if (isset($input['eagerness'])) {
      $sanitary_values['eagerness'] = sanitize_text_field($input['eagerness']);
    }
    if (isset($input['match_urls'])) {
      $sanitary_values['match_urls'] = sanitize_textarea_field($input['match_urls']);
    }
    if (isset($input['exclude_urls'])) {
      $sanitary_values['exclude_urls'] = sanitize_textarea_field($input['exclude_urls']);
    }
    return $sanitary_values;
  }

  public function section_info()
  {
    echo 'Enter your settings below:';
  }

  public function type_callback()
  {
  ?>
    <select name="speculation_rules_options[type]" id="type">
      <option value="prefetch" <?php selected($this->options['type'], 'prefetch'); ?>>Prefetch – Load the page only, no subresources</option>
      <option value="prerender" <?php selected($this->options['type'], 'prerender'); ?>>Prerender – Fully load the page and all subresources</option>
    </select>
    <p class="description">
      Prerendering will lead to faster load times than prefetching. However, in case of interactive content, prefetching may be a safer choice.
    </p>
  <?php
  }

  public function eagerness_callback()
  {
  ?>
    <select name="speculation_rules_options[eagerness]" id="eagerness">
      <option value="conservative" <?php selected($this->options['eagerness'], 'conservative'); ?>>Conservative (typically on click)</option>
      <option value="moderate" <?php selected($this->options['eagerness'], 'moderate'); ?>>Moderate (typically on hover)</option>
      <option value="eager" <?php selected($this->options['eagerness'], 'eager'); ?>>Eager (on slightest suggestion)</option>
    </select>
    <p class="description">
      The eagerness setting defines the heuristics based on which the loading is triggered. "Eager" will have the minimum delay to start speculative loads, "Conservative" increases the chance that only URLs the user actually navigates to are loaded.
    </p>
  <?php
  }

  public function match_urls_callback()
  {
  ?>
    <textarea name="speculation_rules_options[match_urls]" id="match_urls" rows="5" cols="50"><?php echo isset($this->options['match_urls']) ? esc_textarea($this->options['match_urls']) : ''; ?></textarea>
    <p class="description">Enter URLs to prefetch or prerender (one per line). Example: /*, /products/*, /services</p>
  <?php
  }

  public function exclude_urls_callback()
  {
  ?>
    <textarea name="speculation_rules_options[exclude_urls]" id="exclude_urls" rows="5" cols="50"><?php echo isset($this->options['exclude_urls']) ? esc_textarea($this->options['exclude_urls']) : ''; ?></textarea>
    <p class="description">Enter URLs to exclude from prefetching or prerendering (one per line).</p>
<?php
  }

  public function add_speculation_rules()
  {
    $options = get_option('speculation_rules_options');
    if (!$options) {
      return;
    }

    $type = isset($options['type']) ? $options['type'] : 'prefetch';
    $eagerness = isset($options['eagerness']) ? $options['eagerness'] : 'moderate';
    $match_urls = isset($options['match_urls']) ? explode("\n", $options['match_urls']) : array();
    $exclude_urls = isset($options['exclude_urls']) ? explode("\n", $options['exclude_urls']) : array();

    $rules = array(
      'prerender' => array(),
      'prefetch' => array()
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

    if (!empty($rules[$type])) {
      echo "<!-- Speculation Rules added by Speculation Rules for WP plugin -->\n";
      echo "<script type=\"speculationrules\">\n";
      echo json_encode(array('prerender' => $rules[$type]), JSON_PRETTY_PRINT);
      echo "\n</script>\n";
    }
  }
}

$speculation_rules_api = new Speculation_Rules_API();
