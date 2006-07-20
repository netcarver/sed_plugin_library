<?php

$plugin['name'] = 'sed_plugin_library';
$plugin['version'] = '0.1';
$plugin['author'] = 'Stephen Dickinson';
$plugin['author_uri'] = 'txp-plugins.netcarving.com';
$plugin['description'] = "Helper functions for sed plugins.";

$plugin['type'] = 2; // 0 = regular plugin; public only, 1 = admin plugin; public + admin, 2 = library

@include_once('zem_tpl.php');

if (0) {
?>
<!-- CSS 
# --- BEGIN PLUGIN CSS ---
<style type="text/css">
div#sed_help td { vertical-align:top; }
div#sed_help code { font-weight:bold; font: 105%/130% "Courier New", courier, monospace; background-color: #FFFFCC;}
div#sed_help code.sed_code_tag { font-weight:normal; border:1px dotted #999; background-color: #f0e68c; display:block; margin:10px 10px 20px; padding:10px; }
div#sed_help a:link, div#sed_help a:visited { color: blue; text-decoration: none; border-bottom: 1px solid blue; padding-bottom:1px;}
div#sed_help a:hover, div#sed_help a:active { color: blue; text-decoration: none; border-bottom: 2px solid blue; padding-bottom:1px;}
div#sed_help h1 { color: #369; font: 20px Georgia, sans-serif; margin: 0; text-align: center; }
div#sed_help h2 { border-bottom: 1px solid black; padding:10px 0 0; color: #369; font: 17px Georgia, sans-serif; }
div#sed_help h3 { color: #693; font: bold 12px Arial, sans-serif; letter-spacing: 1px; margin: 10px 0 0;text-transform: uppercase;}
</style>
# --- END PLUGIN CSS ---
-->
<!-- HELP
# --- BEGIN PLUGIN HELP ---
<div id="sed_help">

h1(#intro). Plugin Library

sed_plugin_library v0.1 (June 6th, 2006)

Provides some useful helper functions for plugins.

h2(#functions). Function Listing

|_. Function |_. Description |
| _extract_packed_vars | 	Returns an array of key->value mappings parsed from all the sections of a packed string. |
| _extract_packed_variable_section | 	Returns an array of key->value mappings parsed from one section of a packed string. |

Formats...

*packed string* ... _section_ ['|' _section_ ]
*section* ... _section-name_ '(' _variable-list_ ')' 
*variable-list* ... name='value' [ ';' variable-list ] 

h3(#examples). Examples

$string = "copyright(owner='Steve';start='1970')|location(state='sabah')|personal(dob='1/1/01';email='';phone='')";

h2(#versions). Version History

v0.1

* Functions to extract packed variables from strings.

</div>
# --- END PLUGIN HELP ---
-->
<?php
}

# --- BEGIN PLUGIN CODE ---

/*
	Returns an array of key->value mappings parsed from all the sections of a packed string.
*/

function _extract_packed_vars( $packed_string, $prefix='', $attach_name=true, $section_char='|', $variable_delim_char=';' ) {
	$result = array();

//	print_r( "Prefix / Section Delimiter / variable delimiter / Packed String.<br/>\n" ); 
//	print_r( $prefix.' / "'.$section_char.'" / "'.$variable_delim_char."\" / ".$packed_string."<br/>\n" );
	
	if( empty( $packed_string ) )
		return false;
	
	//
	//	Break the packed string on the section boundaries...
	//	
	$sections = explode( $section_char , $packed_string );
	$count = count( $sections );
//	print_r( "Found $count sections.<br/>\n" );
	if( 0 == $count ) 
		return false;

	foreach( $sections as $section ) {
		//
		//	Pull out the section name
		$section_len = strlen( $section );
		$len = strpos( $section , '(' );
		$section_name = substr( $section , 0 , $len );
		$content = substr( $section , $len + 1 , ($section_len - $len - 2) );
//		print_r( "Processing Section: [$section]. Section length=$section_len, Name Length=$len<br/>&nbsp;&nbsp;&nbsp;Content=[$content].<br/>\n" );

		//
		//	Split this section on the variable delimiter...
		//
		$chunks = explode( $variable_delim_char , $content );
//		$chunk_count = count( $chunks );
		
//		print_r( "Section '$section_name' exploded into $chunk_count chunks.<br/>\n" );
//		print_r( $chunks );
//		print_r( "<br>\n" );
		//
		//	Build the result array mapping 
		//  [ variable_x => value_x ]
		//
		foreach( $chunks as $chunk ) {
			list( $storage_key, $value ) = explode( '=', $chunk );
//			print_r( "Chunk [$chunk] -> Found key [$storage_key] and value [$value].<br/>\n" );
			if( $attach_name ) $storage_key = $section_name.'_'.$storage_key;
			if( !empty( $prefix) ) $storage_key = $prefix.'_'.$storage_key;
			$result[ $storage_key ] = trim( $value, " '\"" );
			}
		}
	
	return $result;
	}

	
/*
	Returns an array of key->value mappings parsed from one section of a packed string.
*/
function _extract_packed_variable_section( $section_name, $packed_string, $prefix='', $attach_name=false, $section_char='|', $variable_delim_char=';' ) {
	$result = array();

//	print_r( "Section Name / Prefix / Section Delimiter / variable delimiter / Packed String.<br/>\n" ); 
//	print_r( $section_name.' / '.$prefix.' / "'.$section_char.'" / "'.$variable_delim_char."\" / ".$packed_string."<br/>\n" );
	
	if( empty( $packed_string ) or empty( $section_name ) )
		return false;
	
	//
	//	Break the packed string on the section boundaries...
	//	
	$sections = explode( $section_char , $packed_string );
	$count = count( $sections );
//	print_r( "Found $count sections.<br/>\n" );
	if( 0 == $count ) 
		return false;

	//
	//	Find the section with the matching prefix. If it is not present 
	// then return false.
	//
	$found = false;
	$section = '';
	$len = strlen( $section_name );
	for( $i = 0; $i < $count; $i++ ) {
		$s = $sections[$i];
		if( $s{$len} === '(' )
			{
			if( substr( $s , 0 , $len ) == $section_name ) {
				$section = $s;
				$i = $count;
				}
			}
		}
	if( '' === $section ) {
//		print_r( "Failed to find section: $section_name in variables. Exiting." );
		return false;
		}

//	print_r( "Found section: $section_name in variables. Unpacking it now.<br/>\n" );
	//
	//	Split this section on the variable delimiter...
	//
	$section_len = strlen( $section );
	$content = substr( $section , $len + 1 , $section_len - $len - 2 );
//	print_r( "Content [$content].<br>\n" );
	$chunks = explode( $variable_delim_char , $content );
	
	//
	//	Build the result array mapping 
	//  [ variable_x => value_x ]
	//
	foreach( $chunks as $chunk ) {
		list( $storage_key, $value ) = explode( '=', $chunk );
//		print_r( "Chunk [$chunk] -> Found key [$storage_key] and value [$value].<br/>\n" );
		if( $attach_name ) $storage_key = $section_name.'_'.$storage_key;
		if( !empty( $prefix) ) $storage_key = $prefix.'_'.$storage_key;
		$result[ $storage_key ] = trim( $value, " '\"" );
		}
	
	return $result;
	}



# --- END PLUGIN CODE ---

?>
