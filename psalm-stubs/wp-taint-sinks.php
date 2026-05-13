<?php
/**
 * Custom Psalm stubs that mark WordPress APIs as taint sinks.
 *
 * humanmade/psalm-plugin-wordpress provides type stubs for $wpdb and
 * other WP APIs but does NOT annotate them with @psalm-taint-sink, so
 * unescaped user input flowing into $wpdb->query, header(), etc. is not
 * flagged. This file supplements the WP plugin with the missing
 * security-relevant annotations.
 *
 * Consumers load this via the <stubs> section of their psalm config.
 *
 * @psalm-suppress DuplicateClass
 */

namespace {
	/**
	 * @psalm-suppress DuplicateClass
	 */
	class wpdb {
		/**
		 * @param string $query
		 * @psalm-taint-sink sql $query
		 * @psalm-flow ($query) -> return
		 * @return int|bool
		 */
		public function query( $query ) {}

		/**
		 * @param string|null $query
		 * @psalm-taint-sink sql $query
		 * @return array<int, mixed>|object|null
		 */
		public function get_results( $query = null, $output = OBJECT ) {}

		/**
		 * @param string|null $query
		 * @psalm-taint-sink sql $query
		 * @return mixed
		 */
		public function get_row( $query = null, $output = OBJECT, $row = 0 ) {}

		/**
		 * @param string|null $query
		 * @psalm-taint-sink sql $query
		 * @return string|null
		 */
		public function get_var( $query = null, $x = 0, $y = 0 ) {}

		/**
		 * @param string|null $query
		 * @psalm-taint-sink sql $query
		 * @return array<int, mixed>
		 */
		public function get_col( $query = null, $x = 0 ) {}
	}
}
