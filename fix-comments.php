<?php
/**
 * Fix inline comment punctuation - both standalone and trailing comments.
 * PHPCS WordPress standard requires inline comments to end in . ! ?
 */

$files = [
    'public_html/wp-content/plugins/mosque-timetable/mosque-timetable.php',
    'public_html/wp-content/plugins/mosque-timetable/includes/helpers.php',
    'public_html/wp-content/plugins/mosque-timetable/includes/class-mosque-timetable.php',
];

/**
 * Returns true if the comment text is "ok to add a period" to.
 */
function needs_period( string $text ): bool {
    $text = trim( $text );

    if ( '' === $text ) return false;

    // Already ends correctly.
    if ( preg_match( '/[.!?]$/', $text ) ) return false;

    // phpcs directives.
    if ( str_contains( $text, 'phpcs:' ) ) return false;

    // URL at the end.
    if ( preg_match( '/https?:\/\/\S+$/', $text ) ) return false;

    // Separator lines like === or ----
    if ( preg_match( '/^[\s=\-*#~]+$/', $text ) ) return false;

    // @annotations
    if ( preg_match( '/^\s*@/', $text ) ) return false;

    // Ends with a code-like closer: ) ] } ; (but not trailing text comments).
    // We do NOT exclude ) ] } ; here because trailing comments often come after ) on the same line.

    return true;
}

foreach ( $files as $file ) {
    $lines   = file( $file );
    $changed = 0;

    foreach ( $lines as $i => $line ) {
        $raw     = rtrim( $line, "\r\n" );
        $eol     = str_ends_with( $line, "\r\n" ) ? "\r\n" : "\n";

        // --- Case 1: Line is purely a comment (starts with optional whitespace then //)
        if ( preg_match( '/^(\s*)(\/\/.*)$/', $raw, $m ) ) {
            $indent  = $m[1];
            $comment = $m[2]; // includes the //

            if ( str_contains( $comment, 'phpcs:' ) ) continue;

            // Extract text after //
            $text = substr( $comment, 2 );

            if ( needs_period( $text ) ) {
                $lines[ $i ] = $indent . '//' . $text . '.' . $eol;
                $changed++;
            }
            continue;
        }

        // --- Case 2: Trailing comment — code; // comment text
        // Find last occurrence of // that isn't inside a string.
        // Simple heuristic: find // that appears after a ; or , or ) and is not inside '...' or "..."
        if ( ! str_contains( $raw, '//' ) ) continue;

        // Use a simple state machine to find the comment start position.
        $in_single = false;
        $in_double = false;
        $comment_pos = -1;

        for ( $j = 0; $j < strlen( $raw ) - 1; $j++ ) {
            $ch   = $raw[ $j ];
            $next = $raw[ $j + 1 ];

            if ( ! $in_single && ! $in_double && $ch === "'" ) { $in_single = true; continue; }
            if ( $in_single && $ch === "'" && ( $j === 0 || $raw[ $j - 1 ] !== '\\' ) ) { $in_single = false; continue; }

            if ( ! $in_single && ! $in_double && $ch === '"' ) { $in_double = true; continue; }
            if ( $in_double && $ch === '"' && ( $j === 0 || $raw[ $j - 1 ] !== '\\' ) ) { $in_double = false; continue; }

            if ( ! $in_single && ! $in_double && $ch === '/' && $next === '/' ) {
                $comment_pos = $j;
                break;
            }
        }

        if ( $comment_pos < 0 ) continue;

        // Make sure there's actual code before the //.
        $before_comment = rtrim( substr( $raw, 0, $comment_pos ) );
        if ( '' === $before_comment ) continue; // Already handled by Case 1.

        $comment_text = substr( $raw, $comment_pos + 2 ); // text after //

        if ( ! needs_period( $comment_text ) ) continue;

        // Rebuild: code + ' // ' + comment + '.'
        $lines[ $i ] = $before_comment . ' //' . $comment_text . '.' . $eol;
        $changed++;
    }

    file_put_contents( $file, implode( '', $lines ) );
    echo "Fixed $changed comments in $file\n";
}
