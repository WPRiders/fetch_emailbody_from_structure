?php 
/**
 * Author: Marius Vetrici, Mihai Irodiu from WPRiders
 * Author URL: http://www.wpriders.com
 * Description: Alternative way into extracting the path to the part number. It is a string of integers delimited by period which index into a body part list as per the IMAP4 specification.
 * PHP: The code was created and tested in PHP 5.5.26
 * License: GPL2
 */
function extract_body_part_path_alternative( $parts, &$path = array(), &$found ) {

	$encoding_type = false;

	foreach ( $parts as $index => $part ) {

		if ( $part->subtype === 'HTML' ) {
			$found  = true;
			$path[] = $index;

			return $part->encoding;
		} else {
			if ( isset( $part->parts ) ) {
				if ( ! $found ) {
					$path[] = $index;
				}
				$encoding_type = extract_body_part_path_alternative( $part->parts, $path, $found );

				if ( ! $found ) {
					array_pop( $path );
				}

			}
		}
	}

	return $encoding_type;
}

$structure      = imap_fetchstructure( $imap_connection, $email_number );
$structure_path = array();
$found          = false;

if ( isset( $structure->parts ) ) {
	$structure_encode = extract_body_part_path_alternative( $structure->parts, $structure_path, $found );// get the path into variable $structure_path by reference
	var_dump($structure_path); // see the array result, you will have to increment each value +1( array_filter ) and implode the array values to string
}else{
	//Treat exception here, see first example
}

?>
