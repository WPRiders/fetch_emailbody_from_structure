<?php
/**
 * Author: Mihai Irodiu from WPRiders
 * Author URL: http://www.wpriders.com
 * Description: Extracting the path to the part number. It is a string of integers delimited by period which index into a body part list as per the IMAP4 specification.
 * PHP: The code was created and tested in PHP 5.5.26
 * License: GPL2
 */

/**
 * This snippet is part of the article published here: 
 * [TODO: add link back to our post here] 
 *
 * In case you want to work with us, please post your task here: 
 * www.wpriders.com/hire-us-ref
*/

function extract_body_part_path( $structure, $content_type_search = 'HTML' ) {
	/*
	 * This is an example of a structure, but it's not fixed and there will be some problems if the algorithm to find the content is not implemented correctly
	 * The structure will change if the e-mail is forwarded, reply, has attachments and so on.
	 * In my case, I only need the HTML
	 * Under testing, this method might take 0.000 to 0.001 seconds
	0 multipart/mixed
		1 multipart/alternative
			1.1 text/plain
			1.2 text/html
		2 message/rfc822
			2 multipart/mixed
				2.1 multipart/alternative
					2.1.1 text/plain
					2.1.2 text/html
				2.2 message/rfc822
					2.2 multipart/alternative
						2.2.1 text/plain
						2.2.2 text/html
	*/

	$iter = new RecursiveIteratorIterator(
		new RecursiveArrayIterator( $structure ),
		RecursiveIteratorIterator::SELF_FIRST );

	foreach ( $iter as $key => $value ) {
		if ( $value === $content_type_search ) {

			$keys          = array();
			$encoding_type = $iter->getSubIterator()->getArrayCopy()['encoding'];
			for ( $i = $iter->getDepth() - 1; $i >= 0; $i -- ) {
				//add each parent key, this is what we need
				$path_element = $iter->getSubIterator( $i )->key();
				if ( is_numeric( $path_element ) ) { // if it's not numeric, we don't need it.
					array_unshift( $keys, ( $path_element + 1 ) );
				}
			}

			//return our output array
			return array( 'path' => implode( '.', $keys ), 'encoding' => $encoding_type );
		}
	}

	//return false if not found, but will have exception for ->parts when is_null()
	return false;
}

function extract_body_part_path_exception( $structure, $structure_result, $content_type = 'HTML' ) {
	// if the encoding exists but the path is missing
	// then the structure must be missing the parts parameter and the HTML part is the first depth;
	// if the encoding is not empty(it can be zero), then we found the parameter subtype = 'HTML'
	if ( empty( $structure_result['path'] ) && '' !== $structure_result['encoding'] ) {
		if ( isset( $structure->subtype ) && $content_type === $structure->subtype ) {
			$structure_result['path'] = '1'; // remember, the path must be a string, not numeric

			return $structure_result;
		}
	} elseif ( ! empty( $structure_result['path'] ) && '' !== $structure_result['encoding'] ) {
		return $structure_result;
	} else {
		return false;
		// something is wrong, this e-mail does not contain the HTML structure,
		// you might want to add it to a log and ignore this e-mail processing further
	}
}

function your_decoding_method( $content, $get_content_type ) {
	//... Decoding email content code ...
	return $content;
}

// Example how to use

// ... code ( IMAP connection and so on )

$structure            = imap_fetchstructure( $imap_connection, $email_number );
$structure_path       = extract_body_part_path( $structure ); // second parameter is by default set to HTML, you can search anything else also ( PLAIN, HTML, MIXED ... )
$structure_path_final = extract_body_part_path_exception( $structure, $structure_path ); // original streucture $structure, result of the first search for path $structure_path.

// use of end result
echo $structure_path_final['path']; // result example '1.2', '2.1.2'...
echo $structure_path_final['encoding']; // integer value 0, 1, 2...

$email_html_content = imap_fetchbody( $imap_connection, $email_number, $structure_path_final['path'], FT_UID );
$content            = your_decoding_method( $content, (int) $structure_path_final['encoding'] );

// ... code ( process the content, close connection and so on )

// Remember, IMAP must be compiled with SSL in order to connect
?>
