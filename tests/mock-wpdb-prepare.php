<?php
// Fix Mock_WPDB prepare implementation to replace in exact sequential order:
function mock_prepare( $pdo, $query, $args ) {
	if ( empty( $args ) ) {
		return $query;
	}
	if ( count( $args ) === 1 && is_array( $args[0] ) ) {
		$args = $args[0];
	}

	$index = 0;
	return preg_replace_callback( '/(%[sdfF])/', function( $matches ) use ( &$index, $args, $pdo ) {
		if ( ! array_key_exists( $index, $args ) ) {
			return $matches[0];
		}
		$arg = $args[ $index++ ];
		$type = $matches[0];

		if ( '%s' === $type ) {
			return null === $arg ? 'NULL' : $pdo->quote( (string) $arg );
		} elseif ( '%d' === $type ) {
			return (string) (int) $arg;
		} elseif ( '%f' === $type || '%F' === $type ) {
			return null === $arg ? 'NULL' : (string) (float) $arg;
		}
		return (string) $arg;
	}, $query );
}
