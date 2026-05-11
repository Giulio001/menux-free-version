<?php
/**
 * MenuX Pro — Helper Functions
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function menux_code_to_key($code) {
    return 'lang_' . str_replace('-', '_', $code);
}
