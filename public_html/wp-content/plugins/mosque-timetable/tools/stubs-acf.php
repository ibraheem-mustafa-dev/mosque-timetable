<?php
// tools/stubs-acf.php — static analysis only

if ( ! function_exists( 'get_field' ) ) {
	/** @return mixed */
	function get_field( $selector, $post_id = false, $format_value = true ) {}
}
if ( ! function_exists( 'update_field' ) ) {
	function update_field( $selector, $value, $post_id = false ): bool {
		return true;
	}
}
if ( ! function_exists( 'delete_field' ) ) {
	function delete_field( $selector, $post_id = false ): bool {
		return true;
	}
}
if ( ! function_exists( 'acf_add_local_field_group' ) ) {
	function acf_add_local_field_group( array $field_group ): void {}
}
if ( ! function_exists( 'acf_form_head' ) ) {
	function acf_form_head(): void {}
}
if ( ! function_exists( 'acf_form' ) ) {
	function acf_form( array $args = array() ): void {}
}
